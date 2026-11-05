<?php

namespace Tests\Feature;

use App\Models\FonteDado;
use App\Models\IndicadorMunicipal;
use App\Models\Municipio;
use App\Services\ConsultaRendaIbge;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportarRendaRjTest extends TestCase
{
    private array $dados;

    private array $metadados;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
        Http::preventStrayRequests();
        $series = [];

        foreach (array_values(config('municipios_receita')) as $ibge) {
            Municipio::create(['codigo_ibge' => $ibge, 'nome' => 'Município fictício '.$ibge, 'uf' => 'RJ']);
            $series[] = ['localidade' => ['id' => $ibge, 'nivel' => ['id' => 'N6']], 'serie' => ['2022' => '1407.80']];
        }

        $this->metadados = [
            'id' => 10295,
            'pesquisa' => 'Censo Demográfico',
            'periodicidade' => ['inicio' => 2022, 'fim' => 2022],
            'nivelTerritorial' => ['Administrativo' => ['N1', 'N3', 'N6']],
            'variaveis' => [['id' => 13431, 'nome' => ConsultaRendaIbge::NOME_VARIAVEL, 'unidade' => 'Reais']],
        ];
        $this->dados = [[
            'id' => '13431',
            'variavel' => ConsultaRendaIbge::NOME_VARIAVEL,
            'unidade' => 'Reais',
            'resultados' => [[
                'classificacoes' => [
                    ['id' => '2', 'categoria' => ['6794' => 'Total']],
                    ['id' => '86', 'categoria' => ['95251' => 'Total']],
                    ['id' => '58', 'categoria' => ['95253' => 'Total']],
                ],
                'series' => $series,
            ]],
        ]];
    }

    private function simularHttp(): void
    {
        // Cada cenário deve usar seus próprios stubs, sem herdar a resposta anterior.
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            ConsultaRendaIbge::URL_BASE.'/metadados' => Http::response($this->metadados),
            ConsultaRendaIbge::URL_BASE.'/periodos/2022/variaveis/13431*' => Http::response($this->dados),
        ]);
    }

    public function test_simulacao_confere_recorte_sem_escrever_ou_usar_zero_para_ausencia(): void
    {
        $this->simularHttp();
        DB::connection()->beforeExecuting(function ($sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $sql);
        });

        $this->artisan('ibge:importar-renda-rj')
            ->expectsOutput('Municípios preparados: 92')
            ->expectsOutput('Simulação concluída. Nenhum dado foi gravado.')
            ->assertSuccessful();

        $this->assertDatabaseCount('fontes_dados', 0);
        $this->assertDatabaseCount('indicadores_municipais', 0);
        Http::assertSent(fn ($r) => str_starts_with($r->url(), ConsultaRendaIbge::URL_BASE.'/periodos/')
            && $r['localidades'] === 'N6[N3[33]]' && $r['classificacao'] === '2[6794]|86[95251]|58[95253]');
        Http::assertSentCount(2);
    }

    public function test_grava_92_rendimentos_com_procedencia_e_reimporta_sem_duplicar(): void
    {
        $this->simularHttp();
        $this->artisan('ibge:importar-renda-rj', ['--gravar' => true])->assertSuccessful();
        $antes = IndicadorMunicipal::orderBy('id')->get()->map(fn ($m) => [$m->id, $m->municipio_id, $m->created_at->toISOString()])->all();
        $this->assertDatabaseCount('indicadores_municipais', 92);
        $fonte = FonteDado::where('codigo', ConsultaRendaIbge::CODIGO_FONTE)->firstOrFail();
        $this->assertSame(ConsultaRendaIbge::urlValores(), $fonte->url_referencia);
        $this->assertSame(92, IndicadorMunicipal::where('codigo_indicador', ConsultaRendaIbge::CODIGO_INDICADOR)
            ->where('periodo_referencia', '2022')->where('unidade', 'Reais')->where('versao_fonte', ConsultaRendaIbge::VERSAO_RECORTE)->count());
        $this->assertSame(92, IndicadorMunicipal::whereNotNull('coletado_em')->count());

        $this->dados[0]['resultados'][0]['series'][0]['serie']['2022'] = '1555.55';
        $this->simularHttp();
        $this->artisan('ibge:importar-renda-rj', ['--gravar' => true])->assertSuccessful();
        $this->assertDatabaseCount('indicadores_municipais', 92);
        $this->assertSame($antes, IndicadorMunicipal::orderBy('id')->get()->map(fn ($m) => [$m->id, $m->municipio_id, $m->created_at->toISOString()])->all());
        $this->assertEquals(1555.55, IndicadorMunicipal::orderBy('id')->firstOrFail()->valor);
    }

    public function test_preserva_populacao_e_zero_de_renda_e_consulta_nao_mistura_outra_fonte(): void
    {
        $this->dados[0]['resultados'][0]['series'][0]['serie']['2022'] = '0.00';
        $this->simularHttp();
        $populacao = FonteDado::create(['codigo' => 'populacao-teste', 'nome' => 'População', 'url_referencia' => 'https://example.org/']);
        $municipio = Municipio::firstOrFail();
        $indicador = IndicadorMunicipal::create(['municipio_id' => $municipio->id, 'fonte_dado_id' => $populacao->id,
            'codigo_indicador' => 'populacao_residente', 'valor' => 10000, 'unidade' => 'Pessoas', 'periodo_referencia' => '2022', 'coletado_em' => now()]);
        $antes = $indicador->fresh()->getAttributes();
        $this->artisan('ibge:importar-renda-rj', ['--gravar' => true])->assertSuccessful();
        $this->assertSame($antes, $indicador->fresh()->getAttributes());
        IndicadorMunicipal::create(['municipio_id' => $municipio->id, 'fonte_dado_id' => $populacao->id,
            'codigo_indicador' => ConsultaRendaIbge::CODIGO_INDICADOR, 'valor' => 9999, 'unidade' => 'Reais', 'periodo_referencia' => '2022', 'coletado_em' => now()->addDay()]);

        $resposta = $this->getJson('/consulta-demonstrativa?codigo_ibge='.$municipio->codigo_ibge.'&cnae=5611201')
            ->assertOk()->assertJsonPath('renda.codigo_fonte', ConsultaRendaIbge::CODIGO_FONTE)
            ->assertJsonPath('renda.periodo_referencia', '2022')->assertJsonPath('renda.unidade', 'Reais')
            ->assertJsonPath('renda.versao_fonte', ConsultaRendaIbge::VERSAO_RECORTE)->assertJsonPath('score', null);
        $this->assertEquals(0, $resposta->json('renda.valor'));
        $this->assertDatabaseCount('indicadores_municipais', 94);
    }

    public function test_sem_carga_renda_continua_null(): void
    {
        $this->getJson('/consulta-demonstrativa?codigo_ibge='.Municipio::firstOrFail()->codigo_ibge.'&cnae=5611201')
            ->assertOk()->assertJsonPath('renda', null)->assertJsonPath('score', null);
    }

    #[DataProvider('respostasInvalidas')]
    public function test_rejeita_todo_lote_antes_de_gravar(string $caso): void
    {
        $serie = &$this->dados[0]['resultados'][0]['series'];
        switch ($caso) {
            case 'vazio': $this->dados = [];
                break;
            case 'unidade': $this->dados[0]['unidade'] = 'Pessoas';
                break;
            case 'mediana': $this->dados[0]['id'] = '13534';
                break;
            case 'nome': $this->dados[0]['variavel'] = 'Salário médio';
                break;
            case 'sexo': $this->dados[0]['resultados'][0]['classificacoes'][0]['categoria'] = ['4' => 'Homens'];
                break;
            case 'duplicata classificacao': $this->dados[0]['resultados'][0]['classificacoes'][1] = $this->dados[0]['resultados'][0]['classificacoes'][0];
                break;
            case 'classificacao ausente': array_pop($this->dados[0]['resultados'][0]['classificacoes']);
                break;
            case 'municipio ausente': array_pop($serie);
                break;
            case 'municipio duplicado': $serie[91] = $serie[0];
                break;
            case 'municipio fora': $serie[0]['localidade']['id'] = '3309999';
                break;
            case 'nivel': $serie[0]['localidade']['nivel']['id'] = 'N3';
                break;
            case 'periodo': $serie[0]['serie'] = ['2023' => '1000.00'];
                break;
            case 'periodo extra': $serie[0]['serie']['2023'] = '1000.00';
                break;
            case 'simbolo': $serie[0]['serie']['2022'] = '..';
                break;
            case 'negativo': $serie[0]['serie']['2022'] = '-10.00';
                break;
            case 'virgula': $serie[0]['serie']['2022'] = '1407,80';
                break;
            case 'numerico': $serie[0]['serie']['2022'] = 1407.80;
                break;
            case 'overflow': $serie[0]['serie']['2022'] = '100000000000000.00';
                break;
            case 'precisao': $serie[0]['serie']['2022'] = '1.00001';
                break;
            case 'catalogo': Municipio::orderBy('id')->firstOrFail()->delete();
                break;
        }
        $this->simularHttp();
        $this->artisan('ibge:importar-renda-rj', ['--gravar' => true])->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
        $this->assertDatabaseCount('indicadores_municipais', 0);
    }

    public static function respostasInvalidas(): array
    {
        return array_map(fn ($caso) => [$caso], ['vazio', 'unidade', 'mediana', 'nome', 'sexo', 'duplicata classificacao', 'classificacao ausente', 'municipio ausente', 'municipio duplicado', 'municipio fora', 'nivel', 'periodo', 'periodo extra', 'simbolo', 'negativo', 'virgula', 'numerico', 'overflow', 'precisao', 'catalogo']);
    }

    #[DataProvider('metadadosInvalidos')]
    public function test_metadados_divergentes_bloqueiam_antes_de_consultar_valores(string $campo, mixed $valor): void
    {
        data_set($this->metadados, $campo, $valor);
        $this->simularHttp();
        $this->artisan('ibge:importar-renda-rj', ['--gravar' => true])->assertFailed();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('indicadores_municipais', 0);
    }

    public static function metadadosInvalidos(): array
    {
        return [
            ['id', 4714],
            ['pesquisa', 'PNAD Contínua'],
            ['periodicidade.inicio', 2023],
            ['nivelTerritorial.Administrativo', ['N1', 'N3']],
            ['variaveis.0.id', 13534],
            ['variaveis.0.unidade', 'Salários mínimos'],
        ];
    }

    public function test_http_falha_e_conexao_falha_nao_gravam(): void
    {
        Http::fake([ConsultaRendaIbge::URL_BASE.'/*' => Http::response([], 503)]);
        $this->artisan('ibge:importar-renda-rj', ['--gravar' => true])->assertFailed();
        Http::swap(new Factory);
        Http::fake(fn () => throw new ConnectionException('Falha fictícia.'));
        $this->artisan('ibge:importar-renda-rj', ['--gravar' => true])
            ->expectsOutput('Não foi possível conectar ao IBGE com TLS verificado. Confira a rede e o pacote CA local.')
            ->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
        $this->assertDatabaseCount('indicadores_municipais', 0);
    }

    public function test_falha_de_persistencia_desfaz_fonte_e_indicadores(): void
    {
        $this->simularHttp();
        DB::connection()->beforeExecuting(function ($sql) {
            if (str_starts_with($sql, 'insert into "indicadores_municipais"')) {
                throw new \RuntimeException('Falha fictícia.');
            }
        });
        $this->artisan('ibge:importar-renda-rj', ['--gravar' => true])->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
        $this->assertDatabaseCount('indicadores_municipais', 0);
    }
}
