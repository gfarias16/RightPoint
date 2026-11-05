<?php

namespace Tests\Unit;

use App\Services\AnaliseSensibilidadeScore;
use App\Services\CalculadorScoreRelativo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AnaliseSensibilidadeScoreTest extends TestCase
{
    private function linhas(): array
    {
        return [
            ['codigo_ibge' => '3300001', 'nome' => 'A', 'fatores' => ['renda' => 0, 'oferta' => 0]],
            ['codigo_ibge' => '3300002', 'nome' => 'B', 'fatores' => ['renda' => 100, 'oferta' => 1]],
            ['codigo_ibge' => '3300003', 'nome' => 'C', 'fatores' => ['renda' => 200, 'oferta' => 2]],
        ];
    }

    public function test_cenarios_invertem_ordem_sem_desempatar_base_pelo_nome(): void
    {
        $analise = new AnaliseSensibilidadeScore(new CalculadorScoreRelativo);
        $resultado = $analise->analisar($this->linhas());
        $porCodigo = array_column($resultado['municipios'], null, 'codigo_ibge');
        $a = $porCodigo['3300001'];
        $this->assertSame(['30_70' => 70.0, '50_50' => 50.0, '70_30' => 30.0], $a['notas']);
        $this->assertEquals(['30_70' => 1, '50_50' => 2, '70_30' => 3], $a['posicoes']);
        $this->assertEquals(2, $a['amplitude_posicoes']);
        $this->assertSame(40.0, $a['amplitude_pontos']);
        $this->assertSame(2, $resultado['cenarios']['30_70']['municipios_com_posicao_alterada']);
        $this->assertEquals(1, $resultado['cenarios']['30_70']['maior_deslocamento_da_base']);
        $this->assertSame(0.67, $resultado['cenarios']['30_70']['deslocamento_medio_da_base']);
        $this->assertSame(0, $resultado['cenarios']['50_50']['municipios_com_posicao_alterada']);
        $this->assertSame($resultado, $analise->analisar(array_reverse($this->linhas())));
    }

    public function test_fatores_iguais_nao_criam_mudanca_de_nota_ou_posicao(): void
    {
        $linhas = $this->linhas();
        foreach ($linhas as &$linha) {
            $linha['fatores'] = ['renda' => 0, 'oferta' => 0];
        }
        unset($linha);
        $resultado = (new AnaliseSensibilidadeScore(new CalculadorScoreRelativo))->analisar($linhas);
        foreach ($resultado['municipios'] as $m) {
            $this->assertSame(0.0, $m['amplitude_pontos']);
            $this->assertEquals(0, $m['amplitude_posicoes']);
            $this->assertSame(['30_70' => 50.0, '50_50' => 50.0, '70_30' => 50.0], $m['notas']);
        }
    }

    public function test_codigo_repetido_nao_reduz_amostra_silenciosamente(): void
    {
        $linhas = $this->linhas();
        $linhas[2]['codigo_ibge'] = $linhas[0]['codigo_ibge'];
        $this->expectException(InvalidArgumentException::class);
        (new AnaliseSensibilidadeScore(new CalculadorScoreRelativo))->analisar($linhas);
    }

    public function test_fator_invalido_impede_cenarios(): void
    {
        $linhas = $this->linhas();
        $linhas[2]['fatores']['renda'] = null;
        $this->expectException(InvalidArgumentException::class);
        (new AnaliseSensibilidadeScore(new CalculadorScoreRelativo))->analisar($linhas);
    }

    public function test_conjunto_unitario_nao_produz_ranking(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AnaliseSensibilidadeScore(new CalculadorScoreRelativo))->analisar([$this->linhas()[0]]);
    }
}
