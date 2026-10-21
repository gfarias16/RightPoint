<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerificarEstabelecimentos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cnpj:verificar-estabelecimentos {arquivo}';

    protected $description = 'Verifica se o arquivo de estabelecimentos pode ser lido.';

    public function handle(): int
    {
        $caminho = $this->argument('arquivo');

        // Conferimos o arquivo antes de tentar ler seus registros.
        if (! is_file($caminho) || ! is_readable($caminho)) {
            $this->error('O arquivo não existe ou não pode ser lido.');
            return self::FAILURE;
        }

        $arquivo = fopen($caminho, 'rb');

        if ($arquivo === false) {
            $this->error('Não foi possível abrir o arquivo.');
            return self::FAILURE;
        }

        // Lemos somente um registro, sem carregar o arquivo inteiro na memória.
        $linha = fgetcsv($arquivo, 0, ';', '"', '');
        fclose($arquivo);

        if ($linha === false || $linha === [null]) {
            $this->error('Não foi possível ler uma primeira linha preenchida.');
            return self::FAILURE;
        }

        $this->info('Primeira linha lida: ' . count($linha) . ' campos.');

        return self::SUCCESS;
            }
}
