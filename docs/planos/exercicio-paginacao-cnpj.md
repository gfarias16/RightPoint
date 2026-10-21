# Exercício — paginação limitada de CNPJ

Referência didática de 21/10/2026. **A paginação limitada já foi implementada pelo agente a pedido do usuário. A fonte atual é `Right_Point/app/Console/Commands/VerificarBuscaCnpj.php`; não substitua novamente a classe pelo exemplo abaixo.**

O exemplo abaixo foi conferido separadamente em memória com HTTP simulado e usa uma tabela acumulada. A implementação atual mantém uma tabela por página e possui testes persistentes em `Right_Point/tests/Feature/VerificarBuscaCnpjTest.php`. A confirmação externa do novo laço continua pendente; os testes simulados não comprovam cobertura da fonte.

## Para que serve

- Consultar até 3 páginas por execução, começando com apenas 1 por padrão.
- Reutilizar o cursor sem modificá-lo e manter os mesmos filtros.
- Guardar os CNPJs vistos entre páginas da mesma execução e interromper se houver repetição.
- Interromper em erro, HTTP 429 ou cursor repetido. Não há retentativa automática.
- Mostrar resultados como **amostra**; ainda não gravar nem calcular concorrência total.

## Como fazer

1. Abra `Right_Point/app/Console/Commands/VerificarBuscaCnpj.php`.
2. Leia o `for`, a memória de CNPJs/cursores fora dele e as condições de parada. O exemplo abaixo é uma alternativa didática, não uma instrução para sobrescrever o arquivo atual.
3. Execute a checagem de sintaxe e o comando de teste ao final. Envie a saída para revisão.

