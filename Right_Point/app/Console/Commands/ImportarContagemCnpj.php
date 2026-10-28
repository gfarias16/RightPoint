<?php

namespace App\Console\Commands;

use App\Models\AtividadeEconomica;
use App\Models\ContagemEstabelecimento;
use App\Models\FonteDado;
use App\Models\Municipio;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;

class ImportarContagemCnpj extends Command
{
    protected $signature = 'cnpj:importar-contagem {--gravar}';

    protected $description = 'Valida a contagem do piloto e grava somente com --gravar.';

    public function handle(): int
    {
        $caminho = storage_path('app/amostras/contagem-marica-5611201.json');

        if (! is_file($caminho) || ! is_readable($caminho)) {
            $this->error('Arquivo da contagem não encontrado ou sem leitura.');

            return self::FAILURE;
        }

        $conteudo = file_get_contents($caminho);

        if ($conteudo === false) {
            $this->error('Não foi possível ler o arquivo.');

            return self::FAILURE;
        }

        try {
            $registros = json_decode($conteudo, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->error('O arquivo não contém um JSON válido.');

            return self::FAILURE;
        }

        if (
            ! is_array($registros)
            || ! array_is_list($registros)
            || count($registros) !== 1
            || ! is_array($registros[0])
        ) {
            $this->error('Esperada uma lista JSON com exatamente um agregado.');

            return self::FAILURE;
        }

        $campos = [
            'registros_no_recorte',
            'cnpjs_distintos',
            'cnpjs_ausentes',
        ];

        $contagens = [];

        foreach ($campos as $campo) {
            $valor = $registros[0][$campo] ?? null;

            // O BigQuery exportou as contagens como texto decimal.
            if (
                ! is_string($valor)
                || preg_match('/^(0|[1-9][0-9]{0,9})$/D', $valor) !== 1
            ) {
                $this->error("Campo {$campo}: esperada uma contagem textual válida.");

                return self::FAILURE;
            }

            $numero = filter_var($valor, FILTER_VALIDATE_INT, [
                'options' => [
                    'min_range' => 0,
                    'max_range' => 4294967295,
                ],
            ]);

            if ($numero === false) {
                $this->error("Campo {$campo}: valor fora do limite da coluna.");

                return self::FAILURE;
            }

            $contagens[$campo] = $numero;
        }

        if (
            $contagens['cnpjs_ausentes'] !== 0
            || $contagens['cnpjs_distintos'] !== $contagens['registros_no_recorte']
        ) {
            $this->error('Agregado com ausências, duplicatas ou contagens inconsistentes.');

            return self::FAILURE;
        }

        // Reconhece a cópia conferida, tolerando diferenças de quebra de linha.
        $conteudoNormalizado = str_replace(["\r\n", "\r"], "\n", $conteudo);

        $hashEsperado = '8fa2f578f4a10f8c347ee1a49558313bd781841b77b920b6f821e6b8e64b7bff';

        if (hash('sha256', $conteudoNormalizado) !== $hashEsperado) {
            $this->error('O arquivo difere da cópia conferida deste piloto.');

            return self::FAILURE;
        }

        $this->table(
            ['Controle', 'Quantidade'],
            [
                ['Registros no recorte', $contagens['registros_no_recorte']],
                ['CNPJs distintos', $contagens['cnpjs_distintos']],
                ['CNPJs ausentes', $contagens['cnpjs_ausentes']],
            ]
        );


        $codigoIbge = config('municipios_receita.RJ:5853');

        if (
            ! is_string($codigoIbge)
            || preg_match('/^33[0-9]{5}$/D', $codigoIbge) !== 1
        ) {
            $this->error('Correspondência municipal do piloto não configurada.');

            return self::FAILURE;
        }

        try {
            $municipio = Municipio::query()
                ->where('codigo_ibge', $codigoIbge)
                ->where('uf', 'RJ')
                ->first();

            $atividade = AtividadeEconomica::query()
                ->where('codigo_cnae', '5611201')
                ->first();
        } catch (QueryException $erro) {
            report($erro);
            $this->error('Não foi possível consultar os catálogos locais.');

            return self::FAILURE;
        }

        if ($municipio === null || $atividade === null) {
            $this->error('Município ou atividade não cadastrado neste banco.');

            return self::FAILURE;
        }

        $this->line('Município: ' . $municipio->nome);
        $this->line('CNAE principal: ' . $atividade->codigo_cnae);
        $this->warn('Período e versão da base ainda não confirmados; ficarão sem preenchimento.');

        if (! $this->option('gravar')) {
            $this->info('Simulação concluída. Nenhum dado foi gravado.');

            return self::SUCCESS;
        }

        try {
            $resultado = DB::transaction(function () use (
                $municipio,
                $atividade,
                $contagens
            ): string {
                $fonte = FonteDado::firstOrCreate(
                    ['codigo' => 'opencnpj-receita'],
                    [
                        'nome' => 'OpenCNPJ - dados da Receita Federal',
                        'url_referencia' => 'https://opencnpj.org/',
                    ]
                );

                // Serializa as gravações deste importador pela mesma fonte.
                $fonte = FonteDado::query()
                    ->whereKey($fonte->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $chave = [
                    'municipio_id' => $municipio->id,
                    'atividade_economica_id' => $atividade->id,
                    'fonte_dado_id' => $fonte->id,
                    'criterio' => 'ativos_cnae_principal_exato_v1',

                    // Job: 28/10/2026 às 12:08:25 UTC−3, convertido para UTC.
                    'consultado_em' => '2026-10-28 15:08:25',
                ];

                $valores = [
                    'quantidade' => $contagens['cnpjs_distintos'],
                    'registros_no_recorte' => $contagens['registros_no_recorte'],
                    'cnpjs_ausentes' => $contagens['cnpjs_ausentes'],
                    'periodo_referencia' => null,
                    'versao_fonte' => null,
                ];

                $existente = ContagemEstabelecimento::query()
                    ->where($chave)
                    ->lockForUpdate()
                    ->first();

                if ($existente !== null) {
                    foreach (
                        ['quantidade', 'registros_no_recorte', 'cnpjs_ausentes']
                        as $campo
                    ) {
                        if ($existente->{$campo} !== $valores[$campo]) {
                            throw new DomainException(
                                'Já existe esta coleta com valores diferentes. Nada foi sobrescrito.'
                            );
                        }
                    }

                    return 'Coleta já cadastrada. Nenhuma alteração realizada.';
                }

                ContagemEstabelecimento::create($chave + $valores);

                return 'Importação concluída: uma coleta com 620 estabelecimentos.';
            }, 3);
        } catch (DomainException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        } catch (Throwable $erro) {
            report($erro);
            $this->error('Falha ao gravar. A transação foi desfeita; consulte o log.');

            return self::FAILURE;
        }

        $this->info($resultado);

        return self::SUCCESS;
    }
}