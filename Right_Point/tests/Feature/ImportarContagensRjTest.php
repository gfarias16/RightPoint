<?php

namespace Tests\Feature;

use App\Models\AtividadeEconomica;
use App\Models\ContagemEstabelecimento;
use App\Models\FonteDado;
use App\Models\Municipio;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class ImportarContagensRjTest extends TestCase
{
    private array $registros = [];

    private array $temporarios = [];

    private string $arquivo;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
        Http::fake();

        foreach (config('municipios_receita') as $chave => $codigoIbge) {
            $codigoReceita = substr($chave, 3);
            $nome = match ($codigoReceita) {
                '5853' => 'Maricá',
                '5875' => 'Paraty',
                default => 'Município fictício '.$codigoReceita,
            };
            Municipio::create(['codigo_ibge' => $codigoIbge, 'nome' => $nome, 'uf' => 'RJ']);
            $this->registros[] = [
                'uf' => 'RJ',
                'codigo_municipio' => $codigoReceita,
                'municipio' => $codigoReceita === '5875' ? 'PARATI' : strtoupper(Str::ascii($nome)),
                'cnae_principal' => '5611201',
                'situacao_cadastral' => 'Ativa',
                'registros_no_recorte' => '1',
                'cnpjs_distintos' => '1',
                'cnpjs_ausentes' => '0',
            ];
        }

        AtividadeEconomica::create(['codigo_cnae' => '5611201', 'nome' => 'Atividade fictícia']);
        $json = json_encode($this->registros, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
        $this->arquivo = $this->arquivoTemporario($json);

        // Só o ambiente isolado associa a fotografia fictícia aos metadados de teste.
        config()->set('contagem_cnpj_rj.sha256_lf', hash('sha256', $json));
        config()->set('contagem_cnpj_rj.consultado_em_utc', '2026-10-28 20:01:33');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporarios as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }

        parent::tearDown();
    }

    public function test_padrao_simula_sem_criar_fonte_ou_contagens(): void
    {
        $antes = file_get_contents($this->arquivo);
        $this->artisan('cnpj:importar-contagens-rj', ['arquivo' => $this->arquivo])
            ->expectsOutput('Municípios preparados: 92')
            ->expectsOutput('Soma das contagens municipais: 92')
            ->expectsOutput('Coleta (UTC): 2026-10-28 20:01:33')
            ->expectsOutput('Simulação concluída. Nenhum dado foi gravado.')
            ->assertSuccessful();
        $this->assertDatabaseCount('fontes_dados', 0);
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
        $this->assertSame($antes, file_get_contents($this->arquivo));
        Http::assertNothingSent();
    }

    public function test_grava_92_coletas_com_ids_locais_metadados_e_referencias_nulas(): void
    {
        $this->gravar()->expectsOutput('Importação concluída: 92 novas coletas; 0 já cadastradas.')->assertSuccessful();
        $this->assertDatabaseCount('fontes_dados', 1);
        $this->assertDatabaseCount('contagens_estabelecimentos', 92);
        $this->assertSame(92, ContagemEstabelecimento::sum('quantidade'));
        $this->assertSame(92, ContagemEstabelecimento::whereNull('periodo_referencia')->whereNull('versao_fonte')->count());
        $paraty = ContagemEstabelecimento::where('municipio_id', Municipio::where('codigo_ibge', '3303807')->firstOrFail()->id)->firstOrFail();
        $this->assertSame('2026-10-28 20:01:33', $paraty->consultado_em->format('Y-m-d H:i:s'));
        $this->assertSame('ativos_cnae_principal_exato_v1', $paraty->criterio);
        $this->assertSame('opencnpj-receita', $paraty->fonteDado->codigo);
        $this->assertSame(AtividadeEconomica::firstOrFail()->id, $paraty->atividade_economica_id);
        $this->assertDatabaseCount('municipios', 92);
        $this->assertDatabaseCount('atividades_economicas', 1);
        Http::assertNothingSent();
    }

    public function test_reimportacao_identica_nao_duplica_nem_altera_timestamps(): void
    {
        $this->gravar()->assertSuccessful();
        $coletasAntes = ContagemEstabelecimento::orderBy('id')->get()->toArray();
        $fonteAntes = FonteDado::firstOrFail()->toArray();
        $this->gravar()->expectsOutput('Lote já cadastrado. Nenhuma alteração realizada.')->assertSuccessful();
        $this->assertSame($coletasAntes, ContagemEstabelecimento::orderBy('id')->get()->toArray());
        $this->assertSame($fonteAntes, FonteDado::firstOrFail()->toArray());
        $this->assertDatabaseCount('contagens_estabelecimentos', 92);
    }

    public function test_preserva_piloto_anterior_e_consulta_http_seleciona_coleta_nova(): void
    {
        $piloto = $this->coletaExistente('3302700', '2026-10-28 15:08:25', 620);
        $antes = $piloto->fresh()->toArray();
        $this->gravar()->assertSuccessful();
        $this->assertSame($antes, $piloto->fresh()->toArray());
        $this->assertDatabaseCount('contagens_estabelecimentos', 93);

        $this->getJson('/consulta-demonstrativa?codigo_ibge=3302700&cnae=5611201')
            ->assertOk()
            ->assertJsonPath('concorrentes.quantidade', 1)
            ->assertJsonPath('concorrentes.consultado_em', '2026-10-28T20:01:33.000000Z')
            ->assertJsonPath('score', null);
    }

    public function test_divergencia_na_ultima_coleta_aborta_lote_sem_sobrescrever(): void
    {
        $codigoIbge = array_values(config('municipios_receita'))[91];
        $coleta = $this->coletaExistente($codigoIbge, '2026-10-28 20:01:33', 99);
        $antes = $coleta->fresh()->toArray();
        $this->gravar()
            ->expectsOutput('Coleta divergente para IBGE '.$codigoIbge.'. Nenhum valor foi sobrescrito; transação desfeita.')
            ->assertFailed();
        $this->assertDatabaseCount('contagens_estabelecimentos', 1);
        $this->assertSame($antes, $coleta->fresh()->toArray());
    }

    public function test_falha_no_meio_desfaz_insercoes_e_fonte_criada_na_transacao(): void
    {
        $insercoes = 0;
        DB::connection()->beforeExecuting(function ($sql) use (&$insercoes) {
            if (str_starts_with($sql, 'insert into "contagens_estabelecimentos"') && ++$insercoes === 47) {
                throw new \RuntimeException('Falha fictícia no meio do lote.');
            }
        });
        $this->gravar()
            ->expectsOutput('Falha ao gravar o lote. A transação foi desfeita; confira migrations e o log local.')
            ->assertFailed();
        $this->assertSame(47, $insercoes);
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    public function test_completa_somente_coletas_ausentes_sem_apagar_metadados_enriquecidos(): void
    {
        $existente = $this->coletaExistente('3302700', '2026-10-28 20:01:33', 1);
        $existente->update(['periodo_referencia' => '2026-09', 'versao_fonte' => 'edicao-ficticia']);
        $antes = $existente->fresh()->toArray();
        $this->gravar()->expectsOutput('Importação concluída: 91 novas coletas; 1 já cadastradas.')->assertSuccessful();
        $this->assertSame($antes, $existente->fresh()->toArray());
        $this->assertDatabaseCount('contagens_estabelecimentos', 92);
    }

    public function test_arquivo_valido_diferente_nao_e_associado_ao_job_configurado(): void
    {
        $this->registros[0]['registros_no_recorte'] = '2';
        $this->registros[0]['cnpjs_distintos'] = '2';
        $outro = $this->arquivoTemporario(json_encode($this->registros, JSON_THROW_ON_ERROR));
        $this->artisan('cnpj:importar-contagens-rj', ['arquivo' => $outro, '--gravar' => true])
            ->expectsOutput('O arquivo difere do lote conferido. Não será associado ao job configurado.')
            ->assertFailed();
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    public function test_lote_incompleto_e_rejeitado_antes_da_gravacao(): void
    {
        array_pop($this->registros);
        $outro = $this->arquivoTemporario(json_encode($this->registros, JSON_THROW_ON_ERROR));
        $this->artisan('cnpj:importar-contagens-rj', ['arquivo' => $outro, '--gravar' => true])
            ->expectsOutput('Esperada uma lista JSON com exatamente 92 registros municipais.')
            ->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    public function test_json_invalido_e_rejeitado(): void
    {
        $this->artisan('cnpj:importar-contagens-rj', ['arquivo' => $this->arquivoTemporario('{'), '--gravar' => true])
            ->expectsOutput('O arquivo não contém um JSON válido.')
            ->assertFailed();
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
    }

    public function test_hash_da_configuracao_invalido_falha_sem_gravar(): void
    {
        config()->set('contagem_cnpj_rj.sha256_lf', 'nao-e-hash');
        $this->gravar()->expectsOutput('Hash da coleta não configurado corretamente.')->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    public function test_instante_invalido_falha_sem_usar_horario_atual(): void
    {
        config()->set('contagem_cnpj_rj.consultado_em_utc', '2026-02-30 20:01:33');
        $this->gravar()->expectsOutput('Instante UTC da coleta não configurado corretamente.')->assertFailed();
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
    }

    public function test_crlf_e_aceito_sem_mudar_a_identidade_ou_o_conteudo_do_arquivo(): void
    {
        $crlf = str_replace("\n", "\r\n", file_get_contents($this->arquivo));
        $arquivo = $this->arquivoTemporario($crlf);
        $this->artisan('cnpj:importar-contagens-rj', ['arquivo' => $arquivo, '--gravar' => true])->assertSuccessful();
        $this->assertDatabaseCount('contagens_estabelecimentos', 92);
        $this->assertSame($crlf, file_get_contents($arquivo));
    }

    public function test_simulacao_nao_usa_transacao_nem_cria_fonte(): void
    {
        DB::connection()->beforeExecuting(function ($sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $sql);
        });
        $this->artisan('cnpj:importar-contagens-rj', ['arquivo' => $this->arquivo])->assertSuccessful();
        $this->assertSame(0, DB::connection()->transactionLevel());
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    private function gravar(): PendingCommand
    {
        return $this->artisan('cnpj:importar-contagens-rj', ['arquivo' => $this->arquivo, '--gravar' => true]);
    }

    private function coletaExistente(string $ibge, string $instante, int $quantidade): ContagemEstabelecimento
    {
        $fonte = FonteDado::firstOrCreate(['codigo' => 'opencnpj-receita'], ['nome' => 'Fonte fictícia', 'url_referencia' => 'https://example.org/']);

        return ContagemEstabelecimento::create([
            'municipio_id' => Municipio::where('codigo_ibge', $ibge)->firstOrFail()->id,
            'atividade_economica_id' => AtividadeEconomica::firstOrFail()->id,
            'fonte_dado_id' => $fonte->id,
            'quantidade' => $quantidade,
            'registros_no_recorte' => $quantidade,
            'cnpjs_ausentes' => 0,
            'criterio' => 'ativos_cnae_principal_exato_v1',
            'consultado_em' => $instante,
        ]);
    }

    private function arquivoTemporario(string $conteudo): string
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'rightpoint-importar-');
        $this->assertNotFalse($arquivo);
        $this->temporarios[] = $arquivo;
        file_put_contents($arquivo, $conteudo);

        return $arquivo;
    }
}
