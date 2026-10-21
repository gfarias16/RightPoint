<?php

namespace App\Console\Commands;

use App\Models\Municipio;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class VerificarBuscaCnpj extends Command
{
    protected $signature = 'cnpj:verificar-busca {codigo_ibge} {cnae} {--cursor=} {--paginas=1}';

    protected $description = 'Confere de 1 a 3 páginas da busca pública de CNPJ, sem gravar dados.';

    public function handle(): int
    {
        $codigoIbge = (string) $this->argument('codigo_ibge');
        $cnae = (string) $this->argument('cnae');
        $paginasInformadas = (string) $this->option('paginas');

        // Limita o exercício para evitar muitas requisições.
        if (
            ! ctype_digit($paginasInformadas)
            || (int) $paginasInformadas < 1
            || (int) $paginasInformadas > 3
        ) {
            $this->error('Neste exercício, informe de 1 a 3 páginas.');

            return self::FAILURE;
        }

        $maxPaginas = (int) $paginasInformadas;

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

        $parametros = [
            'uf' => 'RJ',
            'municipio' => $codigoIbge,
            'cnae_fiscal' => $cnae,
            'limit' => 2,
        ];

        $cursor = $this->option('cursor');

        // A primeira consulta não exige cursor; a continuação reutiliza o recebido.
        if ($cursor !== null) {
            $parametros['cursor'] = $cursor;
        }

        // Esta memória pertence à execução inteira, não apenas à página atual.
        $cnpjsVistos = [];
        $cursoresVistos = [];

        for ($pagina = 1; $pagina <= $maxPaginas; $pagina++) {
            if (isset($parametros['cursor'])) {
                $cursoresVistos['cursor:'.$parametros['cursor']] = true;
            }

            $this->line("Página {$pagina} de no máximo {$maxPaginas}:");

            try {
                $resposta = Http::acceptJson()->timeout(20)->get(
                    'https://minhareceita.org/',
                    $parametros
                );
            } catch (ConnectionException $e) {
                $this->error('Não foi possível conectar à busca de CNPJ.');

                return self::FAILURE;
            }

            if ($resposta->status() === 429) {
                $this->warn('A API limitou a busca (HTTP 429). Nenhuma conclusão sobre concorrentes.');

                return self::FAILURE;
            }

            if (! $resposta->successful()) {
                $this->error("A API respondeu com HTTP {$resposta->status()}.");

                return self::FAILURE;
            }

            $dados = $resposta->json();

            if (! is_array($dados) || ! isset($dados['data']) || ! is_array($dados['data'])) {
                $this->error('A resposta não contém a lista data esperada.');

                return self::FAILURE;
            }

            $linhas = [];

            foreach ($dados['data'] as $indice => $item) {
                // Rejeita campos ausentes ou tipos diferentes do formato esperado.
                if (
                    ! is_array($item)
                    || ! is_string($item['cnpj'] ?? null)
                    || trim($item['cnpj']) === ''
                    || ! is_int($item['codigo_municipio_ibge'] ?? null)
                    || ! is_int($item['cnae_fiscal'] ?? null)
                    || ! is_int($item['situacao_cadastral'] ?? null)
                ) {
                    $this->error("Registro {$indice}: campos ausentes ou tipos inesperados.");

                    return self::FAILURE;
                }

                $cnpj = $item['cnpj'];

                // Impede repetição dentro da página ou entre páginas desta execução.
                if (isset($cnpjsVistos[$cnpj])) {
                    $this->error("Registro {$indice}: CNPJ repetido nesta amostra.");

                    return self::FAILURE;
                }

                $cnpjsVistos[$cnpj] = true;

                // A API usa número; recuperamos zeros iniciais do código CNAE.
                $cnaeRecebido = str_pad(
                    (string) $item['cnae_fiscal'],
                    7,
                    '0',
                    STR_PAD_LEFT
                );

                if (
                    ($item['uf'] ?? null) !== 'RJ'
                    || (string) $item['codigo_municipio_ibge'] !== $codigoIbge
                    || $cnaeRecebido !== $cnae
                ) {
                    $this->error("Registro {$indice}: não corresponde aos filtros solicitados.");

                    return self::FAILURE;
                }

                $linhas[] = [
                    $cnpj,
                    $item['uf'],
                    $item['codigo_municipio_ibge'],
                    $cnaeRecebido,
                    $item['situacao_cadastral'] === 2
                        ? 'Ativa'
                        : 'Código '.$item['situacao_cadastral'],
                ];
            }

            $this->table(
                ['CNPJ', 'UF', 'Código IBGE', 'CNAE principal', 'Situação'],
                $linhas
            );

            $this->info('Registros nesta página: '.count($dados['data']));

            $proximoCursor = $dados['cursor'] ?? null;

            // Sem continuação, não faz requisições desnecessárias até o teto.
            if ($proximoCursor === null) {
                $this->line('A API não indicou outra página.');
                break;
            }

            if (
                (! is_string($proximoCursor) && ! is_int($proximoCursor))
                || $proximoCursor === ''
            ) {
                $this->error('A API retornou um cursor inesperado.');

                return self::FAILURE;
            }

            // O cursor é opaco: não o recalculamos nem alteramos seu conteúdo.
            if (isset($cursoresVistos['cursor:'.$proximoCursor])) {
                $this->error('Cursor repetido. Consulta interrompida.');

                return self::FAILURE;
            }

            $parametros['cursor'] = $proximoCursor;
            $this->line('Cursor para continuar: '.$proximoCursor);

            if ($pagina === $maxPaginas) {
                $this->warn('Limite de páginas atingido; ainda existe continuação.');
            }
        }

        $this->line('Nenhum dado foi gravado. Esta amostra não representa o total de concorrentes.');

        return self::SUCCESS;
    }
}
