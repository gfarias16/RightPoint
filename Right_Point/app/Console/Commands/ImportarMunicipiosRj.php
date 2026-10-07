<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Models\Municipio;
use Illuminate\Support\Facades\DB;

class ImportarMunicipiosRj extends Command
{
    /*define o nome e a descrição do comando */
    protected $signature = 'ibge:importar-municipios-rj';

    protected $description = 'Comando para importar os municípios do estado do Rio de Janeiro a partir da API do IBGE.';

   /*metodo que define a lógica do comando */
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
    $dadosParaGravar=[];

    /*aqui verificamos se os dados dos municípios são válidos   */
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

    $this->info('Registros preparados: ' . count($dadosParaGravar));
    $this->info('Consulta concluída: 92 municípios do RJ.');
    $this->line('Nenhum dado foi gravado no banco.');

    return self::SUCCESS;
    }
}
