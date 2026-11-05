<?php

namespace App\Services;

class CalculadorOfertaPorHabitante
{
    private const BASE_POPULACIONAL = 10000;

    /**
     * A escala facilita a leitura; não define saturação ou potencial comercial.
     * O resultado é exploratório: compatibilidade temporal depende das fontes.
     *
     * @return array{estado: string, valor: ?float, base_populacional: int, motivo: ?string}
     */
    public function calcular(?int $quantidade, int|float|string|null $populacao): array
    {
        $motivo = match (true) {
            $quantidade === null => 'Não há contagem disponível para este município e CNAE.',
            $quantidade < 0 => 'A contagem de estabelecimentos é inválida.',
            $populacao === null => 'A população municipal ainda não está disponível.',
            ! is_numeric($populacao) || ! is_finite((float) $populacao) || (float) $populacao <= 0 => 'A população precisa ser um número finito maior que zero.',
            default => null,
        };

        $valor = null;

        if ($motivo === null) {
            $razao = ($quantidade / (float) $populacao) * self::BASE_POPULACIONAL;

            if (is_finite($razao)) {
                $valor = round($razao, 2);
            } else {
                $motivo = 'Os valores disponíveis não permitem calcular uma razão finita.';
            }
        }

        return [
            'estado' => $valor === null ? 'indisponivel' : 'calculado',
            'valor' => $valor,
            'base_populacional' => self::BASE_POPULACIONAL,
            'motivo' => $motivo,
        ];
    }
}
