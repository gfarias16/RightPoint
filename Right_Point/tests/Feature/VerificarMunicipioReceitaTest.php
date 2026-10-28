<?php

namespace Tests\Feature;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class VerificarMunicipioReceitaTest extends TestCase
{
    public function test_relaciona_marica_usando_o_id_do_banco_consultado(): void
    {
        $consulta = $this->consultaMunicipio();
        $consulta->shouldReceive('first')->once()
            ->with(['id', 'nome', 'codigo_ibge', 'uf'])
            ->andReturn((object) [
                'id' => 999,
                'nome' => 'Marica',
                'codigo_ibge' => '3302700',
                'uf' => 'RJ',
            ]);

        $this->artisan('cnpj:verificar-municipio', ['codigo_receita' => '5853'])
            ->expectsOutput('Municipio relacionado: Marica/RJ')
            ->expectsOutput('Codigo IBGE: 3302700')
            ->expectsOutput('ID neste banco: 999')
            ->assertSuccessful();
    }

    public function test_codigo_sem_correspondencia_nao_consulta_o_banco(): void
    {
        DB::shouldReceive('table')->never();

        $this->artisan('cnpj:verificar-municipio', ['codigo_receita' => '0000'])
            ->expectsOutput('Nao existe correspondencia conferida para esse codigo Receita e UF.')
            ->assertFailed();
    }

    public function test_mesmo_codigo_em_outra_uf_nao_reutiliza_o_vinculo(): void
    {
        DB::shouldReceive('table')->never();

        $this->artisan('cnpj:verificar-municipio', ['codigo_receita' => '5853', 'uf' => 'SP'])
            ->expectsOutput('Nao existe correspondencia conferida para esse codigo Receita e UF.')
            ->assertFailed();
    }

    public function test_codigo_ibge_nao_e_aceito_como_codigo_receita(): void
    {
        DB::shouldReceive('table')->never();

        $this->artisan('cnpj:verificar-municipio', ['codigo_receita' => '3302700'])
            ->expectsOutput('Informe o codigo Receita com quatro digitos e a UF com duas letras.')
            ->assertFailed();
    }

    public function test_municipio_ausente_nao_e_criado_automaticamente(): void
    {
        $this->consultaMunicipio()->shouldReceive('first')->once()
            ->with(['id', 'nome', 'codigo_ibge', 'uf'])->andReturnNull();

        $this->artisan('cnpj:verificar-municipio', ['codigo_receita' => '5853'])
            ->expectsOutput('O municipio correspondente ainda nao esta cadastrado neste banco.')
            ->assertFailed();
    }

    public function test_falha_de_conexao_nao_retorna_sucesso(): void
    {
        $this->consultaMunicipio()->shouldReceive('first')->once()
            ->with(['id', 'nome', 'codigo_ibge', 'uf'])
            ->andThrow(new QueryException('mysql', 'select', [], new \RuntimeException('Falha simulada')));

        $this->artisan('cnpj:verificar-municipio', ['codigo_receita' => '5853'])
            ->expectsOutput('Nao foi possivel consultar municipios. Confira a conexao MySQL e as migrations.')
            ->assertFailed();
    }

    private function consultaMunicipio(): Builder
    {
        // Simula somente a leitura: nao abre banco, nao migra e nao apaga dados.
        $consulta = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->once()->with('municipios')->andReturn($consulta);
        $consulta->shouldReceive('where')->once()->with('codigo_ibge', '3302700')->andReturnSelf();
        $consulta->shouldReceive('where')->once()->with('uf', 'RJ')->andReturnSelf();

        return $consulta;
    }
}
