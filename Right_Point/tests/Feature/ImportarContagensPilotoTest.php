<?php

namespace Tests\Feature;

use App\Models\AtividadeEconomica;
use App\Models\ContagemEstabelecimento;
use App\Models\FonteDado;
use App\Models\Municipio;
use App\Services\ValidadorContagensRj;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportarContagensPilotoTest extends TestCase
{
    private array $registros = [];

    private array $temporarios = [];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('contagens_piloto.coletas', []);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);

        foreach (config('municipios_receita') as $chave => $ibge) {
            $receita = substr($chave, 3);
            Municipio::create([
                'codigo_ibge' => $ibge,
                'nome' => match ($receita) {
                    '5853' => 'Maricá',
                    '5875' => 'Paraty',
                    default => 'Município fictício '.$receita,
                },
                'uf' => 'RJ',
            ]);
        }

        foreach (ValidadorContagensRj::CNAES_PILOTO as $indice => $cnae) {
            AtividadeEconomica::create(['codigo_cnae' => $cnae, 'nome' => 'Atividade fictícia '.$cnae]);

            foreach (['5853', '5875'] as $receita) {
                $this->registros[] = $this->registro($receita, $cnae, ($indice + 1) * 10);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->temporarios as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }

        parent::tearDown();
    }

    public function test_simula_sem_metadados_sem_escrita_e_sem_usar_rede(): void
    {
        Http::preventStrayRequests();
        $arquivo = $this->arquivo();
        $conteudo = file_get_contents($arquivo);
        DB::connection()->beforeExecuting(function ($sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $sql);
        });

        $this->artisan('cnpj:importar-contagens-piloto', ['arquivo' => $arquivo])
            ->expectsOutput('Grupos preparados: 8')
            ->expectsOutput('Fotografia ainda não conferida em contagens_piloto.coletas. Gravação bloqueada.')
            ->expectsOutput('Simulação concluída. Nenhum dado foi gravado.')
            ->assertSuccessful();

        $this->assertSame($conteudo, file_get_contents($arquivo));
        $this->assertDatabaseCount('fontes_dados', 0);
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
        $this->assertSame(0, DB::connection()->transactionLevel());
    }

    public function test_aceita_os_368_pares_sem_misturar_atividade_e_municipio(): void
    {
        $registros = [];

        foreach (ValidadorContagensRj::CNAES_PILOTO as $cnae) {
            foreach (array_keys(config('municipios_receita')) as $chave) {
                $registros[] = $this->registro(substr($chave, 3), $cnae, 1);
            }
        }

        $lote = app(ValidadorContagensRj::class)->validarPiloto($this->arquivo($registros));
        $this->assertCount(368, $lote['linhas']);
        $this->assertSame(368, $lote['total']);
        $this->assertSame([92, 92, 92, 92], array_column($lote['resumo_cnaes'], 'municipios_com_registro'));
        $this->assertSame([92, 92, 92, 92], array_column($lote['resumo_cnaes'], 'total'));
    }

    public function test_lote_esparso_nao_fabrica_zero_para_outro_municipio_ou_cnae(): void
    {
        $arquivo = $this->arquivo([$this->registro('5853', '1091102', 3)]);
        $lote = app(ValidadorContagensRj::class)->validarPiloto($arquivo);
        $resumo = collect($lote['resumo_cnaes'])->keyBy('codigo_cnae');
        $this->assertNull($resumo['5611201']['total']);
        $this->assertSame(0, $resumo['5611201']['municipios_com_registro']);
        $this->conferir($arquivo);
        $this->gravar($arquivo)->assertSuccessful();
        $this->assertDatabaseCount('contagens_estabelecimentos', 1);
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3303807&cnae=1091102')
            ->assertOk()->assertJsonPath('concorrentes', null);
        $this->getJson('/consulta-demonstrativa?codigo_ibge=3302700&cnae=5611201')
            ->assertOk()->assertJsonPath('concorrentes', null);
    }

    public function test_grava_cnaes_separados_e_controller_le_cada_recorte_sem_score(): void
    {
        $arquivo = $this->arquivo();
        $this->conferir($arquivo);
        $this->gravar($arquivo)->expectsOutput('Importação concluída: 8 novas coletas; 0 já cadastradas.')->assertSuccessful();

        foreach (['5611201' => 10, '1091102' => 20, '4721102' => 30, '9602501' => 40] as $cnae => $quantidade) {
            $this->getJson('/consulta-demonstrativa?codigo_ibge=3302700&cnae='.$cnae)
                ->assertOk()
                ->assertJsonPath('concorrentes.quantidade', $quantidade)
                ->assertJsonPath('concorrentes.codigo_fonte', 'opencnpj-receita')
                ->assertJsonPath('concorrentes.consultado_em', '2026-11-03T15:00:00.000000Z')
                ->assertJsonPath('concorrentes.periodo_referencia', null)
                ->assertJsonPath('score', null);
        }

        $this->assertDatabaseCount('contagens_estabelecimentos', 8);
        $this->assertSame(8, ContagemEstabelecimento::whereNull('periodo_referencia')->whereNull('versao_fonte')->count());
    }

    public function test_preserva_coleta_anterior_e_reimportacao_nao_altera_registros(): void
    {
        $piloto = $this->coleta('3302700', '5611201', '2026-10-28 15:08:25', 620);
        $pilotoAntes = $piloto->fresh()->toArray();
        $arquivo = $this->arquivo();
        $this->conferir($arquivo);
        $this->gravar($arquivo)->assertSuccessful();
        $antes = ContagemEstabelecimento::orderBy('id')->get()->toArray();
        $fonte = FonteDado::firstOrFail()->toArray();
        $this->gravar($arquivo)->expectsOutput('Lote já cadastrado. Nenhuma alteração realizada.')->assertSuccessful();
        $this->assertSame($antes, ContagemEstabelecimento::orderBy('id')->get()->toArray());
        $this->assertSame($fonte, FonteDado::firstOrFail()->toArray());
        $this->assertSame($pilotoAntes, $piloto->fresh()->toArray());
        $this->assertDatabaseCount('contagens_estabelecimentos', 9);
    }

    public function test_divergencia_no_ultimo_par_aborta_todo_o_lote(): void
    {
        $existente = $this->coleta('3303807', '9602501', '2026-11-03 15:00:00', 99);
        $antes = $existente->fresh()->toArray();
        $arquivo = $this->arquivo();
        $this->conferir($arquivo);
        $this->gravar($arquivo)->assertFailed();
        $this->assertSame($antes, $existente->fresh()->toArray());
        $this->assertDatabaseCount('contagens_estabelecimentos', 1);
    }

    public function test_falha_na_quarta_insercao_desfaz_todas_as_coletas_e_fonte(): void
    {
        $arquivo = $this->arquivo();
        $this->conferir($arquivo);
        $insercoes = 0;
        DB::connection()->beforeExecuting(function ($sql) use (&$insercoes) {
            if (str_starts_with($sql, 'insert into "contagens_estabelecimentos"') && ++$insercoes === 4) {
                throw new \RuntimeException('Falha fictícia.');
            }
        });
        $this->gravar($arquivo)->assertFailed();
        $this->assertSame(4, $insercoes);
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    public function test_completa_lote_sem_alterar_metadados_enriquecidos_ou_contar_par_fora_do_arquivo(): void
    {
        $coleta = $this->coleta('3302700', '1091102', '2026-11-03 15:00:00', 20);
        $coleta->update(['periodo_referencia' => '2026-09', 'versao_fonte' => 'edicao-ficticia']);
        $antes = $coleta->fresh()->toArray();
        // Município e CNAE estão no recorte, mas este par específico não vem no arquivo esparso.
        $outra = $this->coleta('3303807', '1091102', '2026-11-03 15:00:00', 888);
        $arquivo = $this->arquivo([$this->registro('5853', '1091102', 20), $this->registro('5875', '4721102', 30)]);
        $this->conferir($arquivo);
        $this->gravar($arquivo)->expectsOutput('Importação concluída: 1 novas coletas; 1 já cadastradas.')->assertSuccessful();
        $this->assertSame($antes, $coleta->fresh()->toArray());
        $this->assertSame(888, $outra->fresh()->quantidade);
        $this->assertDatabaseCount('contagens_estabelecimentos', 3);
    }

    public function test_gravacao_sem_hash_conferido_falha_sem_criar_fonte(): void
    {
        $this->gravar($this->arquivo())->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
    }

    public function test_modificacao_do_arquivo_nao_herda_metadados_do_hash_antigo(): void
    {
        $arquivo = $this->arquivo();
        $this->conferir($arquivo);
        $this->registros[0]['registros_no_recorte'] = '11';
        $this->registros[0]['cnpjs_distintos'] = '11';
        $this->gravar($this->arquivo())->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    #[DataProvider('metadadosInvalidos')]
    public function test_rejeita_metadados_invalidos_antes_de_gravar(array $dados): void
    {
        $arquivo = $this->arquivo();
        $this->conferir($arquivo, $dados);
        $this->gravar($arquivo)->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    public static function metadadosInvalidos(): array
    {
        $padrao = ['consultado_em_utc' => '2026-11-03 15:00:00', 'periodo_referencia' => null, 'versao_fonte' => null];

        return [
            'ausente' => [['consultado_em_utc' => '2026-11-03 15:00:00']],
            'campo extra' => [$padrao + ['extra' => true]],
            'data impossível' => [array_replace($padrao, ['consultado_em_utc' => '2026-02-30 15:00:00'])],
            'data não UTC explícita' => [array_replace($padrao, ['consultado_em_utc' => '2026-11-03T15:00:00-03:00'])],
            'data com nulo' => [array_replace($padrao, ['consultado_em_utc' => "2026-11-03 15:00:00\0"])],
            'período vazio' => [array_replace($padrao, ['periodo_referencia' => ''])],
            'período longo' => [array_replace($padrao, ['periodo_referencia' => str_repeat('x', 21)])],
            'versão longa' => [array_replace($padrao, ['versao_fonte' => str_repeat('x', 101)])],
            'versão não textual' => [array_replace($padrao, ['versao_fonte' => 2026])],
            'controle' => [array_replace($padrao, ['versao_fonte' => "edicao\nficticia"])],
        ];
    }

    public function test_salva_referencias_somente_quando_explicitamente_conferidas(): void
    {
        $arquivo = $this->arquivo();
        $this->conferir($arquivo, ['consultado_em_utc' => '2026-11-03 15:00:00', 'periodo_referencia' => '2026-09', 'versao_fonte' => 'ficticia']);
        $this->gravar($arquivo)->assertSuccessful();
        $this->assertSame(8, ContagemEstabelecimento::where('periodo_referencia', '2026-09')->where('versao_fonte', 'ficticia')->count());
    }

    public function test_crlf_mantem_hash_conferido_sem_reescrever_arquivo(): void
    {
        $arquivo = $this->arquivo();
        $this->conferir($arquivo);
        $crlf = str_replace("\n", "\r\n", file_get_contents($arquivo));
        file_put_contents($arquivo, $crlf);
        $this->gravar($arquivo)->assertSuccessful();
        $this->assertSame($crlf, file_get_contents($arquivo));
    }

    public function test_aceita_zero_explicito_sem_criar_zeros_para_pares_ausentes(): void
    {
        $arquivo = $this->arquivo([$this->registro('5853', '1091102', 0)]);
        $this->conferir($arquivo);
        $this->gravar($arquivo)->assertSuccessful();
        $this->assertSame(0, ContagemEstabelecimento::firstOrFail()->quantidade);
        $this->assertDatabaseCount('contagens_estabelecimentos', 1);
    }

    #[DataProvider('registrosInvalidos')]
    public function test_rejeita_contrato_filtros_controles_e_pares_invalidos(string $caso): void
    {
        $registros = $this->registros;

        switch ($caso) {
            case 'duplicata': $registros[] = $registros[0];
                break;
            case 'nome': $registros[0]['municipio'] = 'Outro município';
                break;
            case 'código': $registros[0]['codigo_municipio'] = '9999';
                break;
            case 'cnae': $registros[0]['cnae_principal'] = '7490104';
                break;
            case 'uf': $registros[0]['uf'] = 'SP';
                break;
            case 'inativa': $registros[0]['situacao_cadastral'] = 'Baixada';
                break;
            case 'numérica': $registros[0]['cnpjs_distintos'] = 10;
                break;
            case 'ausentes': $registros[0]['cnpjs_ausentes'] = '1';
                break;
            case 'divergente': $registros[0]['registros_no_recorte'] = '11';
                break;
            case 'overflow': $registros[0]['cnpjs_distintos'] = '4294967296';
                break;
            case 'extra': $registros[0]['extra'] = 'não permitido';
                break;
            case 'lista vazia': $registros = [];
                break;
            case 'limite': $registros = array_fill(0, 369, $registros[0]);
                break;
        }

        $arquivo = $this->arquivo($registros);
        $this->conferir($arquivo);
        $this->gravar($arquivo)->assertFailed();
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    public static function registrosInvalidos(): array
    {
        return array_map(fn ($caso) => [$caso], ['duplicata', 'nome', 'código', 'cnae', 'uf', 'inativa', 'numérica', 'ausentes', 'divergente', 'overflow', 'extra', 'lista vazia', 'limite']);
    }

    public function test_json_invalido_e_arquivo_ausente_retornam_falha(): void
    {
        $arquivo = $this->arquivo();
        file_put_contents($arquivo, '{');
        $this->gravar($arquivo)->assertFailed();
        $this->gravar(__DIR__.'/ausente.json')->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    public function test_catalogo_ausente_nao_e_criado_pelo_importador(): void
    {
        AtividadeEconomica::where('codigo_cnae', '9602501')->delete();
        $this->gravar($this->arquivo())->expectsOutput('CNAE 9602501 não cadastrado neste banco.')->assertFailed();
        $this->assertDatabaseCount('atividades_economicas', 3);
        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
    }

    public function test_registro_de_fotografias_invalido_nao_autoriza_gravacao(): void
    {
        config()->set('contagens_piloto.coletas', 'inválido');
        $this->gravar($this->arquivo())->assertFailed();
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    private function registro(string $receita, string $cnae, int $quantidade): array
    {
        $municipio = Municipio::where('codigo_ibge', config('municipios_receita.RJ:'.$receita))->firstOrFail();

        return [
            'uf' => 'RJ',
            'codigo_municipio' => $receita,
            'municipio' => $receita === '5875' ? 'PARATI' : strtoupper(Str::ascii($municipio->nome)),
            'cnae_principal' => $cnae,
            'situacao_cadastral' => 'Ativa',
            'registros_no_recorte' => (string) $quantidade,
            'cnpjs_distintos' => (string) $quantidade,
            'cnpjs_ausentes' => '0',
        ];
    }

    private function arquivo(?array $registros = null): string
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'rightpoint-piloto-');
        $this->assertNotFalse($arquivo);
        $this->temporarios[] = $arquivo;
        file_put_contents($arquivo, json_encode($registros ?? $this->registros, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");

        return $arquivo;
    }

    private function conferir(string $arquivo, ?array $metadados = null): void
    {
        $hash = hash('sha256', str_replace(["\r\n", "\r"], "\n", file_get_contents($arquivo)));
        config()->set('contagens_piloto.coletas.'.$hash, $metadados ?? [
            'consultado_em_utc' => '2026-11-03 15:00:00',
            'periodo_referencia' => null,
            'versao_fonte' => null,
        ]);
    }

    private function gravar(string $arquivo): PendingCommand
    {
        return $this->artisan('cnpj:importar-contagens-piloto', ['arquivo' => $arquivo, '--gravar' => true]);
    }

    private function coleta(string $ibge, string $cnae, string $instante, int $quantidade): ContagemEstabelecimento
    {
        $fonte = FonteDado::firstOrCreate(['codigo' => 'opencnpj-receita'], ['nome' => 'Fonte fictícia', 'url_referencia' => 'https://example.org/']);

        return ContagemEstabelecimento::create([
            'municipio_id' => Municipio::where('codigo_ibge', $ibge)->firstOrFail()->id,
            'atividade_economica_id' => AtividadeEconomica::where('codigo_cnae', $cnae)->firstOrFail()->id,
            'fonte_dado_id' => $fonte->id,
            'quantidade' => $quantidade,
            'registros_no_recorte' => $quantidade,
            'cnpjs_ausentes' => 0,
            'criterio' => 'ativos_cnae_principal_exato_v1',
            'consultado_em' => $instante,
        ]);
    }
}
