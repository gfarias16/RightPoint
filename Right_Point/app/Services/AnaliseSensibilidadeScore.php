<?php

namespace App\Services;

use InvalidArgumentException;

class AnaliseSensibilidadeScore
{
    public const PESOS_RENDA = ['30_70' => 30, '50_50' => 50, '70_30' => 70];

    public function __construct(private CalculadorScoreRelativo $calculador) {}

    public function analisar(array $linhas): array
    {
        $fatores = [];
        foreach ($linhas as $linha) {
            $codigo = $linha['codigo_ibge'] ?? null;
            if (! is_string($codigo) || $codigo === '' || isset($fatores[$codigo])) {
                throw new InvalidArgumentException('Cada município precisa de código único para a análise de sensibilidade.');
            }
            $fatores[$codigo] = $linha['fatores'];
        }
        if (count($fatores) < 2) {
            throw new InvalidArgumentException('A análise exige pelo menos dois municípios.');
        }

        $cenarios = [];
        $municipios = [];
        foreach (self::PESOS_RENDA as $cenario => $peso) {
            $scores = [];
            foreach ($linhas as $linha) {
                $codigo = $linha['codigo_ibge'];
                $calculo = $this->calculador->calcular($fatores, $codigo, $peso);
                // Remove apenas ruído de ponto flutuante; não classifica pela nota visual de uma casa.
                $scores[$codigo] = round($calculo['score_sem_arredondamento'], 10);
                $municipios[$codigo] ??= ['codigo_ibge' => $codigo, 'nome' => $linha['nome'],
                    'pontos_renda' => $calculo['fatores']['renda']['pontos'],
                    'pontos_menor_oferta' => $calculo['fatores']['menor_oferta']['pontos']];
                $municipios[$codigo]['notas'][$cenario] = $calculo['score'];
            }
            foreach ($scores as $codigo => $valor) {
                $maiores = count(array_filter($scores, fn ($outro) => $outro > $valor));
                $iguais = count(array_filter($scores, fn ($outro) => $outro === $valor));
                $municipios[$codigo]['posicoes'][$cenario] = 1 + $maiores + ($iguais - 1) / 2;
                $municipios[$codigo]['scores_precisos'][$cenario] = $valor;
            }
            $cenarios[$cenario] = ['peso_renda' => $peso, 'peso_menor_oferta' => 100 - $peso];
        }

        foreach ($cenarios as $cenario => &$resumo) {
            $deslocamentos = array_map(fn ($m) => abs($m['posicoes'][$cenario] - $m['posicoes']['50_50']), $municipios);
            $resumo['municipios_com_posicao_alterada'] = count(array_filter($deslocamentos, fn ($v) => $v > 0));
            $resumo['maior_deslocamento_da_base'] = max($deslocamentos);
            $resumo['deslocamento_medio_da_base'] = round(array_sum($deslocamentos) / count($deslocamentos), 2);
        }
        unset($resumo);

        foreach ($municipios as &$linha) {
            $linha['amplitude_pontos'] = round(max($linha['scores_precisos']) - min($linha['scores_precisos']), 1);
            $linha['amplitude_posicoes'] = max($linha['posicoes']) - min($linha['posicoes']);
            unset($linha['scores_precisos']);
        }
        unset($linha);
        $municipios = array_values($municipios);
        usort($municipios, fn ($a, $b) => ($b['amplitude_posicoes'] <=> $a['amplitude_posicoes']) ?: strcmp($a['codigo_ibge'], $b['codigo_ibge']));

        return ['cenarios' => $cenarios, 'municipios' => $municipios];
    }
}
