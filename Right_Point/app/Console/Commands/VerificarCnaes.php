<?php

namespace App\Console\Commands;

use App\Support\IbgeHttp;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class VerificarCnaes extends Command
{
    protected $signature = 'ibge:verificar-cnaes';

    protected $description = 'Confere as subclasses CNAE do IBGE sem gravar dados.';

    public function handle(): int
    {
        try {
            $resposta = IbgeHttp::request()->timeout(30)->get(
                'https://servicodados.ibge.gov.br/api/v2/cnae/subclasses'
            );
        } catch (ConnectionException $e) {
            $this->error('Falha na API CNAE: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $resposta->successful()) {
            $this->error("O IBGE respondeu com HTTP {$resposta->status()}.");
            return self::FAILURE;
        }

        $cnaes = $resposta->json();

        if (! is_array($cnaes) || ! array_is_list($cnaes) || count($cnaes) !== 1332) {
            $this->error('A resposta não contém os 1.332 registros esperados.');
            return self::FAILURE;
        }

        $codigosVistos = [];

        // Validamos a lista inteira antes de pensar em importá-la.
        foreach ($cnaes as $indice => $cnae) {
            $codigo = is_array($cnae) ? ($cnae['id'] ?? null) : null;
            $nome = is_array($cnae) ? ($cnae['descricao'] ?? null) : null;

            if (
                ! is_string($codigo)
                || preg_match('/^[0-9]{7}$/', $codigo) !== 1
                || ! is_string($nome)
                || trim($nome) === ''
                || mb_strlen(trim($nome)) > 255
            ) {
                $this->error("Registro {$indice} possui código ou descrição inválidos.");
                return self::FAILURE;
            }

            if (isset($codigosVistos[$codigo])) {
                $this->error("CNAE duplicado: {$codigo}.");
                return self::FAILURE;
            }

            $codigosVistos[$codigo] = true;
        }

        $this->info('CNAEs conferidos: ' . count($cnaes));
        $this->line('Primeiro: ' . $cnaes[0]['id'] . ' — ' . $cnaes[0]['descricao']);
        $this->comment('Nenhum dado foi gravado.');

        return self::SUCCESS;
    }
}
