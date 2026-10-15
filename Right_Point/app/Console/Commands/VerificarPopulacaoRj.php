<?php

namespace App\Console\Commands;

use App\Models\Municipio;
use App\Support\IbgeHttp;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class VerificarPopulacaoRj extends Command
{
    protected $signature = 'ibge:verificar-populacao-rj';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Confere a população de 2022 dos municípios do Rio de Janeiro.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Consulta a população de 2022 dos municípios do RJ antes de qualquer gravação.
        try {
            $resposta = IbgeHttp::request()->timeout(45)->get(
                'https://servicodados.ibge.gov.br/api/v3/agregados/4714/periodos/2022/variaveis/93',
                ['localidades' => 'N6[N3[33]]']
            );
        } catch (ConnectionException $e) {
            $this->error('Não foi possível conectar à API do IBGE.');
            return self::FAILURE;
        }

        if (! $resposta->successful()) {
            $this->error("O IBGE respondeu com HTTP {$resposta->status()}.");
            return self::FAILURE;
        }

        $dados = $resposta->json();
        $series = $dados[0]['resultados'][0]['series'] ?? null;

        if (! is_array($series) || $series === []) {
            $this->error('Não foram encontradas séries no formato esperado.');
            return self::FAILURE;
        }

        $municipiosPorCodigo = Municipio::query()
            ->where('uf', 'RJ')
            ->pluck('id', 'codigo_ibge');

        $codigosBanco = $municipiosPorCodigo->keys()->all();

        if (count($codigosBanco) !== 92) {
            $this->error('O banco não contém os 92 municípios do RJ.');
            return self::FAILURE;
        }

        $populacoes = [];

        foreach ($series as $indice => $item) {
            $codigo = $item['localidade']['id'] ?? null;
            $nivel = $item['localidade']['nivel']['id'] ?? null;
            $valor = $item['serie']['2022'] ?? null;

            if (
                ! is_string($codigo)
                || preg_match('/^33[0-9]{5}$/', $codigo) !== 1
                || $nivel !== 'N6'
                || ! is_string($valor)
                || ! ctype_digit($valor)
            ) {
                $this->error("Série {$indice} possui código, nível ou população inválidos.");
                return self::FAILURE;
            }

            if (isset($populacoes[$codigo])) {
                $this->error("Código IBGE duplicado: {$codigo}.");
                return self::FAILURE;
            }

            $populacoes[$codigo] = (int) $valor;
        }

        $faltantes = array_diff($codigosBanco, array_keys($populacoes));
        $extras = array_diff(array_keys($populacoes), $codigosBanco);

        if (count($series) !== 92 || $faltantes !== [] || $extras !== []) {
            $this->error('Os códigos da API não correspondem aos municípios do banco.');
            $this->line('Faltantes: ' . implode(', ', $faltantes));
            $this->line('Extras: ' . implode(', ', $extras));
            return self::FAILURE;
        }

        $this->info('População de 2022 conferida para os 92 municípios do RJ. Nenhum dado foi gravado.');

        $registrosPreparados = [];

        foreach ($populacoes as $codigo => $valor) {
            $registrosPreparados[] = [
                'municipio_id' => $municipiosPorCodigo->get($codigo),
                'codigo_indicador' => 'populacao_residente',
                'valor' => $valor,
                'unidade' => 'Pessoas',
                'periodo_referencia' => '2022',
            ];
        }

        $this->info(
            'Populações preparadas: ' . count($registrosPreparados) .
            '. Nenhum dado foi gravado.'
        );

        return self::SUCCESS;
    }
}
