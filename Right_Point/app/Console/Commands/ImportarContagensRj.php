<?php

namespace App\Console\Commands;

use App\Models\ContagemEstabelecimento;
use App\Models\FonteDado;
use App\Services\ValidadorContagensRj;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

class ImportarContagensRj extends Command
{
    protected $signature = 'cnpj:importar-contagens-rj {arquivo : JSON do lote conferido} {--gravar : Grava o lote validado no banco local}';

    protected $description = 'Simula ou importa a coleta conferida dos 92 municípios RJ/CNAE 5611201.';

    private const CRITERIO = 'ativos_cnae_principal_exato_v1';

    public function handle(ValidadorContagensRj $validador): int
    {
        try {
            $consultadoEm = $this->validarMetadados();
            $lote = $validador->validar($this->argument('arquivo'));

            // Vincula o conteúdo revisado ao job, não ao nome ou horário do arquivo.
            if ($lote['sha256'] !== config('contagem_cnpj_rj.sha256_lf')) {
                throw new DomainException('O arquivo difere do lote conferido. Não será associado ao job configurado.');
            }
        } catch (JsonException) {
            $this->error('O arquivo não contém um JSON válido.');

            return self::FAILURE;
        } catch (DomainException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        } catch (QueryException) {
            $this->error('Não foi possível consultar os catálogos locais. Confira banco e migrations.');

            return self::FAILURE;
        }

        $this->line('Municípios preparados: '.count($lote['linhas']));
        $this->line('Soma das contagens municipais: '.$lote['total']);
        $this->line('Coleta (UTC): '.$consultadoEm);
        $this->warn('Período e versão desconhecidos permanecem NULL. Contagem da base não é chance de sucesso.');

        if (! $this->option('gravar')) {
            $this->info('Simulação concluída. Nenhum dado foi gravado.');

            return self::SUCCESS;
        }

        try {
            $resultado = DB::transaction(function () use ($lote, $consultadoEm): array {
                $fonte = FonteDado::firstOrCreate(
                    ['codigo' => 'opencnpj-receita'],
                    ['nome' => 'OpenCNPJ - dados da Receita Federal', 'url_referencia' => 'https://opencnpj.org/']
                );

                // Mesmo bloqueio do importador piloto: serializa escritores cooperantes.
                $fonte = FonteDado::query()->whereKey($fonte->id)->lockForUpdate()->firstOrFail();
                $chaveComum = [
                    'atividade_economica_id' => $lote['atividade_id'],
                    'fonte_dado_id' => $fonte->id,
                    'criterio' => self::CRITERIO,
                    'consultado_em' => $consultadoEm,
                ];

                $existentes = ContagemEstabelecimento::query()
                    ->where($chaveComum)
                    ->whereIn('municipio_id', array_column($lote['linhas'], 'municipio_id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('municipio_id');

                // Confere divergências em todo o lote antes de inserir qualquer coleta.
                foreach ($lote['linhas'] as $linha) {
                    $existente = $existentes->get($linha['municipio_id']);

                    if ($existente === null) {
                        continue;
                    }

                    foreach (['quantidade', 'registros_no_recorte', 'cnpjs_ausentes'] as $campo) {
                        if ($existente->{$campo} !== $linha[$campo]) {
                            throw new DomainException('Coleta divergente para IBGE '.$linha['codigo_ibge'].'. Nenhum valor foi sobrescrito; transação desfeita.');
                        }
                    }
                }

                $criadas = 0;

                foreach ($lote['linhas'] as $linha) {
                    if ($existentes->has($linha['municipio_id'])) {
                        continue;
                    }

                    ContagemEstabelecimento::create($chaveComum + [
                        'municipio_id' => $linha['municipio_id'],
                        'quantidade' => $linha['quantidade'],
                        'registros_no_recorte' => $linha['registros_no_recorte'],
                        'cnpjs_ausentes' => $linha['cnpjs_ausentes'],
                        'periodo_referencia' => null,
                        'versao_fonte' => null,
                    ]);
                    $criadas++;
                }

                return ['criadas' => $criadas, 'existentes' => $existentes->count()];
            }, 3);
        } catch (DomainException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        } catch (Throwable $erro) {
            // Registra contexto sem SQL, parâmetros ou detalhes da conexão.
            Log::error('Falha na importação do lote CNPJ RJ; transação desfeita.', [
                'operacao' => 'cnpj:importar-contagens-rj',
                'tipo_erro' => $erro::class,
            ]);
            $this->error('Falha ao gravar o lote. A transação foi desfeita; confira migrations e o log local.');

            return self::FAILURE;
        }

        $this->info($resultado['criadas'] === 0
            ? 'Lote já cadastrado. Nenhuma alteração realizada.'
            : 'Importação concluída: '.$resultado['criadas'].' novas coletas; '.$resultado['existentes'].' já cadastradas.');

        return self::SUCCESS;
    }

    private function validarMetadados(): string
    {
        $hash = config('contagem_cnpj_rj.sha256_lf');
        $instante = config('contagem_cnpj_rj.consultado_em_utc');

        if (! is_string($hash) || preg_match('/^[a-f0-9]{64}$/D', $hash) !== 1) {
            throw new DomainException('Hash da coleta não configurado corretamente.');
        }

        if (! is_string($instante) || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D', $instante) !== 1) {
            throw new DomainException('Instante UTC da coleta não configurado corretamente.');
        }

        $data = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $instante, new DateTimeZone('UTC'));

        if ($data === false || $data->format('Y-m-d H:i:s') !== $instante) {
            throw new DomainException('Instante UTC da coleta não configurado corretamente.');
        }

        return $instante;
    }
}
