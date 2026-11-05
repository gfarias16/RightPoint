<?php

namespace App\Console\Commands;

use App\Models\FonteDado;
use App\Models\IndicadorMunicipal;
use App\Services\ConsultaRendaIbge;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportarRendaRj extends Command
{
    protected $signature = 'ibge:importar-renda-rj {--gravar : Grava os 92 valores após validar metadados e recorte}';

    protected $description = 'Consulta e simula a renda domiciliar per capita média nominal mensal do Censo 2022 no RJ.';

    public function handle(ConsultaRendaIbge $consulta): int
    {
        try {
            $linhas = $consulta->consultar();
        } catch (DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (ConnectionException) {
            $this->error('Não foi possível conectar ao IBGE com TLS verificado. Confira a rede e o pacote CA local.');

            return self::FAILURE;
        } catch (Throwable $e) {
            Log::error('Falha ao conferir renda municipal.', ['operacao' => $this->getName(), 'tipo_erro' => $e::class]);
            $this->error('Não foi possível conferir renda e catálogo local. Nenhum dado foi gravado.');

            return self::FAILURE;
        }

        $coletadoEm = now('UTC');
        $this->info('Municípios preparados: '.count($linhas));
        $this->line('Indicador: rendimento nominal médio mensal domiciliar per capita.');
        $this->line('Referência: julho de 2022 | Unidade: Reais por mês por pessoa.');
        $this->line('Tabela 10295 / variável 13431 / totais de sexo, cor ou raça e idade.');
        $this->line('Coleta (UTC): '.$coletadoEm->format('Y-m-d H:i:s'));
        $this->warn('Resultados da amostra do Censo 2022; a média não é salário individual nem gasto disponível para o negócio.');
        $this->warn('A identificação da tabela/variável não comprova a revisão oficial da base. Nenhum score é calculado.');

        $exemplos = array_filter($linhas, fn ($linha) => in_array($linha['codigo_ibge'], ['3300407', '3302700', '3303302'], true));
        $this->table(['Código IBGE', 'Renda média mensal por pessoa (R$ de 2022)'], array_map(
            fn ($linha) => [$linha['codigo_ibge'], number_format((float) $linha['valor'], 2, ',', '.')],
            array_values($exemplos)
        ));

        if (! $this->option('gravar')) {
            $this->info('Simulação concluída. Nenhum dado foi gravado.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($linhas, $coletadoEm): void {
                $fonte = FonteDado::firstOrCreate(
                    ['codigo' => ConsultaRendaIbge::CODIGO_FONTE],
                    ['nome' => 'IBGE - Censo 2022 - rendimento domiciliar per capita (amostra)', 'url_referencia' => ConsultaRendaIbge::urlValores()]
                );
                $fonte = FonteDado::whereKey($fonte->id)->lockForUpdate()->firstOrFail();
                $registros = array_map(fn ($linha) => [
                    'municipio_id' => $linha['municipio_id'],
                    'fonte_dado_id' => $fonte->id,
                    'codigo_indicador' => ConsultaRendaIbge::CODIGO_INDICADOR,
                    'valor' => $linha['valor'],
                    'unidade' => 'Reais',
                    'periodo_referencia' => '2022',
                    'versao_fonte' => ConsultaRendaIbge::VERSAO_RECORTE,
                    'coletado_em' => $coletadoEm,
                ], $linhas);

                // Mesmo registro por município/fonte/indicador/período; outras fontes/períodos ficam preservados.
                IndicadorMunicipal::upsert($registros,
                    ['municipio_id', 'fonte_dado_id', 'codigo_indicador', 'periodo_referencia'],
                    ['valor', 'unidade', 'versao_fonte', 'coletado_em']
                );
            }, 3);
        } catch (Throwable $e) {
            Log::error('Falha ao gravar renda municipal; transação desfeita.', ['operacao' => $this->getName(), 'tipo_erro' => $e::class]);
            $this->error('Falha na gravação. Transação desfeita; confira banco e log local.');

            return self::FAILURE;
        }

        $this->info('Importação concluída: 92 rendimentos municipais processados.');

        return self::SUCCESS;
    }
}
