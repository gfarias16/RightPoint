<?php

namespace Tests\Feature;

use App\Models\Municipio;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultaDemonstrativaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Estes testes criam tabelas somente em SQLite na memória; o MySQL local não é alterado.
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);

        Municipio::create([
            'codigo_ibge' => '3304557',
            'nome' => 'Rio de Janeiro',
            'uf' => 'RJ',
        ]);
    }

    public function test_consulta_sem_bases_integradas_nao_inventa_score_ou_concorrentes(): void
    {
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=5611201')
            ->assertOk()
            ->assertJsonPath('municipio.codigo_ibge', '3304557')
            ->assertJsonPath('atividade.catalogada', false)
            ->assertJsonPath('populacao', null)
            ->assertJsonPath('concorrentes', null)
            ->assertJsonPath('score', null)
            ->assertJsonCount(4, 'avisos');
    }

    public function test_cnae_fora_do_formato_e_rejeitado(): void
    {
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3304557&cnae=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cnae');
    }

    public function test_municipio_fora_do_catalogo_e_rejeitado(): void
    {
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3300000&cnae=5611201')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo_ibge');
    }
}