```php
<?php

namespace App\Console\Commands;

use App\Models\Municipio;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class VerificarBuscaCnpj extends Command
{
    protected $signature = 'cnpj:verificar-busca {codigo_ibge} {cnae} {--cursor=} {--paginas=1}';

    protected $description = 'Confere uma amostra de CNPJ em até 3 páginas, sem gravar dados.';

    public function handle(): int
    {
        $codigoIbge = (string) $this->argument('codigo_ibge');
        $cnae = (string) $this->argument('cnae');
        $paginasInformadas = (string) $this->option('paginas');

        if (
            preg_match('/^33[0-9]{5}$/', $codigoIbge) !== 1
            || ! Municipio::query()
                ->where('codigo_ibge', $codigoIbge)
                ->where('uf', 'RJ')
                ->exists()
        ) {
            $this->error('Informe o código IBGE de um município do RJ cadastrado.');
            return self::FAILURE;
        }

        if (preg_match('/^[0-9]{7}$/', $cnae) !== 1) {
            $this->error('Informe um CNAE de sete dígitos.');
            return self::FAILURE;
        }

        if (
            ! ctype_digit($paginasInformadas)
            || (int) $paginasInformadas < 1
            || (int) $paginasInformadas > 3
        ) {
            $this->error('Neste exercício, informe de 1 a 3 páginas.');
            return self::FAILURE;
        }

        $maxPaginas = (int) $paginasInformadas;
        $cursor = $this->option('cursor');

        if ($cursor === '') {
            $this->error('O cursor informado não pode estar vazio.');
            return self::FAILURE;
        }

        // Estas listas ficam fora do laço para lembrar o que veio nas páginas anteriores.
        $cnpjsVistos = [];
        $cursoresVistos = [];
        $linhas = [];
        $paginasConferidas = 0;
        $ativos = 0;

        for ($pagina = 1; $pagina <= $maxPaginas; $pagina++) {
            $parametros = [
                'uf' => 'RJ',
                'municipio' => $codigoIbge,
                'cnae_fiscal' => $cnae,
                'limit' => 2,
            ];

            if ($cursor !== null) {
                $parametros['cursor'] = $cursor;
                $cursoresVistos['cursor:' . $cursor] = true;
            }

            try {
                $resposta = Http::acceptJson()->timeout(20)->get(
                    'https://minhareceita.org/',
                    $parametros
                );
            } catch (ConnectionException $e) {
                $this->error("Falha de conexão na página {$pagina}. Amostra incompleta.");
                return self::FAILURE;
            }

            if ($resposta->status() === 429) {
                $this->warn("Busca limitada pela API na página {$pagina} (HTTP 429). Amostra incompleta.");
                return self::FAILURE;
            }

            if (! $resposta->successful()) {
                $this->error("Página {$pagina}: HTTP {$resposta->status()}. Amostra incompleta.");
                return self::FAILURE;
            }

            $dados = $resposta->json();

            if (! is_array($dados) || ! isset($dados['data']) || ! is_array($dados['data'])) {
                $this->error("Página {$pagina}: resposta sem a lista data esperada.");
                return self::FAILURE;
            }

            foreach ($dados['data'] as $indice => $item) {
                if (
                    ! is_array($item)
                    || ! is_string($item['cnpj'] ?? null)
                    || trim($item['cnpj']) === ''
                    || ! is_int($item['codigo_municipio_ibge'] ?? null)
                    || ! is_int($item['cnae_fiscal'] ?? null)
                    || ! is_int($item['situacao_cadastral'] ?? null)
                ) {
                    $this->error("Página {$pagina}, registro {$indice}: campos inválidos.");
                    return self::FAILURE;
                }

                $cnpj = $item['cnpj'];
                $cnaeRecebido = str_pad((string) $item['cnae_fiscal'], 7, '0', STR_PAD_LEFT);

                if (
                    ($item['uf'] ?? null) !== 'RJ'
                    || (string) $item['codigo_municipio_ibge'] !== $codigoIbge
                    || $cnaeRecebido !== $cnae
                ) {
                    $this->error("Página {$pagina}, registro {$indice}: filtros não correspondem.");
                    return self::FAILURE;
                }

                if (isset($cnpjsVistos[$cnpj])) {
                    $this->error("Página {$pagina}: CNPJ repetido na amostra. Consulta interrompida.");
                    return self::FAILURE;
                }

                $cnpjsVistos[$cnpj] = true;
                $ativa = $item['situacao_cadastral'] === 2;
                if ($ativa) {
                    $ativos++;
                }

                $linhas[] = [
                    $pagina,
                    $cnpj,
                    $item['uf'],
                    $item['codigo_municipio_ibge'],
                    $cnaeRecebido,
                    $ativa ? 'Ativa' : 'Código ' . $item['situacao_cadastral'],
                ];
            }

            $cursor = $dados['cursor'] ?? null;

            if ($cursor !== null) {
                if ((! is_string($cursor) && ! is_int($cursor)) || $cursor === '') {
                    $this->error("Página {$pagina}: cursor de formato inesperado.");
                    return self::FAILURE;
                }

                if (isset($cursoresVistos['cursor:' . $cursor])) {
                    $this->error("Página {$pagina}: cursor repetido. Consulta interrompida.");
                    return self::FAILURE;
                }
            }

            $paginasConferidas++;
            if ($cursor === null) {
                break;
            }
        }

        $this->table(
            ['Página', 'CNPJ', 'UF', 'Código IBGE', 'CNAE principal', 'Situação'],
            $linhas
        );
        $this->info('Páginas conferidas: ' . $paginasConferidas);
        $this->line('CNPJs distintos nesta amostra: ' . count($cnpjsVistos));
        $this->line('Ativos nesta amostra: ' . $ativos);

        if ($cursor !== null) {
            $this->warn('Limite de páginas atingido; ainda existe continuação.');
            $this->line('Cursor para continuar: ' . $cursor);
        } else {
            $this->line('A API não indicou outra página.');
        }

        $this->line('Nenhum dado foi gravado. A amostra não representa o total validado de concorrentes.');
        return self::SUCCESS;
    }
}
```

## Como conferir

Execute dentro de `Right_Point/`:

```powershell
php -l app/Console/Commands/VerificarBuscaCnpj.php
php artisan help cnpj:verificar-busca
php artisan cnpj:verificar-busca 3302700 5611201 --paginas=2
```

- O `for` limita as chamadas; `break` encerra antes do limite se não houver continuação.
- `$cnpjsVistos` e `$cursoresVistos` ficam fora do `for` e lembram as páginas anteriores.
- O cabeçalho agora tem 6 colunas, incluindo a página, na mesma ordem dos 6 valores de cada linha.
- Se a API retornar 429, interrompa o teste. Se o limite for atingido, o comando conclui a amostra e informa que ela tem continuação; isso não publica uma carga completa.
- CNPJ continua sendo conferido quanto a presença textual e repetição; a validação completa do formato permanece pendente.
- Próxima etapa após revisão: definir procedência, cobertura e estrutura de armazenamento antes de importar estabelecimentos.

Referência: [paginação da Minha Receita](https://docs.minhareceita.org/como-usar/#busca-paginada). O limite de 3 páginas é uma escolha deste exercício, não um limite declarado pelo fornecedor.
