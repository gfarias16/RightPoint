<?php

namespace App\Console\Commands;

use App\Models\ContextoTerritorial;
use App\Models\Municipio;
use App\Services\GeometriaMunicipal;
use App\Services\ValidadorContextoTerritorial;
use App\Support\IbgeHttp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportarContextoOsm extends Command
{
    protected $signature = 'osm:importar-contexto {codigo_ibge} {--gravar}';

    protected $description = 'Consulta pontual de escolas, saúde e transporte no OSM; simula por padrão, cache 24h';

    public function handle(GeometriaMunicipal $geo, ValidadorContextoTerritorial $validador): int
    {
        try {
            $codigo = $this->argument('codigo_ibge');
            if (! preg_match('/^33[0-9]{5}$/D', $codigo) || ! Municipio::where('codigo_ibge', $codigo)->where('uf', 'RJ')->exists()) {
                throw new \DomainException('Selecione um código IBGE do catálogo RJ.');
            }
            $lote = Cache::remember('rightpoint:contexto-osm:v1:'.$codigo, now()->addDay(), function () use ($codigo, $geo) {
                $resposta = IbgeHttp::request()->connectTimeout(10)->timeout(30)->get('https://servicodados.ibge.gov.br/api/v3/malhas/municipios/'.$codigo,
                    ['formato' => 'application/vnd.geo+json', 'qualidade' => 'minima']);
                $f = $resposta->json('features.0');
                if (! $resposta->successful() || (string) ($f['properties']['codarea'] ?? '') !== $codigo) {
                    throw new \DomainException('Não foi possível validar o limite municipal do IBGE.');
                }
                $g = $geo->validar($f['geometry'] ?? null);
                $coords = [];
                foreach ($g['type'] === 'Polygon' ? [$g['coordinates']] : $g['coordinates'] as $p) {
                    $coords = array_merge($coords, $p[0]);
                }
                $xs = array_column($coords, 0);
                $ys = array_column($coords, 1);
                $bbox = implode(',', [min($ys), min($xs), max($ys), max($xs)]);
                $query = '[out:json][timeout:25][maxsize:10000000];(nwr["amenity"~"^(school|hospital|clinic)$"]('.$bbox.');nwr["public_transport"~"^(station|platform|stop_position)$"]('.$bbox.'););out center;';
                $r = IbgeHttp::request()->withHeaders(['User-Agent' => 'RightPoint-academico/1.0 (manual-context-import)'])->connectTimeout(10)->timeout(40)->asForm()
                    ->post('https://overpass-api.de/api/interpreter', ['data' => $query]);
                $elements = $r->json('elements');
                if (! $r->successful() || $r->json('remark') || strlen($r->body()) > 5 * 1024 * 1024 || ! is_array($elements) || count($elements) > 3000) {
                    throw new \DomainException('OSM indisponível, consulta incompleta ou recorte acima do limite do piloto.');
                }
                $pontos = [];
                foreach ($elements as $e) {
                    $lat = $e['lat'] ?? $e['center']['lat'] ?? null;
                    $lng = $e['lon'] ?? $e['center']['lon'] ?? null;
                    if (! is_numeric($lat) || ! is_numeric($lng) || ! $geo->contem($g, (float) $lng, (float) $lat)) {
                        continue;
                    }
                    $tipo = match ($e['tags']['amenity'] ?? '') {
                        'school' => 'escola', 'hospital', 'clinic' => 'saude', default => 'transporte'
                    };
                    $pontos[] = ['id' => $e['type'].'/'.$e['id'], 'latitude' => $lat, 'longitude' => $lng, 'tipo' => $tipo];
                }

                return ['tipo' => 'osm', 'codigo_ibge' => $codigo, 'fonte_url' => 'https://overpass-api.de/api/interpreter', 'periodo_referencia' => null,
                    'versao_fonte' => 'OSM escolas/saúde/transporte; ponto ou centro representativo; v1', 'coletado_em' => now('UTC')->format('Y-m-d\TH:i:s\Z'),
                    'dados' => ['geometria_municipio' => $g, 'pontos' => $pontos]];
            });
            $dados = $validador->validar($lote);
            $dados['sha256'] = hash('sha256', json_encode($dados, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $this->info(count($dados['dados']['pontos']).' pontos representativos dentro do limite simplificado.');
            $this->warn('Não mede fluxo, funcionamento ou cobertura completa. Centros de áreas são aproximações; não repetir consultas em paralelo.');
            if ($this->option('gravar')) {
                ContextoTerritorial::firstOrCreate(['sha256' => $dados['sha256']], $dados);
                $this->info('Contexto OSM gravado; score preservado.');
            } else {
                $this->line('Simulação: nenhum dado de domínio gravado. Resposta em cache por 24 horas.');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::warning('Falha na consulta pontual OSM.', ['tipo_erro' => $e::class]);
            $this->error($e instanceof \DomainException ? $e->getMessage() : 'Falha de conexão ou validação do OSM/IBGE. Nenhum lote foi gravado.');

            return self::FAILURE;
        }
    }
}
