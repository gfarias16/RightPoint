<?php

namespace App\Console\Commands;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Command;

/*arquivo que realiza consulta diretamnete na API do IBGE sem gravar dados no banco*/

class ConsultarIbge extends Command
{
    /*define o nome e a descrição do comando */
    protected $signature = 'ibge:consultar-rio';

    protected $description = 'Consulta município e população do Rio de Janeiro no IBGE';

    /**
     * funcao que realiza a consulta.
     */
    public function handle(): int
    {
        $codigoIbge ='3304557';

        /* aqui faremos a consulta */
        try {
            $respostaMunicipios= Http::timeout(10)->get(
                'https://servicodados.ibge.gov.br/api/v1/localidades/estados/33/municipios'
            );

            $respostaPopulacao= Http::timeout(10)->get(
                 "https://apisidra.ibge.gov.br/values/t/4714/n6/{$codigoIbge}/v/93/p/2022"
            );
        } catch (ConnectionException $e) {
            $this->error ('Nao foi possivel conectar na API do IBGE.');
            return self::FAILURE;
        }
        /* aqui verificamos se a resposta foi bem sucedida e se os dados são válidos */
        if (! $respostaMunicipios->successful() || ! $respostaPopulacao->successful()) {
            $this->error('Uma das APIs do IBGE retornou erro.');
            return self::FAILURE;
        }

        $municipios = $respostaMunicipios->json();
        $populacao = $respostaPopulacao->json();


        /* aqui verificamos se os dados são válidos */
         if (! is_array($municipios) || ! is_array($populacao)) {
            $this->error('O IBGE retornou dados em formato inesperado.');
            return self::FAILURE;
        }

        $municipio = collect($municipios)->first(
            fn ($item) => is_array($item)
                && (string) ($item['id'] ?? '') === $codigoIbge
        );

        $registro = $populacao[1] ?? null;


        /*
        */
        if (
            ! is_array($municipio)
            || ! is_array($registro)
            || ! is_string($municipio['nome'] ?? null)
            || (string) ($registro['D1C'] ?? '') !== $codigoIbge
            || ! ctype_digit((string) ($registro['V'] ?? ''))
            || ! is_string($registro['MN'] ?? null)
            || ! ctype_digit((string) ($registro['D3C'] ?? ''))
        ) {
            $this->error('Município ou população ausente ou inválida.');
            return self::FAILURE;
        }

        $this->info("Município: {$municipio['nome']} ({$codigoIbge})");
        $this->line("População: {$registro['V']} {$registro['MN']}");
        $this->line("Ano: {$registro['D3C']}");

        return self::SUCCESS;
    }
}
