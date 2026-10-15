<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use App\Support\IbgeHttp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MalhaRjController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            // A API do IBGE fornece a malha simplificada para uso em aplicações web.
            // O cache evita uma chamada externa a cada visita à página.
            $malha = Cache::remember('rightpoint:malha-rj:v1', now()->addDay(), function (): array {
                $resposta = IbgeHttp::request()->timeout(25)->get(
                    'https://servicodados.ibge.gov.br/api/v3/malhas/estados/33',
                    [
                        'intrarregiao' => 'municipio',
                        'formato' => 'application/vnd.geo+json',
                        'qualidade' => 'minima',
                    ]
                );

                $resposta->throw();
                $dados = $resposta->json();

                if (
                    ! is_array($dados)
                    || ($dados['type'] ?? null) !== 'FeatureCollection'
                    || ! is_array($dados['features'] ?? null)
                    || count($dados['features']) !== 92
                ) {
                    throw new RuntimeException('A malha do IBGE não contém os 92 polígonos municipais esperados.');
                }

                $codigosDaMalha = collect($dados['features'])
                    ->map(fn (array $feature) => (string) ($feature['properties']['codarea'] ?? ''))
                    ->sort()
                    ->values()
                    ->all();
                $codigosDoBanco = Municipio::query()
                    ->where('uf', 'RJ')
                    ->pluck('codigo_ibge')
                    ->map(fn ($codigo) => (string) $codigo)
                    ->sort()
                    ->values()
                    ->all();

                // Evita publicar uma malha com 92 polígonos que não correspondam ao catálogo local.
                if ($codigosDaMalha !== $codigosDoBanco) {
                    throw new RuntimeException('Os códigos da malha não correspondem aos municípios locais do RJ.');
                }

                return $dados;
            });

            return response()->json($malha);
        } catch (Throwable $erro) {
            Log::warning('Não foi possível obter a malha municipal do RJ.', [
                'tipo_erro' => $erro::class,
            ]);

            return response()->json([
                'message' => 'O mapa está indisponível no momento. A busca por município continua funcionando.',
            ], 503);
        }
    }
}
