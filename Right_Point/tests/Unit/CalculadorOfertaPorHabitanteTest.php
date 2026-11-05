<?php

namespace Tests\Unit;

use App\Services\CalculadorOfertaPorHabitante;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CalculadorOfertaPorHabitanteTest extends TestCase
{
    #[DataProvider('valoresValidos')]
    public function test_calcula_escala_e_arredondamento_sem_confundir_zero_com_ausencia(int $quantidade, int|float|string $populacao, float $esperado): void
    {
        $resultado = (new CalculadorOfertaPorHabitante)->calcular($quantidade, $populacao);

        $this->assertSame('calculado', $resultado['estado']);
        $this->assertSame($esperado, $resultado['valor']);
        $this->assertSame(10000, $resultado['base_populacional']);
        $this->assertNull($resultado['motivo']);
    }

    public static function valoresValidos(): array
    {
        return [
            'Barra Mansa restaurantes' => [308, 169894, 18.13],
            'Marica restaurantes e decimal do banco' => [620, '197277.0000', 31.43],
            'zero explicitamente registrado' => [0, 100000, 0.0],
            'uma unidade na escala' => [1, 10000.0, 1.0],
            'proporcao equivalente' => [10, 100000, 1.0],
            'arredondamento' => [1, 30000, 0.33],
        ];
    }

    #[DataProvider('valoresIndisponiveis')]
    public function test_ausencia_e_valores_invalidos_retornam_motivo_sem_numero(?int $quantidade, int|float|string|null $populacao): void
    {
        $resultado = (new CalculadorOfertaPorHabitante)->calcular($quantidade, $populacao);

        $this->assertSame('indisponivel', $resultado['estado']);
        $this->assertNull($resultado['valor']);
        $this->assertNotEmpty($resultado['motivo']);
        $this->assertSame(10000, $resultado['base_populacional']);
    }

    public static function valoresIndisponiveis(): array
    {
        return [
            'contagem ausente' => [null, 100000],
            'populacao ausente' => [12, null],
            'ambos ausentes' => [null, null],
            'populacao zero' => [12, 0],
            'zero textual do banco' => [12, '0.0000'],
            'populacao negativa' => [12, -100],
            'contagem negativa' => [-1, 100000],
            'populacao vazia' => [12, ''],
            'texto invalido' => [12, 'indisponivel'],
            'infinito' => [12, INF],
            'NaN' => [12, NAN],
            'overflow textual' => [12, '1e999'],
            'overflow da divisao' => [12, 1e-308],
        ];
    }
}
