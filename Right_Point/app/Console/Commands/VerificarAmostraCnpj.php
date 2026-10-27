<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use JsonException;

class VerificarAmostraCnpj extends Command
{
    protected $signature = 'cnpj:verificar-amostra {arquivo?}';

    protected $description = 'Lê a amostra JSON local de CNPJ, sem gravar dados.';

    public function handle(): int
    {
        $caminho = $this->argument('arquivo') ?? storage_path('app/amostras/cnpj-marica-5611201.json');

        if (! is_file($caminho) || ! is_readable($caminho)) {
            $this->error('Arquivo da amostra não encontrado ou sem permissão de leitura.');

            return self::FAILURE;
        }

        $conteudo = file_get_contents($caminho);

        if ($conteudo === false) {
            $this->error('Não foi possível ler a amostra.');

            return self::FAILURE;
        }

        try {
            // A exportação recebida contém uma lista JSON completa.
            $registros = json_decode($conteudo, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $erro) {
            $this->error('O arquivo não contém um JSON válido.');

            return self::FAILURE;
        }

        if (! is_array($registros) || ! array_is_list($registros)) {
            $this->error('O JSON precisa conter uma lista de registros.');

            return self::FAILURE;
        }

        if ($registros === []) {
            $this->error('A amostra está vazia.');

            return self::FAILURE;
        }

        $filtrosEsperados = [
            'uf' => 'RJ',
            'codigo_municipio' => '5853',
            'cnae_principal' => '5611201',
            'situacao_cadastral' => 'Ativa',
        ];

        $camposObrigatorios = [
            'cnpj',
            'uf',
            'codigo_municipio',
            'cnae_principal',
            'situacao_cadastral',
        ];

        $cnpjsVistos = [];

        foreach ($registros as $indice => $registro) {
            $numero = $indice + 1;

            if (! is_array($registro)) {
                $this->error("Registro {$numero}: estrutura inválida.");

                return self::FAILURE;
            }

            foreach ($camposObrigatorios as $campo) {
                if (
                    ! isset($registro[$campo])
                    || ! is_string($registro[$campo])
                    || trim($registro[$campo]) === ''
                ) {
                    $this->error("Registro {$numero}: campo {$campo} ausente ou não textual.");

                    return self::FAILURE;
                }
            }

            // Este exercício verifica o formato numérico presente na amostra.
            if (preg_match('/^[0-9]{14}$/D', $registro['cnpj']) !== 1) {
                $this->error("Registro {$numero}: CNPJ precisa conter 14 dígitos.");

                return self::FAILURE;
            }

            foreach ($filtrosEsperados as $campo => $valorEsperado) {
                if ($registro[$campo] !== $valorEsperado) {
                    $this->error("Registro {$numero}: campo {$campo} diferente do esperado.");

                    return self::FAILURE;
                }
            }

            // O prefixo mantém o identificador como chave textual.
            $chave = 'cnpj:' . $registro['cnpj'];

            if (isset($cnpjsVistos[$chave])) {
                $this->error("Registro {$numero}: CNPJ repetido na amostra.");

                return self::FAILURE;
            }

            $cnpjsVistos[$chave] = true;
        }

        $this->info('Registros conferidos: ' . count($registros));
        $this->line('Campos, formato, filtros e duplicatas conferidos.');
        $this->warn('Esta amostra não representa o total de concorrentes.');
        $this->line('Nenhum dado foi gravado.');

        return self::SUCCESS;
    }
}