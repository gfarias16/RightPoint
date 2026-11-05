<?php

namespace Tests\Feature;

use App\Models\ContextoTerritorial;
use App\Models\Municipio;
use App\Services\ConsultaContextoIbge;
use App\Services\GeometriaMunicipal;
use App\Services\ValidadorContextoTerritorial;
use DomainException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContextoTerritorialTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
        foreach (array_values(config('municipios_receita')) as $codigo) {
            Municipio::create(['codigo_ibge' => $codigo, 'nome' => 'Município '.$codigo, 'uf' => 'RJ']);
        }
        Http::preventStrayRequests();
    }

    private function geometria(): array
    {
        return ['type' => 'Polygon', 'coordinates' => [[[-43, -23], [-42, -23], [-42, -22], [-43, -22], [-43, -23]]]];
    }

    private function lote(string $tipo = 'osm'): array
    {
        return ['tipo' => $tipo, 'codigo_ibge' => '3302700', 'fonte_url' => 'https://overpass-api.de/api/interpreter',
            'periodo_referencia' => null, 'versao_fonte' => 'Fixture sintética, não importar em produção', 'coletado_em' => '2026-11-01T12:00:00Z',
            'dados' => ['geometria_municipio' => $this->geometria(), 'pontos' => [
                ['id' => 'node/1', 'latitude' => -22.5, 'longitude' => -42.5, 'tipo' => 'escola', 'nome' => 'Dado descartado'],
            ]]];
    }

    public function test_geometria_respeita_buracos_multipoligonos_e_rejeita_anel_aberto(): void
    {
        $g = app(GeometriaMunicipal::class);
        $poligono = $this->geometria();
        $poligono['coordinates'][] = [[-42.8, -22.8], [-42.2, -22.8], [-42.2, -22.2], [-42.8, -22.2], [-42.8, -22.8]];
        $this->assertFalse($g->contem($g->validar($poligono), -42.5, -22.5));
        $this->assertTrue($g->contem($poligono, -42.9, -22.9));
        $this->assertTrue($g->contem($g->validar(['type' => 'MultiPolygon', 'coordinates' => [$this->geometria()['coordinates']]]), -42.5, -22.5));
        array_pop($poligono['coordinates'][0]);
        $this->expectException(DomainException::class);
        $g->validar($poligono);
    }

    public function test_pontos_sao_normalizados_sem_contatos_e_reimportacao_nao_duplica(): void
    {
        $lote = $this->lote();
        $normalizado = app(ValidadorContextoTerritorial::class)->validar($lote);
        $this->assertArrayNotHasKey('nome', $normalizado['dados']['pontos'][0]);
        $arquivo = tempnam(sys_get_temp_dir(), 'rp-contexto-');
        try {
            file_put_contents($arquivo, json_encode($lote, JSON_THROW_ON_ERROR));
            $this->artisan('contexto:importar', ['arquivo' => $arquivo])->assertExitCode(0);
            $this->assertDatabaseCount('contextos_territoriais', 0);
            $this->artisan('contexto:importar', ['arquivo' => $arquivo, '--gravar' => true])->assertExitCode(0);
            $this->artisan('contexto:importar', ['arquivo' => $arquivo, '--gravar' => true])->assertExitCode(0);
            $this->assertDatabaseCount('contextos_territoriais', 1);
        } finally {
            unlink($arquivo);
        }
        Http::assertNothingSent();
    }

    public static function lotesInvalidos(): array
    {
        return array_map(fn ($campo) => [$campo], ['host', 'data', 'municipio', 'fora', 'duplicado', 'tipo', 'geocodificacao', 'setor', 'unidade', 'negativo', 'suprimido']);
    }

    #[DataProvider('lotesInvalidos')]
    public function test_lote_inconsistente_e_rejeitado(string $caso): void
    {
        $lote = $this->lote();
        switch ($caso) {
            case 'host': $lote['fonte_url'] = 'https://overpass-api.de.exemplo.org/';
                break;
            case 'data': $lote['coletado_em'] = '2026-02-30T12:00:00Z';
                break;
            case 'municipio': $lote['codigo_ibge'] = '3500000';
                break;
            case 'fora': $lote['dados']['pontos'][0]['longitude'] = -40;
                break;
            case 'duplicado': $lote['dados']['pontos'][] = $lote['dados']['pontos'][0];
                break;
            case 'tipo': $lote['dados']['pontos'][0]['tipo'] = 'estabelecimento';
                break;
            case 'geocodificacao':
                $lote['tipo'] = 'estabelecimentos';
                $lote['fonte_url'] = 'https://opencnpj.org/';
                $lote['dados']['pontos'][0] += ['cnae' => '5611201'];
                break;
            case 'setor':
            case 'suprimido':
                $lote['tipo'] = 'setores-ibge';
                $lote['fonte_url'] = 'https://www.ibge.gov.br/';
                $lote['periodo_referencia'] = '2022';
                $lote['dados'] = ['features' => [['type' => 'Feature', 'properties' => ['codigo_setor' => $caso === 'setor' ? '330000000000001' : '330270000000001', 'populacao' => 'X'], 'geometry' => $this->geometria()]]];
                break;
            default:
                $lote['tipo'] = 'trabalho-caged';
                $lote['fonte_url'] = 'https://www.gov.br/trabalho-e-emprego/';
                $lote['periodo_referencia'] = '2026-09';
                $lote['dados'] = ['indicadores' => [
                    ['codigo' => 'admissoes', 'valor' => $caso === 'negativo' ? -1 : 10, 'unidade' => $caso === 'unidade' ? 'Pessoas' : 'Vínculos'],
                    ['codigo' => 'desligamentos', 'valor' => 5, 'unidade' => 'Vínculos']]];
        }
        $this->expectException(DomainException::class);
        app(ValidadorContextoTerritorial::class)->validar($lote);
    }

    public function test_setor_suprimido_permanece_null_e_camadas_respeitam_data_de_coleta(): void
    {
        $lote = $this->lote();
        $lote['tipo'] = 'setores-ibge';
        $lote['fonte_url'] = 'https://www.ibge.gov.br/';
        $lote['periodo_referencia'] = '2022';
        $lote['dados'] = ['features' => [['type' => 'Feature', 'properties' => ['codigo_setor' => '330270000000001', 'populacao' => null], 'geometry' => $this->geometria()]]];
        $normalizado = app(ValidadorContextoTerritorial::class)->validar($lote);
        $this->assertNull($normalizado['dados']['features'][0]['properties']['populacao']);
        ContextoTerritorial::create($normalizado + ['sha256' => str_repeat('a', 64)]);
        ContextoTerritorial::create(array_replace($normalizado, ['coletado_em' => '2026-10-01 12:00:00', 'sha256' => str_repeat('b', 64)]));
        $this->getJson('/contextos/3302700')->assertOk()->assertJsonCount(1, 'camadas')->assertJsonPath('camadas.0.sha256', str_repeat('a', 64));
        $this->getJson('/contextos/3300000')->assertNotFound();
    }

    private function respostaIbge(bool $idades): array
    {
        $ids = $idades ? array_merge([100362], ...array_values(ConsultaContextoIbge::FAIXAS)) : ['total'];
        $resultados = [];
        foreach ($ids as $id) {
            $series = [];
            foreach (array_values(config('municipios_receita')) as $codigo) {
                $series[] = ['localidade' => ['id' => $codigo, 'nivel' => ['id' => 'N6']],
                    'serie' => [$idades ? '2022' : '2026' => $idades ? ($id === 100362 ? '20' : ($id === 6653 ? '-' : '1')) : '100']];
            }
            $resultados[] = ['classificacoes' => $idades ? [
                ['id' => '2', 'categoria' => [6794 => 'Total']], ['id' => '286', 'categoria' => [113635 => 'Total']], ['id' => '287', 'categoria' => [$id => 'Faixa']],
            ] : [], 'series' => $series];
        }

        return [['id' => $idades ? '93' : '9324', 'unidade' => 'Pessoas', 'resultados' => $resultados]];
    }

    public function test_estimates_e_idades_validam_92_municipios_sem_confundir_zero_com_ausencia(): void
    {
        $consulta = app(ConsultaContextoIbge::class);
        $municipios = Municipio::pluck('id', 'codigo_ibge')->all();
        $estimativas = $consulta->validar($this->respostaIbge(false), 'estimativa', '2026', $municipios);
        $idades = $consulta->validar($this->respostaIbge(true), 'idades', '2022', $municipios);
        $this->assertCount(92, $estimativas);
        $this->assertCount(276, $idades);
        $this->assertSame(7, $idades[2]['valor']);
        $dados = $this->respostaIbge(true);
        $dados[0]['resultados'][21]['series'][0]['serie']['2022'] = '...';
        $this->expectException(DomainException::class);
        $consulta->validar($dados, 'idades', '2022', $municipios);
    }

    public function test_total_etario_inconsistente_bloqueia_todo_lote(): void
    {
        $dados = $this->respostaIbge(true);
        $dados[0]['resultados'][0]['series'][0]['serie']['2022'] = '21';
        $this->expectException(DomainException::class);
        app(ConsultaContextoIbge::class)->validar($dados, 'idades', '2022', Municipio::pluck('id', 'codigo_ibge')->all());
    }

    public function test_importacao_ibge_simula_grava_e_reimporta_sem_duplicar(): void
    {
        Http::fake([
            '*6579/metadados' => Http::response(['id' => 6579, 'variaveis' => [['id' => 9324, 'unidade' => 'Pessoas']], 'nivelTerritorial' => ['Administrativo' => ['N6']], 'periodicidade' => ['inicio' => 2001, 'fim' => 2026]]),
            '*6579/periodos/2026/variaveis/9324*' => Http::response($this->respostaIbge(false)),
        ]);
        $this->artisan('ibge:importar-contexto-rj', ['tipo' => 'estimativa'])->assertExitCode(0);
        $this->assertDatabaseCount('indicadores_municipais', 0);
        $this->artisan('ibge:importar-contexto-rj', ['tipo' => 'estimativa', '--gravar' => true])->assertExitCode(0);
        $this->artisan('ibge:importar-contexto-rj', ['tipo' => 'estimativa', '--gravar' => true])->assertExitCode(0);
        $this->assertDatabaseCount('indicadores_municipais', 92);
        $this->getJson('/contextos/3302700')->assertOk()->assertJsonCount(1, 'indicadores')->assertJsonPath('indicadores.0.codigo', 'populacao_estimada');
    }

    public function test_metadados_ibge_incompativeis_impedem_gravacao(): void
    {
        Http::fake(['*metadados' => Http::response(['id' => 6579, 'variaveis' => [['id' => 9324, 'unidade' => 'Percentual']]])]);
        $this->artisan('ibge:importar-contexto-rj', ['tipo' => 'estimativa', '--gravar' => true])->assertExitCode(1);
        $this->assertDatabaseCount('indicadores_municipais', 0);
        Http::assertSentCount(1);
    }

    public function test_osm_filtra_fora_do_municipio_e_reusa_cache_sem_duplicar(): void
    {
        config(['cache.default' => 'array']);
        Cache::forget('rightpoint:contexto-osm:v1:3302700');
        Http::fake([
            '*malhas/municipios/3302700*' => Http::response(['features' => [['properties' => ['codarea' => '3302700'], 'geometry' => $this->geometria()]]]),
            'https://overpass-api.de/api/interpreter' => Http::response(['elements' => [
                ['type' => 'node', 'id' => 1, 'lat' => -22.5, 'lon' => -42.5, 'tags' => ['amenity' => 'school']],
                ['type' => 'way', 'id' => 2, 'center' => ['lat' => -22.6, 'lon' => -42.6], 'tags' => ['amenity' => 'hospital']],
                ['type' => 'node', 'id' => 3, 'lat' => -22.5, 'lon' => -40, 'tags' => ['amenity' => 'school']],
            ]]),
        ]);
        $this->artisan('osm:importar-contexto', ['codigo_ibge' => '3302700'])->assertExitCode(0);
        $this->assertDatabaseCount('contextos_territoriais', 0);
        $this->artisan('osm:importar-contexto', ['codigo_ibge' => '3302700', '--gravar' => true])->assertExitCode(0);
        $this->artisan('osm:importar-contexto', ['codigo_ibge' => '3302700', '--gravar' => true])->assertExitCode(0);
        $this->assertDatabaseCount('contextos_territoriais', 1);
        $this->assertCount(2, ContextoTerritorial::firstOrFail()->dados['pontos']);
        Http::assertSentCount(2);
    }

    public function test_overpass_incompleto_nao_vira_camada_vazia(): void
    {
        config(['cache.default' => 'array']);
        Cache::forget('rightpoint:contexto-osm:v1:3302700');
        Http::fake([
            '*malhas/municipios/3302700*' => Http::response(['features' => [['properties' => ['codarea' => '3302700'], 'geometry' => $this->geometria()]]]),
            'https://overpass-api.de/api/interpreter' => Http::response(['elements' => [], 'remark' => 'runtime error']),
        ]);
        $this->artisan('osm:importar-contexto', ['codigo_ibge' => '3302700', '--gravar' => true])->assertExitCode(1);
        $this->assertDatabaseCount('contextos_territoriais', 0);
    }
}
