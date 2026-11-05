<?php

namespace Tests\Feature;

use App\Models\Municipio;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A página consulta o catálogo; preparar somente o SQLite evita depender do banco pessoal.
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
        Http::preventStrayRequests();
    }

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertViewIs('explorar')
            ->assertViewHas('municipios', fn ($municipios) => $municipios->isEmpty());
        Http::assertNothingSent();
    }

    public function test_pagina_inicial_carrega_catalogo_preparado_em_memoria(): void
    {
        Municipio::create(['codigo_ibge' => '3304557', 'nome' => 'Rio de Janeiro', 'uf' => 'RJ']);

        $this->get('/')->assertOk()
            ->assertViewHas('municipios', fn ($municipios) => $municipios->count() === 1
                && $municipios->first()->codigo_ibge === '3304557');
        Http::assertNothingSent();
    }
}
