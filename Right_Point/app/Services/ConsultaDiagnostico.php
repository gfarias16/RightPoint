<?php

namespace App\Services;

use App\Models\AtividadeEconomica;
use App\Models\ContagemEstabelecimento;
use App\Models\IndicadorMunicipal;
use App\Models\Municipio;

class ConsultaDiagnostico
{
    public function consultar(string $codigoIbge, string $cnae, bool $preservarEntradas = false): array
    {
        $calculador = app(CalculadorOfertaPorHabitante::class);
        $diagnosticoPiloto = app(DiagnosticoPiloto::class);
        $municipio = Municipio::query()
            ->where('codigo_ibge', $codigoIbge)
            ->firstOrFail();

        $atividade = AtividadeEconomica::query()
            ->where('codigo_cnae', $cnae)
            ->first();

        // O denominador segue o mesmo recorte do score; não converter ou misturar unidades/fontes.
        $populacao = IndicadorMunicipal::query()
            ->with('fonteDado')
            ->where('municipio_id', $municipio->id)
            ->where('codigo_indicador', 'populacao_residente')
            ->where('periodo_referencia', '2022')
            ->where('unidade', 'Pessoas')
            ->where('versao_fonte', 'Censo 2022 - agregado 4714 - variavel 93')
            ->whereHas('fonteDado', fn ($query) => $query->where('codigo', 'ibge-censo-2022-populacao'))
            ->orderByDesc('coletado_em')
            ->first();

        $renda = IndicadorMunicipal::query()
            ->with('fonteDado')
            ->where('municipio_id', $municipio->id)
            ->where('codigo_indicador', ConsultaRendaIbge::CODIGO_INDICADOR)
            ->where('periodo_referencia', '2022')
            ->where('unidade', 'Reais')
            ->whereHas('fonteDado', fn ($query) => $query->where('codigo', ConsultaRendaIbge::CODIGO_FONTE))
            ->orderByDesc('coletado_em')
            ->first();

        // Não mistura fontes ou critérios diferentes na leitura do piloto.
        $contagem = $atividade === null ? null : ContagemEstabelecimento::query()
            ->with('fonteDado')
            ->where('municipio_id', $municipio->id)
            ->where('atividade_economica_id', $atividade->id)
            ->where('criterio', 'ativos_cnae_principal_exato_v1')
            ->whereHas('fonteDado', fn ($query) => $query->where('codigo', 'opencnpj-receita'))
            ->orderByDesc('consultado_em')
            ->orderByDesc('id')
            ->first();

        $avisos = [];

        if ($atividade === null) {
            $avisos[] = 'O CNAE informado ainda não foi conferido em um catálogo carregado no sistema.';
        }

        if ($populacao === null) {
            $avisos[] = 'Não há população compatível com o Censo 2022 do IBGE, em pessoas e com o recorte esperado, para este município.';
        }

        if ($contagem === null) {
            $avisos[] = 'Não há coleta de estabelecimentos disponível para este município e CNAE. Dado indisponível não significa zero concorrentes.';
        } else {
            $avisos[] = 'A contagem inclui apenas CNPJs ativos com CNAE principal exato no município, na base consultada. Não comprova cobertura de todos os negócios ou concorrentes reais.';

            if ($contagem->periodo_referencia === null || $contagem->versao_fonte === null) {
                $avisos[] = 'Período de referência ou versão da base de estabelecimentos não confirmados. A data da consulta não substitui essas informações.';
            }

            if ($contagem->quantidade === 0) {
                $avisos[] = 'Uma coleta com zero registros não comprova ausência real de concorrentes sem validação independente de cobertura.';
            }

            if ($populacao !== null) {
                $avisos[] = 'As referências de população e estabelecimentos não foram compatibilizadas; estes dados não formam uma fotografia validada do mesmo período.';
            }
        }

        $diagnostico = $diagnosticoPiloto->consultar($municipio, $atividade, $populacao, $renda, $contagem, $preservarEntradas);
        $avisos[] = 'Pesos e interpretação em avaliação. A capacidade de prever resultados comerciais não foi validada.';

        return [
            'municipio' => [
                'codigo_ibge' => $municipio->codigo_ibge,
                'nome' => $municipio->nome,
            ],
            'atividade' => [
                'codigo_cnae' => $cnae,
                'nome' => $atividade?->nome,
                'catalogada' => $atividade !== null,
            ],
            'populacao' => $populacao === null ? null : [
                'valor' => $populacao->valor,
                'periodo_referencia' => $populacao->periodo_referencia,
                'unidade' => $populacao->unidade,
                'fonte' => $populacao->fonteDado?->nome,
                'coletado_em' => $populacao->coletado_em,
                'codigo_fonte' => $populacao->fonteDado?->codigo,
                'url_referencia' => $populacao->fonteDado?->url_referencia,
                'versao_fonte' => $populacao->versao_fonte,
            ],
            'renda' => $renda === null ? null : [
                'valor' => $renda->valor,
                'periodo_referencia' => $renda->periodo_referencia,
                'unidade' => $renda->unidade,
                'fonte' => $renda->fonteDado->nome,
                'codigo_fonte' => $renda->fonteDado->codigo,
                'url_referencia' => $renda->fonteDado->url_referencia,
                'coletado_em' => $renda->coletado_em,
                'versao_fonte' => $renda->versao_fonte,
            ],
            'concorrentes' => $contagem === null ? null : [
                'quantidade' => $contagem->quantidade,
                'criterio' => $contagem->criterio,
                'fonte' => $contagem->fonteDado->nome,
                'codigo_fonte' => $contagem->fonteDado->codigo,
                'url_referencia' => $contagem->fonteDado->url_referencia,
                'consultado_em' => $contagem->consultado_em,
                'periodo_referencia' => $contagem->periodo_referencia,
                'versao_fonte' => $contagem->versao_fonte,
            ],
            'oferta_por_10_mil_habitantes' => $calculador->calcular($contagem?->quantidade, $populacao?->valor),
            'score' => $diagnostico['score'],
            'diagnostico' => $diagnostico,
            'contexto' => app(ContextoMunicipal::class)->consultar($codigoIbge, $preservarEntradas),
            'avisos' => $avisos,
        ];
    }
}
