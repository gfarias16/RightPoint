<?php

namespace Tests\Unit;

use App\Services\CalculadorScoreRelativo;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CalculadorScoreRelativoTest extends TestCase
{
    public function test_extremos_pesos_e_ordem_dos_fatores(): void
    {
        $dados = ['a' => ['renda' => 0, 'oferta' => 30], 'b' => ['renda' => 100, 'oferta' => 20], 'c' => ['renda' => 200, 'oferta' => 0]];
        $calc = new CalculadorScoreRelativo;
        $this->assertSame(0.0, $calc->calcular($dados, 'a')['score']);
        $this->assertSame(50.0, $calc->calcular($dados, 'b')['score']);
        $melhor = $calc->calcular($dados, 'c');
        $this->assertSame(100.0, $melhor['score']);
        foreach ($melhor['fatores'] as $fator) {
            $this->assertSame(100.0, $fator['pontos']);
            $this->assertSame(0.5, $fator['peso']);
            $this->assertSame(50.0, $fator['contribuicao']);
        }
    }

    public function test_empates_usam_posicao_media_e_independem_da_ordem(): void
    {
        $dados = [10 => ['renda' => 100, 'oferta' => 1], 20 => ['renda' => 100, 'oferta' => 2], 30 => ['renda' => 200, 'oferta' => 3]];
        $calc = new CalculadorScoreRelativo;
        $resposta = $calc->calcular($dados, 10);
        $this->assertSame(25.0, $resposta['fatores']['renda']['pontos']);
        $this->assertSame(62.5, $resposta['score']);
        $this->assertSame($resposta, $calc->calcular(array_reverse($dados, true), 10));
        $iguais = [1 => ['renda' => 0, 'oferta' => 0], 2 => ['renda' => 0, 'oferta' => 0]];
        $this->assertSame(50.0, $calc->calcular($iguais, 1)['score']);
    }

    public function test_razoes_proximas_nao_empatam_por_arredondamento_visual(): void
    {
        $dados = [1 => ['renda' => 100, 'oferta' => 1.00001], 2 => ['renda' => 100, 'oferta' => 1.00002]];
        $calc = new CalculadorScoreRelativo;
        $this->assertSame(75.0, $calc->calcular($dados, 1)['score']);
        $this->assertSame(25.0, $calc->calcular($dados, 2)['score']);
    }

    public function test_pesos_exploratorios_nao_alteram_padrao_e_contribuicoes_somam_nota(): void
    {
        $dados = [1 => ['renda' => 0, 'oferta' => 0], 2 => ['renda' => 100, 'oferta' => 100]];
        $calc = new CalculadorScoreRelativo;
        foreach ([0, 30, 50, 70, 100] as $peso) {
            $resultado = $calc->calcular($dados, 1, $peso);
            $this->assertSame((float) (100 - $peso), $resultado['score']);
            $this->assertEquals($resultado['score'], array_sum(array_column($resultado['fatores'], 'contribuicao')));
        }
        $this->assertSame($calc->calcular($dados, 1, 50), $calc->calcular($dados, 1));
    }

    public function test_pesos_fora_do_intervalo_sao_rejeitados(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CalculadorScoreRelativo)->calcular([], 1, 101);
    }

    #[DataProvider('invalidos')]
    public function test_rejeita_dados_insuficientes_ou_invalidos(array $dados, int $selecionado): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CalculadorScoreRelativo)->calcular($dados, $selecionado);
    }

    public static function invalidos(): array
    {
        $base = [1 => ['renda' => 10, 'oferta' => 1], 2 => ['renda' => 20, 'oferta' => 2]];
        $casos = ['vazio' => [[], 1], 'um município' => [[1 => $base[1]], 1], 'seleção externa' => [$base, 3]];
        foreach (['renda', 'oferta'] as $fator) {
            foreach ([null, -1, INF, NAN, 'abc'] as $i => $invalido) {
                $dados = $base;
                $dados[2][$fator] = $invalido;
                $casos[$fator.$i] = [$dados, 1];
            }
        }

        return $casos;
    }
}
