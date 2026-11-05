<?php

namespace App\Services;

use App\Models\ContextoTerritorial;
use App\Models\IndicadorMunicipal;
use App\Models\Municipio;

class ContextoMunicipal
{
    public function consultar(string $codigo, bool $incluirGeometrias = true): array
    {
        $municipio = Municipio::where('codigo_ibge', $codigo)->where('uf', 'RJ')->firstOrFail();
        $indicadores = IndicadorMunicipal::with('fonteDado')->where('municipio_id', $municipio->id)->where('unidade', 'Pessoas')
            ->where(function ($q) {
                $q->where(fn ($e) => $e->where('codigo_indicador', 'populacao_estimada')->where('periodo_referencia', '2026')
                    ->where('versao_fonte', 'Tabela 6579 / variável 9324 / recorte RJ v1')
                    ->whereHas('fonteDado', fn ($f) => $f->where('codigo', 'ibge-estimativas-populacao')))
                    ->orWhere(fn ($e) => $e->whereIn('codigo_indicador', array_keys(ConsultaContextoIbge::FAIXAS))->where('periodo_referencia', '2022')
                        ->where('versao_fonte', 'Tabela 9514 / variável 93 / recorte RJ v1')
                        ->whereHas('fonteDado', fn ($f) => $f->where('codigo', 'ibge-censo-2022-idades')));
            })->get()->map(fn ($i) => [
                'codigo' => $i->codigo_indicador, 'valor' => $i->valor, 'unidade' => $i->unidade,
                'periodo_referencia' => $i->periodo_referencia, 'versao_fonte' => $i->versao_fonte,
                'fonte' => $i->fonteDado->nome, 'url' => $i->fonteDado->url_referencia, 'coletado_em' => $i->coletado_em,
            ])->all();
        // Um arquivo antigo importado depois não deve substituir uma coleta mais recente.
        $camadas = ContextoTerritorial::where('codigo_ibge', $codigo)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('contextos_territoriais as posterior')
                ->whereColumn('posterior.codigo_ibge', 'contextos_territoriais.codigo_ibge')
                ->whereColumn('posterior.tipo', 'contextos_territoriais.tipo')
                ->where(fn ($maisRecente) => $maisRecente->whereColumn('posterior.coletado_em', '>', 'contextos_territoriais.coletado_em')
                    ->orWhere(fn ($empate) => $empate->whereColumn('posterior.coletado_em', 'contextos_territoriais.coletado_em')->whereColumn('posterior.id', '>', 'contextos_territoriais.id'))))
            ->orderBy('tipo')->get()->toArray();
        if (! $incluirGeometrias) {
            $camadas = array_map(function ($camada) {
                $dados = $camada['dados'];
                $camada['dados'] = ['aviso' => $dados['aviso'], 'quantidade_elementos' => count($dados['pontos'] ?? $dados['features'] ?? [])];
                if (isset($dados['indicadores'])) {
                    $camada['dados']['indicadores'] = $dados['indicadores'];
                }

                return $camada;
            }, $camadas);
        }

        return ['indicadores' => $indicadores, 'camadas' => $camadas, 'geometrias_incluidas' => $incluirGeometrias,
            'aviso' => 'Contextos complementares não entram no score 50/50. Referências distintas não medem demanda atual ou garantem cobertura completa.'];
    }
}
