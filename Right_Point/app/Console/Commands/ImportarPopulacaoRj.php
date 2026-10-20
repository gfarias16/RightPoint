<?php

namespace App\Console\Commands;

use App\Models\FonteDado;
use App\Models\IndicadorMunicipal;
use App\Models\Municipio;
use App\Support\IbgeHttp;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;

//define('IBGE_CENSO_2022_POPULACAO', 'ibge-censo-2022-populacao');
//O fluxo é: consultar → validar os 92 códigos e valores → registrar a fonte → gravar tudo numa transação.
//Se a gravação falhar, a transação desfaz as mudanças; upsert atualiza o mesmo município/fonte/indicador/período em vez de criar duplicatas.
//A migration já possui a chave única necessária para isso no MySQL/MariaDB.

class ImportarPopulacaoRj extends Command
{
    protected $signature = 'ibge:importar-populacao-rj';

    protected $description = 'Importa a população do Censo 2022 dos 92 municípios do RJ.';

    public function handle(): int
    {
        $url = 'https://servicodados.ibge.gov.br/api/v3/agregados/4714/periodos/2022/variaveis/93';

        try {
            $resposta = IbgeHttp::request()->timeout(45)->get(
                $url,
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

        $series = $resposta->json()[0]['resultados'][0]['series'] ?? null;

        if (! is_array($series) || count($series) !== 92) {
            $this->error('A resposta não contém as 92 séries esperadas.');
            return self::FAILURE;
        }

        $municipiosPorCodigo = Municipio::query()
            ->where('uf', 'RJ')
            ->pluck('id', 'codigo_ibge');

        if ($municipiosPorCodigo->count() !== 92) {
            $this->error('O banco não contém os 92 municípios do RJ.');
            return self::FAILURE;
        }

        $populacoes = [];

        // Conferimos toda a resposta antes de iniciar qualquer gravação.
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
                || strlen($valor) > 14
            ) {
                $this->error("Série {$indice} possui município ou população inválidos.");
                return self::FAILURE;
            }

            if (isset($populacoes[$codigo])) {
                $this->error("Código IBGE duplicado: {$codigo}.");
                return self::FAILURE;
            }

            $populacoes[$codigo] = (int) $valor;
        }

        $codigosBanco = $municipiosPorCodigo->keys()->all();
        $faltantes = array_diff($codigosBanco, array_keys($populacoes));
        $extras = array_diff(array_keys($populacoes), $codigosBanco);

        if ($faltantes !== [] || $extras !== []) {
            $this->error('Os códigos da API não correspondem aos municípios do banco.');
            return self::FAILURE;
        }

        $coletadoEm = now();

        try {
            DB::transaction(function () use (
                $url,
                $populacoes,
                $municipiosPorCodigo,
                $coletadoEm
            ): void {
                $fonte = FonteDado::updateOrCreate(
                    ['codigo' => 'ibge-censo-2022-populacao'],
                    [
                        'nome' => 'IBGE - Censo Demográfico 2022',
                        'url_referencia' => $url . '?localidades=N6%5BN3%5B33%5D%5D',
                    ]
                );

                $registros = [];

                foreach ($populacoes as $codigo => $valor) {
                    $registros[] = [
                        'municipio_id' => $municipiosPorCodigo->get($codigo),
                        'fonte_dado_id' => $fonte->id,
                        'codigo_indicador' => 'populacao_residente',
                        'valor' => $valor,
                        'unidade' => 'Pessoas',
                        'periodo_referencia' => '2022',
                        'versao_fonte' => 'Censo 2022 - agregado 4714 - variavel 93',
                        'coletado_em' => $coletadoEm,
                    ];
                }

                IndicadorMunicipal::upsert(
                    $registros,
                    [
                        'municipio_id',
                        'fonte_dado_id',
                        'codigo_indicador',
                        'periodo_referencia',
                    ],
                    [
                        'valor',
                        'unidade',
                        'versao_fonte',
                        'coletado_em',
                    ]
                );
            });
        } catch (\Throwable $e) {
            report($e);
            $this->error('Falha ao gravar a população. Consulte o log do Laravel.');
            return self::FAILURE;
        }

        $this->info('Importação concluída: 92 populações municipais processadas.');
        return self::SUCCESS;
    }
}
