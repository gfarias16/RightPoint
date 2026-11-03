<?php

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;

class ConsultaSqlPerfisPilotoTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE receita_ficticia (uf TEXT, codigo_municipio TEXT, municipio TEXT, cnae_principal TEXT, situacao_cadastral TEXT, cnpj TEXT)');
        // Compatibilidade apenas para verificar a lógica; não valida o dialeto GoogleSQL.
        $this->pdo->sqliteCreateAggregate('COUNTIF', fn ($soma, $linha, $valor) => ($soma ?? 0) + (int) (bool) $valor, fn ($soma, $linhas) => $soma ?? 0, 1);
    }

    public function test_sql_separa_quatro_cnaes_e_aplica_uf_situacao_sem_preencher_ausencias(): void
    {
        foreach (['5611201', '1091102', '4721102', '9602501'] as $indice => $cnae) {
            $this->inserir($cnae, str_pad((string) ($indice + 1), 14, '0', STR_PAD_LEFT));
        }

        $this->inserir('5611201', '00000000000005', 'SP');
        $this->inserir('5611201', '00000000000006', 'RJ', 'Baixada');
        $this->inserir('7490104', '00000000000007');
        $linhas = $this->consultar();
        $this->assertCount(4, $linhas);
        $this->assertSame(['1091102', '4721102', '5611201', '9602501'], array_column($linhas, 'cnae_principal'));

        foreach ($linhas as $linha) {
            $this->assertSame('0770', $linha['codigo_municipio']);
            $this->assertSame(1, $linha['registros_no_recorte']);
            $this->assertSame(1, $linha['cnpjs_distintos']);
            $this->assertSame(0, $linha['cnpjs_ausentes']);
        }
    }

    public function test_sql_expõe_duplicatas_e_identificadores_ausentes_para_rejeicao_pelo_validador(): void
    {
        foreach (['00000000000001', '00000000000001', null, '   '] as $cnpj) {
            $this->inserir('1091102', $cnpj);
        }

        $linhas = $this->consultar();
        $this->assertCount(1, $linhas);
        $this->assertSame(4, $linhas[0]['registros_no_recorte']);
        $this->assertSame(2, $linhas[0]['cnpjs_distintos']);
        $this->assertSame(2, $linhas[0]['cnpjs_ausentes']);
    }

    private function inserir(string $cnae, ?string $cnpj, string $uf = 'RJ', string $situacao = 'Ativa'): void
    {
        $this->pdo->prepare('INSERT INTO receita_ficticia VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$uf, '0770', 'Município fictício', $cnae, $situacao, $cnpj]);
    }

    private function consultar(): array
    {
        $sql = file_get_contents(dirname(__DIR__, 3).'/docs/consultas/cnpj-rj-perfis-piloto.sql');
        $this->assertNotFalse($sql);
        $sql = str_replace('`opencnpj-bigquery.public.receita`', 'receita_ficticia', $sql);

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
