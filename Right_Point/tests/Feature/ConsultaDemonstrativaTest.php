<?php

namespace Tests\Feature;

use App\Models\AtividadeEconomica;
use App\Models\ContagemEstabelecimento;
use App\Models\FonteDado;
use App\Models\IndicadorMunicipal;
use App\Models\Municipio;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultaDemonstrativaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Estes testes criam tabelas somente em SQLite na memória; o MySQL local não é alterado.
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);

        Municipio::create([
            'codigo_ibge' => '3304557',
            'nome' => 'Rio de Janeiro',
            'uf' => 'RJ',
        ]);
    }

    public function test_consulta_sem_bases_integradas_nao_inventa_score_ou_concorrentes(): void
    {
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=5611201')
            ->assertOk()
            ->assertJsonPath('municipio.codigo_ibge', '3304557')
            ->assertJsonPath('atividade.catalogada', false)
            ->assertJsonPath('populacao', null)
            ->assertJsonPath('concorrentes', null)
            ->assertJsonPath('score', null)
            ->assertJsonCount(4, 'avisos');
    }

    public function test_cnae_fora_do_formato_e_rejeitado(): void
    {
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cnae');
    }

    public function test_municipio_fora_do_catalogo_e_rejeitado(): void
    {
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3300000&cnae=5611201')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo_ibge');
    }

    public function test_consulta_exibe_agregado_e_procedencia_sem_inventar_referencia_ou_score(): void
    {
        Http::fake();
        $this->criarContagem();

        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=5611201')
            ->assertOk()
            ->assertJsonPath('concorrentes.quantidade', 12)
            ->assertJsonPath('concorrentes.criterio', 'ativos_cnae_principal_exato_v1')
            ->assertJsonPath('concorrentes.fonte', 'Fonte piloto de teste')
            ->assertJsonPath('concorrentes.codigo_fonte', 'opencnpj-receita')
            ->assertJsonPath('concorrentes.url_referencia', 'https://opencnpj.org/')
            ->assertJsonPath('concorrentes.consultado_em', '2026-10-20T12:00:00.000000Z')
            ->assertJsonPath('concorrentes.periodo_referencia', null)
            ->assertJsonPath('concorrentes.versao_fonte', null)
            ->assertJsonPath('score', null)
            ->assertJsonFragment(['Período de referência ou versão da base de estabelecimentos não confirmados. A data da consulta não substitui essas informações.']);

        Http::assertNothingSent();
        $this->assertDatabaseCount('contagens_estabelecimentos', 1);
    }

    public function test_cnae_sem_coleta_nao_herda_contagem_de_outra_atividade(): void
    {
        $this->criarContagem();
        AtividadeEconomica::create(['codigo_cnae' => '4712100', 'nome' => 'Outra atividade']);

        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=4712100')
            ->assertOk()
            ->assertJsonPath('atividade.catalogada', true)
            ->assertJsonPath('concorrentes', null)
            ->assertJsonPath('score', null)
            ->assertJsonFragment(['Não há coleta de estabelecimentos disponível para este município e CNAE. Dado indisponível não significa zero concorrentes.']);
    }

    public function test_municipio_sem_coleta_nao_herda_contagem_de_outro_municipio(): void
    {
        $this->criarContagem();
        Municipio::create(['codigo_ibge' => '3302700', 'nome' => 'Maricá', 'uf' => 'RJ']);

        $this->getJson('/consulta-demonstrativa?codigo_ibge=3302700&cnae=5611201')
            ->assertOk()
            ->assertJsonPath('concorrentes', null)
            ->assertJsonPath('score', null);
    }

    public function test_cnae_nao_catalogado_continua_sem_contagem(): void
    {
        $this->criarContagem();

        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=9999999')
            ->assertOk()
            ->assertJsonPath('atividade.catalogada', false)
            ->assertJsonPath('concorrentes', null);
    }

    public function test_seleciona_coleta_mais_recente_sem_misturar_fonte_ou_criterio(): void
    {
        $this->criarContagem();
        $this->criarContagem(['consultado_em' => '2026-10-21 12:00:00', 'quantidade' => 15, 'registros_no_recorte' => 15]);
        $this->criarContagem(['consultado_em' => '2026-10-22 12:00:00', 'criterio' => 'outro_criterio', 'quantidade' => 99, 'registros_no_recorte' => 99]);
        $outraFonte = FonteDado::create(['codigo' => 'outra-fonte', 'nome' => 'Outra fonte', 'url_referencia' => 'https://example.org/']);
        $this->criarContagem(['consultado_em' => '2026-10-23 12:00:00', 'fonte_dado_id' => $outraFonte->id, 'quantidade' => 88, 'registros_no_recorte' => 88]);

        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=5611201')
            ->assertOk()
            ->assertJsonPath('concorrentes.quantidade', 15)
            ->assertJsonPath('concorrentes.consultado_em', '2026-10-21T12:00:00.000000Z')
            ->assertJsonPath('score', null);
    }

    public function test_zero_registrado_e_diferente_de_coleta_ausente_sem_prometer_ausencia_real(): void
    {
        $this->criarContagem(['quantidade' => 0, 'registros_no_recorte' => 0]);

        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=5611201')
            ->assertOk()
            ->assertJsonPath('concorrentes.quantidade', 0)
            ->assertJsonPath('score', null)
            ->assertJsonFragment(['Uma coleta com zero registros não comprova ausência real de concorrentes sem validação independente de cobertura.']);
    }

    public function test_referencia_confirmada_e_preservada_sem_usar_data_da_consulta(): void
    {
        $this->criarContagem(['periodo_referencia' => '2026-09', 'versao_fonte' => 'edicao-teste']);

        $resposta = $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=5611201')
            ->assertOk()
            ->assertJsonPath('concorrentes.periodo_referencia', '2026-09')
            ->assertJsonPath('concorrentes.versao_fonte', 'edicao-teste');

        $this->assertNotContains(
            'Período de referência ou versão da base de estabelecimentos não confirmados. A data da consulta não substitui essas informações.',
            $resposta->json('avisos')
        );
    }

    public function test_populacao_e_contagem_juntas_nao_geram_score_e_exibem_limite_temporal(): void
    {
        $contagem = $this->criarContagem();
        $fontePopulacao = FonteDado::create([
            'codigo' => 'populacao-teste',
            'nome' => 'Fonte de população de teste',
            'url_referencia' => 'https://example.org/populacao',
        ]);
        IndicadorMunicipal::create([
            'municipio_id' => $contagem->municipio_id,
            'fonte_dado_id' => $fontePopulacao->id,
            'codigo_indicador' => 'populacao_residente',
            'valor' => 100000,
            'periodo_referencia' => '2022',
            'unidade' => 'Pessoas',
            'coletado_em' => '2026-10-19 12:00:00',
        ]);

        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=5611201')
            ->assertOk()
            ->assertJsonPath('populacao.periodo_referencia', '2022')
            ->assertJsonPath('concorrentes.quantidade', 12)
            ->assertJsonPath('score', null)
            ->assertJsonFragment(['As referências de população e estabelecimentos não foram compatibilizadas; estes dados não formam uma fotografia validada do mesmo período.']);
    }

    private function criarContagem(array $atributos = []): ContagemEstabelecimento
    {
        $atividade = AtividadeEconomica::firstOrCreate(
            ['codigo_cnae' => '5611201'],
            ['nome' => 'Atividade de teste']
        );
        $fonte = FonteDado::firstOrCreate(
            ['codigo' => 'opencnpj-receita'],
            ['nome' => 'Fonte piloto de teste', 'url_referencia' => 'https://opencnpj.org/']
        );

        return ContagemEstabelecimento::create(array_replace([
            'municipio_id' => Municipio::where('codigo_ibge', '3304557')->firstOrFail()->id,
            'atividade_economica_id' => $atividade->id,
            'fonte_dado_id' => $fonte->id,
            'quantidade' => 12,
            'registros_no_recorte' => 12,
            'cnpjs_ausentes' => 0,
            'criterio' => 'ativos_cnae_principal_exato_v1',
            'consultado_em' => '2026-10-20 12:00:00',
            'periodo_referencia' => null,
            'versao_fonte' => null,
        ], $atributos));
    }
}
