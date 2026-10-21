<?php

namespace Tests\Feature;

use App\Models\Municipio;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class VerificarBuscaCnpjTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Banco descartável em memória: nunca usa o MySQL do trabalho/escola.
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);

        Municipio::create([
            'codigo_ibge' => '3302700',
            'nome' => 'Maricá',
            'uf' => 'RJ',
        ]);

        Http::preventStrayRequests();
    }

    public function test_duas_paginas_preservam_filtros_e_cursor_sem_exceder_limite(): void
    {
        Http::fakeSequence()
            ->push(['data' => [$this->registro('TESTE-A')], 'cursor' => 'token:0001'])
            ->push(['data' => [$this->registro('TESTE-B')], 'cursor' => 'token:0002']);

        $this->consulta(['--paginas' => '2'])
            ->expectsOutputToContain('Página 2 de no máximo 2:')
            ->expectsOutputToContain('Limite de páginas atingido')
            ->expectsOutputToContain('não representa o total de concorrentes')
            ->assertSuccessful();

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => ($request['cursor'] ?? null) === 'token:0001'
            && $request['uf'] === 'RJ'
            && (string) $request['municipio'] === '3302700'
            && (string) $request['cnae_fiscal'] === '5611201'
            && (int) $request['limit'] === 2
        );
    }

    public function test_padrao_consulta_somente_uma_pagina(): void
    {
        Http::fakeSequence()->push(['data' => [], 'cursor' => 'continua']);

        $this->consulta()->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_teto_de_tres_paginas_e_respeitado(): void
    {
        Http::fakeSequence()
            ->push(['data' => [], 'cursor' => 'a'])
            ->push(['data' => [], 'cursor' => 'b'])
            ->push(['data' => [], 'cursor' => 'c']);

        $this->consulta(['--paginas' => '3'])
            ->expectsOutputToContain('Página 3 de no máximo 3:')
            ->expectsOutputToContain('Limite de páginas atingido')
            ->assertSuccessful();

        Http::assertSentCount(3);
    }

    public function test_fim_antecipado_nao_dispara_paginas_extras(): void
    {
        Http::fakeSequence()->push(['data' => [], 'cursor' => null]);

        $this->consulta(['--paginas' => '3'])
            ->expectsOutputToContain('A API não indicou outra página.')
            ->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_cnpj_repetido_entre_paginas_interrompe(): void
    {
        Http::fakeSequence()
            ->push(['data' => [$this->registro('TESTE-A')], 'cursor' => 'a'])
            ->push(['data' => [$this->registro('TESTE-A')], 'cursor' => 'b']);

        $this->consulta(['--paginas' => '3'])
            ->expectsOutputToContain('CNPJ repetido nesta amostra.')
            ->assertFailed();

        Http::assertSentCount(2);
    }

    public function test_cursor_inicial_repetido_interrompe(): void
    {
        Http::fakeSequence()->push(['data' => [], 'cursor' => 'inicial']);

        $this->consulta(['--cursor' => 'inicial', '--paginas' => '3'])
            ->expectsOutputToContain('Cursor repetido.')
            ->assertFailed();

        Http::assertSentCount(1);
    }

    public function test_429_na_segunda_pagina_nao_provoca_retentativa(): void
    {
        Http::fakeSequence()
            ->push(['data' => [], 'cursor' => 'a'])
            ->push([], 429);

        $this->consulta(['--paginas' => '3'])
            ->expectsOutputToContain('HTTP 429')
            ->assertFailed();

        Http::assertSentCount(2);
    }

    public function test_quatro_paginas_sao_rejeitadas_antes_da_api(): void
    {
        Http::fake();

        $this->consulta(['--paginas' => '4'])
            ->expectsOutputToContain('informe de 1 a 3 páginas')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_filtros_incompativeis_na_resposta_sao_rejeitados(): void
    {
        Http::fakeSequence()->push([
            'data' => [array_replace($this->registro('TESTE-A'), ['uf' => 'SP'])],
        ]);

        $this->consulta()->expectsOutputToContain('não corresponde aos filtros')->assertFailed();
    }

    public function test_cnae_com_zero_inicial_e_preservado(): void
    {
        Http::fakeSequence()->push([
            'data' => [array_replace($this->registro('TESTE-A'), ['cnae_fiscal' => 111301])],
        ]);

        $this->consulta(['cnae' => '0111301'])->assertSuccessful();
    }

    public function test_formato_de_cursor_invalido_e_rejeitado(): void
    {
        Http::fakeSequence()->push(['data' => [], 'cursor' => []]);

        $this->consulta()->expectsOutputToContain('cursor inesperado')->assertFailed();
    }

    private function consulta(array $opcoes = []): PendingCommand
    {
        return $this->artisan('cnpj:verificar-busca', array_replace([
            'codigo_ibge' => '3302700',
            'cnae' => '5611201',
        ], $opcoes));
    }

    private function registro(string $cnpj): array
    {
        // Identificadores fictícios: este comando ainda não valida formato/DV de CNPJ.
        return [
            'cnpj' => $cnpj,
            'uf' => 'RJ',
            'codigo_municipio_ibge' => 3302700,
            'cnae_fiscal' => 5611201,
            'situacao_cadastral' => 2,
        ];
    }
}
