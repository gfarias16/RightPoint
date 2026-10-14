<?php

namespace App\Console\Commands;

use App\Models\Municipio;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportarMunicipiosRj extends Command
{
    protected $signature = 'ibge:importar-municipios-rj';

    protected $description = 'Comando para importar os municípios do estado do Rio de Janeiro a partir da API do IBGE.';

    public function handle(): int
    {
        try {
            $resposta = Http::timeout(10)->get(
                'https://servicodados.ibge.gov.br/api/v1/localidades/estados/33/municipios'
            );
        } catch (ConnectionException $e) {
            $this->error('Não foi possível conectar à API do IBGE.');
            return self::FAILURE;
        }

        if (! $resposta->successful()) {
            $this->error("A API do IBGE respondeu com HTTP {$resposta->status()}.");
            return self::FAILURE;
        }

        $municipios = $resposta->json();

        if (! is_array($municipios) || ! array_is_list($municipios)) {
            $this->error('O IBGE não retornou uma lista de municípios.');
            return self::FAILURE;
        }

        if (count($municipios) !== 92) {
            $this->error('Era esperado encontrar 92 municípios do RJ; recebidos: ' . count($municipios));
            return self::FAILURE;
        }

        $codigosVistos = [];
        $dadosParaGravar = [];

        // Validamos a lista inteira antes de gravar qualquer município.
        foreach ($municipios as $indice => $municipio) {
            if (! is_array($municipio)) {
                $this->error("Registro {$indice} não é um município válido.");
                return self::FAILURE;
            }

            $id = $municipio['id'] ?? null;
            $nome = $municipio['nome'] ?? null;
            $uf = $municipio['microrregiao']['mesorregiao']['UF']['sigla'] ?? null;

            if ($uf !== 'RJ') {
                $this->error("Registro {$indice} não pertence ao RJ ou não possui UF válida.");
                return self::FAILURE;
            }

            if (! is_int($id) && ! is_string($id)) {
                $this->error("Registro {$indice} não possui código IBGE válido.");
                return self::FAILURE;
            }

            $codigoIbge = (string) $id;

            if (
                preg_match('/^33[0-9]{5}$/', $codigoIbge) !== 1
                || ! is_string($nome)
                || trim($nome) === ''
            ) {
                $this->error("Registro {$indice} possui código ou nome inválido.");
                return self::FAILURE;
            }

            if (isset($codigosVistos[$codigoIbge])) {
                $this->error("Código IBGE duplicado: {$codigoIbge}.");
                return self::FAILURE;
            }

            $codigosVistos[$codigoIbge] = true;

            $dadosParaGravar[] = [
                'codigo_ibge' => $codigoIbge,
                'nome' => trim($nome),
                'uf' => $uf,
            ];
        }

        try {
            // A transação desfaz a gravação se houver erro; use disponibiliza o array na função.
            DB::transaction(function () use ($dadosParaGravar): void {
                // O código IBGE identifica o município: insere novos e atualiza nome e UF dos existentes.
                Municipio::upsert($dadosParaGravar, ['codigo_ibge'], ['nome', 'uf']);
            });
        } catch (\Throwable $e) {
            report($e);
            $this->error('Não foi possível gravar os municípios. Consulte o log do Laravel.');
            return self::FAILURE;
        }

        $this->info('Importação concluída: ' . count($dadosParaGravar) . ' municípios processados.');

        return self::SUCCESS;
    }
}
