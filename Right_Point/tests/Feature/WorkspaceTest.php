<?php

namespace Tests\Feature;

use App\Models\AtividadeEconomica;
use App\Models\DiagnosticoSalvo;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
        Http::preventStrayRequests();
        Municipio::create(['codigo_ibge' => '3302700', 'nome' => 'Maricá', 'uf' => 'RJ']);
        AtividadeEconomica::create(['codigo_cnae' => '5611201', 'nome' => 'Restaurantes']);
    }

    public function test_visitante_consulta_mas_nao_acessa_registros_privados(): void
    {
        $this->get('/')->assertOk();
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3302700&cnae=5611201')->assertOk();
        foreach (['/perfis', '/diagnosticos'] as $url) {
            $this->getJson($url)->assertUnauthorized();
            $this->postJson($url, [])->assertUnauthorized();
        }
        Http::assertNothingSent();
    }

    public function test_cadastro_login_logout_e_senha_protegida(): void
    {
        $this->post('/cadastro', ['name' => 'Pessoa teste', 'email' => 'pessoa@example.org', 'password' => 'Exemplo12345', 'password_confirmation' => 'Exemplo12345'])->assertRedirect('/');
        $usuario = User::firstOrFail();
        $this->assertTrue(Hash::check('Exemplo12345', $usuario->password));
        $this->assertNotSame('Exemplo12345', $usuario->password);
        $this->assertAuthenticatedAs($usuario);
        $this->post('/sair')->assertRedirect('/');
        $this->assertGuest();
        $this->post('/entrar', ['email' => 'pessoa@example.org', 'password' => 'Errada12345'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/entrar', ['email' => 'pessoa@example.org', 'password' => 'Exemplo12345'])->assertRedirect('/');
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_cadastro_rejeita_senha_fraca_e_limitacao_de_tentativas(): void
    {
        $this->postJson('/cadastro', ['name' => 'Teste', 'email' => 'a@example.org', 'password' => 'abc', 'password_confirmation' => 'abc'])->assertUnprocessable();
        $this->assertDatabaseCount('users', 0);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/entrar', ['email' => 'a@example.org', 'password' => 'incorreta'])->assertUnprocessable();
        }
        $this->postJson('/entrar', ['email' => 'a@example.org', 'password' => 'incorreta'])->assertStatus(429);
    }

    public function test_perfis_crud_validacao_e_isolamento_por_usuario(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $dados = ['nome' => 'Meu restaurante', 'codigo_ibge' => '3302700', 'cnae' => '5611201', 'notas' => 'Teste'];
        $id = $this->actingAs($dono)->postJson('/perfis', $dados + ['user_id' => $outro->id])->assertCreated()->json('id');
        $this->assertDatabaseHas('perfis_negocio', ['id' => $id, 'user_id' => $dono->id]);
        $this->getJson('/perfis')->assertJsonPath('total', 1);
        $this->putJson('/perfis/'.$id, array_replace($dados, ['nome' => 'Editado']))->assertOk()->assertJsonPath('nome', 'Editado');
        $this->postJson('/perfis', array_replace($dados, ['cnae' => '0000000']))->assertUnprocessable();
        $this->actingAs($outro)->getJson('/perfis')->assertJsonPath('total', 0);
        $this->putJson('/perfis/'.$id, $dados)->assertNotFound();
        $this->deleteJson('/perfis/'.$id)->assertNotFound();
        $this->actingAs($dono)->deleteJson('/perfis/'.$id)->assertNoContent();
        $this->assertDatabaseCount('perfis_negocio', 0);
    }

    public function test_copia_local_e_idempotente_e_senha_longa_nao_sofre_truncamento(): void
    {
        $senha = str_repeat('á', 40).'123';
        $this->postJson('/cadastro', ['name' => 'Teste', 'email' => 'teste@example.org', 'password' => $senha, 'password_confirmation' => $senha])->assertUnprocessable();
        $this->assertDatabaseCount('users', 0);
        $this->post('/cadastro', ['name' => 'Teste', 'email' => 'TESTE@example.org', 'password' => 'Exemplo12345', 'password_confirmation' => 'Exemplo12345'])->assertRedirect('/');
        $this->assertSame('teste@example.org', User::firstOrFail()->email);
        $dados = ['nome' => 'Local', 'codigo_ibge' => '3302700', 'cnae' => '5611201', 'origem_local' => 'origem-123'];
        $id = $this->postJson('/perfis', $dados)->assertCreated()->json('id');
        $this->putJson('/perfis/'.$id, array_replace($dados, ['nome' => 'Editado na conta']))->assertOk();
        $this->postJson('/perfis', $dados)->assertOk()->assertJsonPath('id', $id)->assertJsonPath('nome', 'Editado na conta');
        $this->assertDatabaseCount('perfis_negocio', 1);
    }

    public function test_snapshot_parcial_calculado_no_servidor_exportacao_e_privacidade(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $id = $this->actingAs($dono)->postJson('/diagnosticos', [
            'titulo' => 'Análise de teste', 'codigo_ibge' => '3302700', 'cnae' => '5611201',
            'score' => 99, 'snapshot' => ['score' => 99], 'user_id' => $outro->id,
        ])->assertCreated()->json('id');
        $antes = DiagnosticoSalvo::findOrFail($id)->snapshot;
        $this->assertNull($antes['score']);
        $this->assertSame(1, $antes['versao_snapshot']);
        Municipio::query()->update(['nome' => 'Outro nome']);
        $this->getJson('/diagnosticos/'.$id)->assertOk()->assertJsonPath('snapshot.municipio.nome', 'Maricá');
        $this->get('/diagnosticos/'.$id.'/relatorio')->assertOk()->assertSee('Análise de teste')->assertSee('indisponível');
        $this->getJson('/diagnosticos/'.$id.'/json')->assertOk()->assertJsonPath('snapshot.score', null)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame($antes, DiagnosticoSalvo::findOrFail($id)->snapshot);
        $this->assertSame(hash('sha256', json_encode($antes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)), DiagnosticoSalvo::findOrFail($id)->sha256);
        $this->actingAs($outro)->getJson('/diagnosticos')->assertJsonPath('total', 0);
        foreach (['', '/relatorio', '/json'] as $sufixo) {
            $this->getJson('/diagnosticos/'.$id.$sufixo)->assertNotFound();
        }
        $this->deleteJson('/diagnosticos/'.$id)->assertNotFound();
        $this->actingAs($dono)->deleteJson('/diagnosticos/'.$id)->assertNoContent();
        Http::assertNothingSent();
    }
}
