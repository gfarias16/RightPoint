<?php

namespace Tests\Feature;

use App\Models\AtividadeEconomica;
use App\Models\ContagemEstabelecimento;
use App\Models\FonteDado;
use App\Models\Municipio;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VerificarContagensRjTest extends TestCase
{
    private array $registros = [];

    private array $arquivosTemporarios = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
        Http::fake();

        // Nenhum arquivo real, API ou catálogo MySQL é usado pelos testes.
        foreach (config('municipios_receita') as $chave => $codigoIbge) {
            $codigoReceita = substr($chave, 3);
            $nome = match ($codigoReceita) {
                '5853' => 'Maricá',
                '5875' => 'Paraty',
                default => 'Município Teste '.$codigoReceita,
            };

            Municipio::create(['codigo_ibge' => $codigoIbge, 'nome' => $nome, 'uf' => 'RJ']);
            $this->registros[] = [
                'uf' => 'RJ',
                'codigo_municipio' => $codigoReceita,
                'municipio' => $codigoReceita === '5875' ? 'PARATI' : strtoupper(Str::ascii($nome)),
                'cnae_principal' => '5611201',
                'situacao_cadastral' => 'Ativa',
                'registros_no_recorte' => '1',
                'cnpjs_distintos' => '1',
                'cnpjs_ausentes' => '0',
            ];
        }

        AtividadeEconomica::create(['codigo_cnae' => '5611201', 'nome' => 'Atividade fictícia']);
    }

    protected function tearDown(): void
    {
        // Remove somente os arquivos fictícios criados por este teste.
        foreach ($this->arquivosTemporarios as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }

        parent::tearDown();
    }

    public function test_simula_lote_completo_incluindo_parati_sem_alterar_a_coleta_existente(): void
    {
        $fonte = FonteDado::create(['codigo' => 'opencnpj-receita', 'nome' => 'Fonte fictícia', 'url_referencia' => 'https://example.org/']);
        $coleta = ContagemEstabelecimento::create([
            'municipio_id' => Municipio::where('codigo_ibge', '3302700')->firstOrFail()->id,
            'atividade_economica_id' => AtividadeEconomica::firstOrFail()->id,
            'fonte_dado_id' => $fonte->id,
            'quantidade' => 620,
            'registros_no_recorte' => 620,
            'cnpjs_ausentes' => 0,
            'criterio' => 'ativos_cnae_principal_exato_v1',
            'consultado_em' => '2026-10-28 15:08:25',
        ]);
        $antes = $coleta->fresh()->toArray();

        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Registros conferidos: 92')
            ->expectsOutput('Soma das contagens municipais: 92')
            ->expectsOutput('Simulação concluída. Nenhum dado foi gravado.')
            ->assertSuccessful();

        $this->assertSame($antes, $coleta->fresh()->toArray());
        $this->assertDatabaseCount('contagens_estabelecimentos', 1);
        $this->assertDatabaseCount('fontes_dados', 1);
        $this->assertDatabaseCount('municipios', 92);
        Http::assertNothingSent();
    }

    public function test_mapa_tem_92_pares_unicos_preserva_marica_e_codigos_com_zero_inicial(): void
    {
        $mapa = config('municipios_receita');
        $this->assertCount(92, $mapa);
        $this->assertCount(92, array_unique(array_values($mapa)));
        $this->assertSame('3302700', $mapa['RJ:5853']);
        $this->assertSame('3303807', $mapa['RJ:5875']);
        $this->assertSame('3300233', $mapa['RJ:0770']);
        $this->assertSame('3304557', $mapa['RJ:6001']);
    }

    #[DataProvider('camposInvalidos')]
    public function test_rejeita_campo_invalido_sem_declarar_sucesso(string $campo, mixed $valor, string $mensagem): void
    {
        $this->registros[0][$campo] = $valor;
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Registro 1: '.$mensagem)
            ->doesntExpectOutput('Simulação concluída. Nenhum dado foi gravado.')
            ->assertFailed();

        $this->assertDatabaseCount('contagens_estabelecimentos', 0);
        $this->assertDatabaseCount('fontes_dados', 0);
    }

    public static function camposInvalidos(): array
    {
        return [
            'UF' => ['uf', 'SP', 'filtros diferentes de RJ/5611201/Ativa.'],
            'CNAE' => ['cnae_principal', '4712100', 'filtros diferentes de RJ/5611201/Ativa.'],
            'situação' => ['situacao_cadastral', 'Baixada', 'filtros diferentes de RJ/5611201/Ativa.'],
            'código numérico' => ['codigo_municipio', 770, 'campo codigo_municipio ausente ou não textual.'],
            'código sem zero inicial' => ['codigo_municipio', '770', 'código Receita precisa conter quatro dígitos textuais.'],
            'nome vazio' => ['municipio', ' ', 'campo municipio ausente ou não textual.'],
            'nome de controle' => ['municipio', "NOME\x1b", 'nome municipal inválido.'],
            'contagem numérica' => ['cnpjs_distintos', 1, 'campo cnpjs_distintos ausente ou não textual.'],
            'contagem nula' => ['cnpjs_distintos', null, 'campo cnpjs_distintos ausente ou não textual.'],
            'contagem negativa' => ['cnpjs_distintos', '-1', 'campo cnpjs_distintos fora do formato ou limite de contagem.'],
            'zero não canônico' => ['cnpjs_distintos', '01', 'campo cnpjs_distintos fora do formato ou limite de contagem.'],
            'contagem exponencial' => ['cnpjs_distintos', '1e2', 'campo cnpjs_distintos fora do formato ou limite de contagem.'],
            'overflow unsigned' => ['cnpjs_distintos', '4294967296', 'campo cnpjs_distintos fora do formato ou limite de contagem.'],
            'quebra de linha' => ['cnpjs_distintos', "1\n", 'campo cnpjs_distintos fora do formato ou limite de contagem.'],
            'duplicata na fonte' => ['registros_no_recorte', '2', 'ausências, duplicatas ou contagens inconsistentes.'],
            'CNPJ ausente' => ['cnpjs_ausentes', '1', 'ausências, duplicatas ou contagens inconsistentes.'],
        ];
    }

    public function test_rejeita_arquivo_inexistente(): void
    {
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => __DIR__.'/arquivo-inexistente.json'])
            ->expectsOutput('Arquivo do lote não encontrado ou sem permissão de leitura.')
            ->assertFailed();
    }

    public function test_rejeita_json_malformado(): void
    {
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->conteudo('{')])
            ->expectsOutput('O arquivo não contém um JSON válido.')
            ->assertFailed();
    }

    public function test_rejeita_arquivo_maior_que_limite_antes_de_decodificar(): void
    {
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->conteudo(str_repeat(' ', 1048577))])
            ->expectsOutput('Não foi possível ler o lote ou o arquivo ultrapassa 1 MiB.')
            ->assertFailed();
    }

    #[DataProvider('estruturasInvalidas')]
    public function test_rejeita_raiz_invalida_ou_lote_incompleto(mixed $estrutura): void
    {
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($estrutura)])
            ->expectsOutput('Esperada uma lista JSON com exatamente 92 registros municipais.')
            ->assertFailed();
    }

    public static function estruturasInvalidas(): array
    {
        return ['vazio' => [[]], 'objeto' => [(object) ['linhas' => []]], 'escalar' => [42]];
    }

    public function test_rejeita_91_municipios_sem_completar_ausencia_com_zero(): void
    {
        array_pop($this->registros);
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Esperada uma lista JSON com exatamente 92 registros municipais.')
            ->assertFailed();
    }

    public function test_rejeita_campo_ausente(): void
    {
        unset($this->registros[0]['uf']);
        $this->contratoInvalido();
    }

    public function test_rejeita_campo_extra(): void
    {
        $this->registros[0]['campo_extra'] = 'não faz parte do SQL';
        $this->contratoInvalido();
    }

    public function test_rejeita_registro_escalar(): void
    {
        $this->registros[0] = 'não é um objeto';
        $this->contratoInvalido();
    }

    public function test_rejeita_municipio_repetido_mesmo_com_outro_nome(): void
    {
        $this->registros[1]['codigo_municipio'] = $this->registros[0]['codigo_municipio'];
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Registro 2: código Receita repetido no lote.')
            ->assertFailed();
    }

    public function test_rejeita_codigo_receita_desconhecido(): void
    {
        $this->registros[0]['codigo_municipio'] = '9999';
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('O lote não corresponde aos 92 códigos Receita configurados.')
            ->assertFailed();
    }

    public function test_rejeita_nome_divergente_sem_associacao_aproximada(): void
    {
        $this->registros[0]['municipio'] = 'MUNICIPIO TESTE ERRADO';
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Registro 1: nome municipal diverge da correspondência configurada.')
            ->assertFailed();
    }

    public function test_excecao_parati_nao_aceita_outra_grafia_nao_documentada(): void
    {
        $indice = array_search('5875', array_column($this->registros, 'codigo_municipio'), true);
        $this->registros[$indice]['municipio'] = 'PARATO';
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Registro '.($indice + 1).': nome municipal diverge da correspondência configurada.')
            ->assertFailed();
    }

    public function test_rejeita_ibge_repetido_no_mapa(): void
    {
        config()->set('municipios_receita.RJ:0770', '3300936');
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Código IBGE repetido nas correspondências RJ.')
            ->assertFailed();
    }

    public function test_rejeita_mapa_incompleto_ou_cache_antigo(): void
    {
        config()->set('municipios_receita', ['RJ:5853' => '3302700']);
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('São necessárias 92 correspondências RJ. Confira a configuração e seu cache local.')
            ->assertFailed();
    }

    public function test_rejeita_destino_ibge_fora_do_rj(): void
    {
        config()->set('municipios_receita.RJ:0770', '3550308');
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Correspondência municipal com código inválido.')
            ->assertFailed();
    }

    public function test_rejeita_municipio_ausente_no_catalogo_sem_cria_lo(): void
    {
        Municipio::where('codigo_ibge', '3300233')->delete();
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Registro 1: município IBGE não cadastrado no RJ.')
            ->assertFailed();
        $this->assertDatabaseCount('municipios', 91);
    }

    public function test_rejeita_atividade_ausente_sem_cria_la(): void
    {
        AtividadeEconomica::query()->delete();
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('CNAE 5611201 não cadastrado neste banco.')
            ->assertFailed();
        $this->assertDatabaseCount('atividades_economicas', 0);
    }

    public function test_falha_de_banco_tem_mensagem_generica_sem_expor_conexao(): void
    {
        DB::connection()->beforeExecuting(fn () => throw new QueryException('sqlite', 'select', [], new \RuntimeException('falha fictícia')));
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Não foi possível consultar os catálogos locais. Confira banco e migrations.')
            ->assertFailed();
    }

    public function test_zero_registrado_nao_e_confundido_com_campo_ausente(): void
    {
        $this->registros[0]['registros_no_recorte'] = '0';
        $this->registros[0]['cnpjs_distintos'] = '0';
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Soma das contagens municipais: 91')
            ->expectsOutput('A soma não comprova cobertura real ou unicidade de CNPJ entre municípios; zero não comprova ausência real.')
            ->assertSuccessful();
    }

    public function test_limite_unsigned_e_somado_sem_overflow_de_32_bits(): void
    {
        $this->registros[0]['registros_no_recorte'] = '4294967295';
        $this->registros[0]['cnpjs_distintos'] = '4294967295';
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Soma das contagens municipais: 4294967386')
            ->assertSuccessful();
    }

    public function test_hash_normalizado_tolera_crlf_sem_alterar_arquivo(): void
    {
        $lf = json_encode($this->registros, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
        $crlf = str_replace("\n", "\r\n", $lf);
        $caminho = $this->conteudo($crlf);
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $caminho])
            ->expectsOutput('SHA-256 (LF): '.hash('sha256', $lf))
            ->assertSuccessful();
        $this->assertSame($crlf, file_get_contents($caminho));
    }

    private function contratoInvalido(): void
    {
        $this->artisan('cnpj:verificar-contagens-rj', ['arquivo' => $this->arquivo($this->registros)])
            ->expectsOutput('Registro 1: campos diferentes do contrato do SQL.')
            ->assertFailed();
    }

    private function arquivo(mixed $registros): string
    {
        return $this->conteudo(json_encode($registros, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function conteudo(string $conteudo): string
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'rightpoint-contagens-');
        $this->assertNotFalse($arquivo);
        $this->arquivosTemporarios[] = $arquivo;
        file_put_contents($arquivo, $conteudo);

        return $arquivo;
    }
}
