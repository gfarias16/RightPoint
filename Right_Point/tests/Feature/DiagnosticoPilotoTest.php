<?php

namespace Tests\Feature;

use App\Models\AtividadeEconomica;
use App\Models\ContagemEstabelecimento;
use App\Models\DiagnosticoSalvo;
use App\Models\FonteDado;
use App\Models\IndicadorMunicipal;
use App\Models\Municipio;
use App\Models\User;
use App\Services\ConsultaRendaIbge;
use App\Services\DiagnosticoPiloto;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DiagnosticoPilotoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
        Http::preventStrayRequests();
        $fontes = [];
        foreach (['ibge-censo-2022-populacao', ConsultaRendaIbge::CODIGO_FONTE, 'opencnpj-receita'] as $codigo) {
            $fontes[] = FonteDado::create(['codigo' => $codigo, 'nome' => $codigo, 'url_referencia' => 'https://example.org/'])->id;
        }
        foreach (DiagnosticoPiloto::CNAES as $cnae) {
            AtividadeEconomica::create(['codigo_cnae' => $cnae, 'nome' => 'Atividade '.$cnae]);
        }
        $indicadores = [];
        $contagens = [];
        $codigos = array_values(config('municipios_receita'));
        sort($codigos, SORT_STRING);
        foreach ($codigos as $i => $codigo) {
            $municipio = Municipio::create(['codigo_ibge' => $codigo, 'nome' => 'Município '.$i, 'uf' => 'RJ']);
            $base = ['municipio_id' => $municipio->id, 'periodo_referencia' => '2022', 'coletado_em' => '2026-11-04 18:00:00'];
            $indicadores[] = $base + ['fonte_dado_id' => $fontes[0], 'codigo_indicador' => 'populacao_residente', 'valor' => 10000, 'unidade' => 'Pessoas', 'versao_fonte' => 'Censo 2022 - agregado 4714 - variavel 93'];
            $indicadores[] = $base + ['fonte_dado_id' => $fontes[1], 'codigo_indicador' => ConsultaRendaIbge::CODIGO_INDICADOR, 'valor' => $i * 100, 'unidade' => 'Reais', 'versao_fonte' => ConsultaRendaIbge::VERSAO_RECORTE];
            foreach (AtividadeEconomica::all() as $atividade) {
                $contagens[] = ['municipio_id' => $municipio->id, 'atividade_economica_id' => $atividade->id, 'fonte_dado_id' => $fontes[2], 'quantidade' => 91 - $i, 'registros_no_recorte' => 91 - $i, 'cnpjs_ausentes' => 0, 'criterio' => 'ativos_cnae_principal_exato_v1', 'consultado_em' => DiagnosticoPiloto::COLETA_CNPJ];
            }
        }
        DB::table('indicadores_municipais')->insert($indicadores);
        DB::table('contagens_estabelecimentos')->insert($contagens);
    }

    public function test_quatro_cnaes_com_conjunto_completo_calculam_sem_escrita_ou_chamada_externa(): void
    {
        $antes = $this->retrato();
        foreach (DiagnosticoPiloto::CNAES as $cnae) {
            $resposta = $this->getJson('/consulta-demonstrativa?codigo_ibge=3306305&cnae='.$cnae)
                ->assertOk()->assertJsonPath('score', 100)->assertJsonPath('diagnostico.estado', 'experimental')
                ->assertJsonPath('diagnostico.metodo', DiagnosticoPiloto::METODO)
                ->assertJsonPath('diagnostico.conjunto.municipios_aptos', 92)
                ->assertJsonCount(92, 'diagnostico.conjunto.codigos_ibge')
                ->assertJsonPath('diagnostico.premissa_cnpj.ano_assumido', 2026)
                ->assertJsonPath('diagnostico.premissa_cnpj.confirmado_pela_fonte', false)
                ->assertJsonPath('concorrentes.periodo_referencia', null)
                ->assertJsonPath('concorrentes.versao_fonte', null)
                ->assertJsonPath('diagnostico.fatores.renda.contribuicao', 50);
            $this->assertSame(64, strlen($resposta->json('diagnostico.conjunto.sha256_dados')));
        }
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3300100&cnae=5611201')
            ->assertOk()->assertJsonPath('score', 0)->assertJsonPath('diagnostico.estado', 'experimental');
        $this->assertSame($antes, $this->retrato());
        Http::assertNothingSent();
    }

    #[DataProvider('fatoresInvalidos')]
    public function test_fator_invalido_em_outro_municipio_bloqueia_conjunto_sem_reduzir_amostra(string $fator, array $alteracoes): void
    {
        IndicadorMunicipal::where('municipio_id', 1)->where('codigo_indicador', $fator)->update($alteracoes);
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3306305&cnae=5611201')
            ->assertOk()->assertJsonPath('score', null)->assertJsonPath('diagnostico.estado', 'indisponivel')
            ->assertJsonPath('diagnostico.conjunto.municipios_aptos', 91)->assertJsonCount(1, 'diagnostico.motivos');
    }

    public static function fatoresInvalidos(): array
    {
        return [
            ['populacao_residente', ['valor' => 0]],
            ['populacao_residente', ['periodo_referencia' => '2025']],
            ['populacao_residente', ['unidade' => 'Mil pessoas']],
            ['populacao_residente', ['versao_fonte' => null]],
            [ConsultaRendaIbge::CODIGO_INDICADOR, ['valor' => -1]],
            [ConsultaRendaIbge::CODIGO_INDICADOR, ['periodo_referencia' => '2025']],
            [ConsultaRendaIbge::CODIGO_INDICADOR, ['unidade' => 'Salários mínimos']],
            [ConsultaRendaIbge::CODIGO_INDICADOR, ['versao_fonte' => 'Outra variável']],
        ];
    }

    public function test_cnpj_ausente_nao_usa_coleta_antiga_ou_zero(): void
    {
        ContagemEstabelecimento::where('municipio_id', 1)->update(['consultado_em' => '2026-10-28 20:01:33']);
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3306305&cnae=5611201')->assertOk()
            ->assertJsonPath('score', null)->assertJsonPath('diagnostico.conjunto.municipios_aptos', 91);
    }

    public function test_nao_mistura_fontes_ou_metadados_diferentes_da_fotografia(): void
    {
        $outra = FonteDado::create(['codigo' => 'outra', 'nome' => 'Outra', 'url_referencia' => 'https://example.org/']);
        IndicadorMunicipal::where('municipio_id', 1)->where('codigo_indicador', ConsultaRendaIbge::CODIGO_INDICADOR)->update(['fonte_dado_id' => $outra->id]);
        ContagemEstabelecimento::where('municipio_id', 2)->update(['periodo_referencia' => '2025']);
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3306305&cnae=5611201')->assertOk()
            ->assertJsonPath('score', null)->assertJsonPath('diagnostico.conjunto.municipios_aptos', 90);
    }

    public function test_nova_coleta_exibida_nao_recebe_score_da_fotografia_antiga(): void
    {
        $atual = ContagemEstabelecimento::where('municipio_id', 92)->firstOrFail();
        $nova = $atual->replicate();
        $nova->consultado_em = '2026-11-04 20:00:00';
        $nova->save();
        $resposta = $this->getJson('/consulta-demonstrativa?codigo_ibge=3306305&cnae=5611201')->assertOk()
            ->assertJsonPath('score', null)->assertJsonPath('diagnostico.conjunto.municipios_aptos', 92);
        $this->assertStringContainsString('diferem do recorte', $resposta->json('diagnostico.motivos.0'));
    }

    public function test_fingerprint_muda_com_valor_e_outra_atividade_nao_altera_nota(): void
    {
        $url = '/consulta-demonstrativa?codigo_ibge=3306305&cnae=5611201';
        $antes = $this->getJson($url)->assertOk()->json('diagnostico');
        ContagemEstabelecimento::where('atividade_economica_id', 2)->update(['quantidade' => 999, 'registros_no_recorte' => 999]);
        $this->assertSame($antes, $this->getJson($url)->assertOk()->json('diagnostico'));
        IndicadorMunicipal::where('municipio_id', 1)->where('codigo_indicador', ConsultaRendaIbge::CODIGO_INDICADOR)->update(['valor' => 99999]);
        $depois = $this->getJson($url)->assertOk()->json('diagnostico');
        $this->assertNotSame($antes['conjunto']['sha256_dados'], $depois['conjunto']['sha256_dados']);
        $this->assertLessThan($antes['score'], $depois['score']);
    }

    public function test_atualizacao_do_mesmo_indicador_entre_leituras_nao_mistura_valores_exibidos_e_calculados(): void
    {
        $municipio = Municipio::findOrFail(92);
        $atividade = AtividadeEconomica::findOrFail(1);
        $populacao = IndicadorMunicipal::where('municipio_id', 92)->where('codigo_indicador', 'populacao_residente')->firstOrFail();
        $renda = IndicadorMunicipal::where('municipio_id', 92)->where('codigo_indicador', ConsultaRendaIbge::CODIGO_INDICADOR)->firstOrFail();
        $contagem = ContagemEstabelecimento::where('municipio_id', 92)->where('atividade_economica_id', 1)->firstOrFail();
        IndicadorMunicipal::where('id', $renda->id)->update(['valor' => 99999]);
        $resultado = app(DiagnosticoPiloto::class)->consultar($municipio, $atividade, $populacao, $renda, $contagem);
        $this->assertNull($resultado['score']);
        $this->assertStringContainsString('diferem do recorte', $resultado['motivos'][0]);
    }

    public function test_cnae_externo_e_catalogo_incompleto_nao_recebem_nota(): void
    {
        AtividadeEconomica::create(['codigo_cnae' => '7490104', 'nome' => 'Outra atividade']);
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3306305&cnae=7490104')->assertOk()
            ->assertJsonPath('score', null)->assertJsonPath('diagnostico.estado', 'indisponivel');
        Municipio::where('id', 1)->update(['codigo_ibge' => '3300000']);
        $resposta = $this->getJson('/consulta-demonstrativa?codigo_ibge=3306305&cnae=5611201')->assertOk()->assertJsonPath('score', null);
        $this->assertStringContainsString('92 códigos', $resposta->json('diagnostico.motivos.0'));
    }

    public function test_salvar_preserva_entradas_dos_92_municipios_e_nao_recalcula_ao_reabrir(): void
    {
        $usuario = User::factory()->create();
        $id = $this->actingAs($usuario)->postJson('/diagnosticos', ['titulo' => 'Restaurante', 'codigo_ibge' => '3306305', 'cnae' => '5611201', 'score' => 0])
            ->assertCreated()->json('id');
        $snapshot = DiagnosticoSalvo::findOrFail($id)->snapshot;
        $this->assertEquals(100, $snapshot['score']);
        $this->assertCount(92, $snapshot['diagnostico']['entradas_comparacao']);
        $this->assertArrayHasKey('fonte_dado', $snapshot['diagnostico']['entradas_comparacao'][0]['populacao']);
        IndicadorMunicipal::query()->where('codigo_indicador', ConsultaRendaIbge::CODIGO_INDICADOR)->update(['valor' => 1234]);
        $this->getJson('/diagnosticos/'.$id)->assertOk()->assertJsonPath('snapshot', $snapshot);
        Http::assertNothingSent();
    }

    private function retrato(): string
    {
        return hash('sha256', DB::table('indicadores_municipais')->orderBy('id')->get()->toJson().DB::table('contagens_estabelecimentos')->orderBy('id')->get()->toJson());
    }

    public function test_cenario_populacional_preserva_consulta_e_bloqueia_conjunto_incompleto(): void
    {
        $this->artisan('diagnostico:comparar-populacoes')->expectsOutputToContain('faltam estimativas')->assertFailed();
        $fonte = FonteDado::create(['codigo' => 'ibge-estimativas-populacao', 'nome' => 'IBGE estimativas', 'url_referencia' => 'https://example.org/']);
        foreach (Municipio::all() as $cidade) {
            IndicadorMunicipal::create(['municipio_id' => $cidade->id, 'fonte_dado_id' => $fonte->id, 'codigo_indicador' => 'populacao_estimada',
                'valor' => 12000, 'unidade' => 'Pessoas', 'periodo_referencia' => '2026', 'versao_fonte' => 'Tabela 6579 / variável 9324 / recorte RJ v1', 'coletado_em' => '2026-11-05 18:00:00']);
        }
        $antes = $this->retrato();
        $this->assertSame(0, Artisan::call('diagnostico:comparar-populacoes', ['--json' => true]));
        $saida = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(92, $saida['entradas']);
        $this->assertCount(92, $saida['resultados']);
        $this->assertEquals(0, $saida['resultados'][0]['deslocamento']);
        $this->assertEquals(100, $saida['resultados'][91]['estimativa']['score']);
        $this->assertSame($antes, $this->retrato());
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3306305&cnae=5611201')->assertOk()->assertJsonPath('score', 100)->assertJsonPath('populacao.periodo_referencia', '2022');
        Http::assertNothingSent();
    }

    public function test_comando_de_sensibilidade_le_quatro_conjuntos_sem_alterar_banco_ou_api(): void
    {
        $antes = $this->retrato();
        $url = '/consulta-demonstrativa?codigo_ibge=3306305&cnae=5611201';
        $diagnosticoAntes = $this->getJson($url)->assertOk()->json();
        $this->artisan('diagnostico:analisar-sensibilidade')->expectsOutputToContain('Nenhum dado, peso da aplicação ou análise foi gravado.')->assertSuccessful();
        $this->assertSame($antes, $this->retrato());
        $this->assertSame($diagnosticoAntes, $this->getJson($url)->assertOk()->json());
        Http::assertNothingSent();
    }

    public function test_relatorio_markdown_inclui_92_municipios_em_cada_cnae_e_metadados(): void
    {
        $this->assertSame(0, Artisan::call('diagnostico:analisar-sensibilidade', ['--markdown' => true]));
        $saida = Artisan::output();
        $this->assertSame(4, substr_count($saida, '| Município | IBGE |'));
        $this->assertSame(4, substr_count($saida, '| Município 0 | 3300100 |'));
        $this->assertSame(4, substr_count($saida, '| Município 91 | 3306305 |'));
        foreach (DiagnosticoPiloto::CNAES as $codigo) {
            $this->assertStringContainsString('## '.$codigo.' —', $saida);
        }
        $this->assertStringContainsString(DiagnosticoPiloto::METODO, $saida);
        $this->assertStringContainsString('2026 assumido', $saida);
        $this->assertStringContainsString('Conjunto SHA-256:', $saida);
    }

    public function test_relatorio_nao_publica_resultados_parciais_quando_ultimo_cnae_esta_incompleto(): void
    {
        ContagemEstabelecimento::where('atividade_economica_id', 4)->where('municipio_id', 1)->delete();
        $this->assertSame(1, Artisan::call('diagnostico:analisar-sensibilidade', ['--markdown' => true]));
        $saida = Artisan::output();
        $this->assertStringContainsString('Análise bloqueada para o CNAE 9602501', $saida);
        $this->assertStringContainsString('91 de 92', $saida);
        $this->assertStringNotContainsString('# Sensibilidade dos pesos', $saida);
    }
}
