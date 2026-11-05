<?php

namespace App\Console\Commands;

use App\Models\FonteDado;
use App\Models\IndicadorMunicipal;
use App\Services\ConsultaContextoIbge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportarContextoIbge extends Command
{
    protected $signature = 'ibge:importar-contexto-rj {tipo : estimativa ou idades} {--ano=} {--gravar}';

    protected $description = 'Valida contexto municipal do IBGE; simula por padrão e preserva os fatores do score atual';

    public function handle(ConsultaContextoIbge $consulta): int
    {
        try {
            $tipo = $this->argument('tipo');
            $lote = $consulta->consultar($tipo, $this->option('ano') ?: ($tipo === 'idades' ? '2022' : '2026'));
            $this->info(count($lote['linhas']).' indicadores validados; ano '.$lote['ano'].'.');
            if (! $this->option('gravar')) {
                $this->line('Simulação: nenhum dado gravado; score e Censo atuais preservados.');

                return self::SUCCESS;
            }
            DB::transaction(function () use ($lote) {
                $fonte = FonteDado::firstOrCreate(['codigo' => $lote['fonte']], ['nome' => $lote['nome'], 'url_referencia' => $lote['url']]);
                $agora = now('UTC');
                $linhas = array_map(fn ($linha) => $linha + ['fonte_dado_id' => $fonte->id, 'unidade' => 'Pessoas',
                    'periodo_referencia' => $lote['ano'], 'versao_fonte' => $lote['versao'], 'coletado_em' => $agora], $lote['linhas']);
                IndicadorMunicipal::upsert($linhas, ['municipio_id', 'fonte_dado_id', 'codigo_indicador', 'periodo_referencia'], ['valor', 'unidade', 'versao_fonte', 'coletado_em']);
            });
            $this->info('Contexto importado. Não altera o método 50/50 nem substitui população Censo 2022.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::warning('Falha ao importar contexto IBGE.', ['tipo_erro' => $e::class]);
            $this->error($e instanceof \DomainException ? $e->getMessage() : 'Não foi possível consultar/validar/gravar o contexto. Confira rede, certificado e banco.');

            return self::FAILURE;
        }
    }
}
