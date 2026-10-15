<?php

namespace App\Console\Commands;

use App\Models\AtividadeEconomica;
use App\Support\IbgeHttp;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;

class ImportarCnaes extends Command
{
    protected $signature = 'ibge:importar-cnaes';

    protected $description = 'Importa as subclasses CNAE consultadas na API do IBGE.';

    public function handle(): int
    {
        try {
            $resposta = IbgeHttp::request()->timeout(30)->get(
                'https://servicodados.ibge.gov.br/api/v2/cnae/subclasses'
            );
        } catch (ConnectionException $e) {
            $this->error('Não foi possível conectar à API CNAE: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $resposta->successful()) {
            $this->error("O IBGE respondeu com HTTP {$resposta->status()}.");
            return self::FAILURE;
        }

        $cnaes = $resposta->json();

        if (! is_array($cnaes) || ! array_is_list($cnaes) || count($cnaes) !== 1332) {
            $this->error('A resposta não contém os 1.332 CNAEs esperados.');
            return self::FAILURE;
        }

        $codigosVistos = [];
        $dadosParaGravar = [];

        // Toda a resposta é validada antes da primeira gravação.
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
                $this->error("CNAE {$indice} possui código ou descrição inválidos.");
                return self::FAILURE;
            }

            if (isset($codigosVistos[$codigo])) {
                $this->error("Código CNAE duplicado: {$codigo}.");
                return self::FAILURE;
            }

            $codigosVistos[$codigo] = true;
            $dadosParaGravar[] = [
                'codigo_cnae' => $codigo,
                'nome' => trim($nome),
            ];
        }

        try {
            DB::transaction(function () use ($dadosParaGravar): void {
                AtividadeEconomica::upsert(
                    $dadosParaGravar,
                    ['codigo_cnae'],
                    ['nome']
                );
            });
        } catch (\Throwable $e) {
            report($e);
            $this->error('Falha ao gravar os CNAEs. Consulte o log do Laravel.');
            return self::FAILURE;
        }

        $this->info('Importação concluída: ' . count($dadosParaGravar) . ' CNAEs processados.');
        return self::SUCCESS;
    }
}