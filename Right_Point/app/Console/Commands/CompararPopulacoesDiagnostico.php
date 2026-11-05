<?php

namespace App\Console\Commands;

use App\Models\AtividadeEconomica;
use App\Models\IndicadorMunicipal;
use App\Services\CalculadorScoreRelativo;
use App\Services\DiagnosticoPiloto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CompararPopulacoesDiagnostico extends Command
{
    protected $signature = 'diagnostico:comparar-populacoes {cnae=5611201} {--json : Emite entradas, metadados e resultados sem escrever arquivo}';

    protected $description = 'Compara Censo 2022 e estimativa 2026 com pesos 50/50, sem mudar a consulta ou o banco';

    public function handle(DiagnosticoPiloto $piloto, CalculadorScoreRelativo $calculador): int
    {
        try {
            $atividade = AtividadeEconomica::where('codigo_cnae', $this->argument('cnae'))->first();
            $base = $piloto->prepararConjunto($atividade);
            if ($base['diagnostico']['estado'] !== 'apto') {
                $this->error(implode(' ', $base['diagnostico']['motivos']));

                return self::FAILURE;
            }
            $estimativas = IndicadorMunicipal::with('municipio', 'fonteDado')
                ->where('codigo_indicador', 'populacao_estimada')->where('periodo_referencia', '2026')->where('unidade', 'Pessoas')
                ->where('versao_fonte', 'Tabela 6579 / variável 9324 / recorte RJ v1')
                ->whereHas('fonteDado', fn ($q) => $q->where('codigo', 'ibge-estimativas-populacao'))->get()->keyBy('municipio_id');
            $fatores = [];
            $entradas = [];
            foreach ($base['linhas'] as $id => $linha) {
                $e = $estimativas->get($id);
                if ($e === null || ! is_numeric($e->valor) || ! is_finite((float) $e->valor) || $e->valor <= 0) {
                    $this->error('Comparação bloqueada: faltam estimativas compatíveis e positivas para os 92 municípios.');

                    return self::FAILURE;
                }
                $fatores['censo'][$id] = $linha['fatores'];
                $fatores['estimativa'][$id] = ['renda' => $linha['fatores']['renda'], 'oferta' => $linha['contagem']->quantidade / (float) $e->valor * 10000];
                $entradas[] = ['codigo_ibge' => $linha['codigo_ibge'], 'nome' => $linha['nome'],
                    'populacao_censo' => $linha['populacao']->toArray(), 'estimativa' => $e->toArray(),
                    'renda' => $linha['renda']->toArray(), 'contagem' => $linha['contagem']->toArray()];
            }
            $notas = [];
            $linhas = [];
            foreach ($base['linhas'] as $id => $linha) {
                foreach ($fatores as $cenario => $conjunto) {
                    $notas[$cenario][$id] = round($calculador->calcular($conjunto, $id)['score_sem_arredondamento'], 10);
                }
            }
            foreach ($base['linhas'] as $id => $linha) {
                $r = ['codigo_ibge' => $linha['codigo_ibge'], 'nome' => $linha['nome']];
                foreach ($notas as $cenario => $valores) {
                    $maiores = count(array_filter($valores, fn ($n) => $n > $valores[$id]));
                    $iguais = count(array_filter($valores, fn ($n) => $n === $valores[$id]));
                    $r[$cenario] = ['score' => round($valores[$id], 1), 'posicao' => 1 + $maiores + ($iguais - 1) / 2,
                        'estabelecimentos_por_10_mil' => $fatores[$cenario][$id]['oferta']];
                }
                $r['deslocamento'] = $r['estimativa']['posicao'] - $r['censo']['posicao'];
                $linhas[] = $r;
            }
            $relatorio = ['metodo_base' => $base['diagnostico'], 'cnae' => $atividade->codigo_cnae, 'entradas' => $entradas, 'resultados' => $linhas,
                'aviso' => 'Cenário exploratório: renda 2022 fixa, denominador 2022 versus estimativa 2026, CNPJ 2026 assumido. Não prova melhoria, demanda ou sucesso. Consulta permanece Censo 2022 e 50/50.'];
            $relatorio['sha256'] = hash('sha256', json_encode($relatorio, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            if ($this->option('json')) {
                $this->line(json_encode($relatorio, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->table(['Município', 'Nota Censo', 'Nota estimativa', 'Posição Censo', 'Posição estimativa', 'Deslocamento'],
                    array_map(fn ($r) => [$r['nome'], $r['censo']['score'], $r['estimativa']['score'], $r['censo']['posicao'], $r['estimativa']['posicao'], $r['deslocamento']], $linhas));
                $this->line('Posições alteradas: '.count(array_filter($linhas, fn ($r) => $r['deslocamento'] != 0)).' / 92. SHA-256: '.$relatorio['sha256']);
                $this->warn($relatorio['aviso']);
                $this->line('Somente leitura. Use --json para preservar todas as entradas e resultados.');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::warning('Comparação populacional bloqueada.', ['tipo_erro' => $e::class]);
            $this->error('Não foi possível ler ou validar a comparação. Confira as cargas compatíveis e o log local.');

            return self::FAILURE;
        }
    }
}
