<?php

namespace App\Services;

use InvalidArgumentException;

class CalculadorScoreRelativo
{
    /** Os fatores devem chegar sem arredondamento e no mesmo recorte entre municípios. */
    public function calcular(array $municipios, int|string $selecionado, int $pesoRendaPercentual = 50): array
    {
        if ($pesoRendaPercentual < 0 || $pesoRendaPercentual > 100) {
            throw new InvalidArgumentException('O peso percentual da renda precisa estar entre 0 e 100.');
        }
        if (count($municipios) < 2 || ! array_key_exists($selecionado, $municipios)) {
            throw new InvalidArgumentException('São necessários pelo menos dois municípios e uma seleção pertencente ao conjunto.');
        }

        foreach ($municipios as $fatores) {
            foreach (['renda', 'oferta'] as $nome) {
                $valor = $fatores[$nome] ?? null;
                if (! is_numeric($valor) || ! is_finite((float) $valor) || $valor < 0) {
                    throw new InvalidArgumentException('Todos os municípios precisam dos dois fatores finitos e não negativos.');
                }
            }
        }

        $renda = $this->posicaoPercentual(array_column($municipios, 'renda'), (float) $municipios[$selecionado]['renda']);
        $menorOferta = 100 - $this->posicaoPercentual(array_column($municipios, 'oferta'), (float) $municipios[$selecionado]['oferta']);
        $pesoRenda = $pesoRendaPercentual / 100;
        $pesoOferta = (100 - $pesoRendaPercentual) / 100;
        $score = $renda * $pesoRenda + $menorOferta * $pesoOferta;

        return [
            'score' => round($score, 1),
            'score_sem_arredondamento' => $score,
            'fatores' => [
                'renda' => ['pontos' => round($renda, 4), 'peso' => $pesoRenda, 'contribuicao' => round($renda * $pesoRenda, 4)],
                'menor_oferta' => ['pontos' => round($menorOferta, 4), 'peso' => $pesoOferta, 'contribuicao' => round($menorOferta * $pesoOferta, 4)],
            ],
        ];
    }

    private function posicaoPercentual(array $valores, float $selecionado): float
    {
        $menores = 0;
        $iguais = 0;
        foreach ($valores as $valor) {
            $numero = (float) $valor;
            $menores += (int) ($numero < $selecionado);
            $iguais += (int) ($numero === $selecionado);
        }

        // A posição média evita desempatar municípios pelo nome ou pela ordem da consulta.
        return 100 * ($menores + ($iguais - 1) / 2) / (count($valores) - 1);
    }
}
