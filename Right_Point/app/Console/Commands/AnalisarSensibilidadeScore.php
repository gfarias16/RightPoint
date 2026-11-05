<?php

namespace App\Console\Commands;

use App\Models\AtividadeEconomica;
use App\Services\AnaliseSensibilidadeScore;
use App\Services\DiagnosticoPiloto;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class AnalisarSensibilidadeScore extends Command
{
    protected $signature = 'diagnostico:analisar-sensibilidade {--markdown : Emite relatório completo em Markdown, sem escrever arquivos}';

    protected $description = 'Compara pesos 30/70, 50/50 e 70/30 nos quatro CNAEs, somente com leitura do banco local';

    public function handle(DiagnosticoPiloto $diagnostico, AnaliseSensibilidadeScore $analise): int
    {
        $relatorios = [];
        try {
            $atividades = AtividadeEconomica::whereIn('codigo_cnae', DiagnosticoPiloto::CNAES)->get()->keyBy('codigo_cnae');
            foreach (DiagnosticoPiloto::CNAES as $cnae) {
                $conjunto = $diagnostico->prepararConjunto($atividades->get($cnae));
                if ($conjunto['diagnostico']['estado'] !== 'apto') {
                    $this->error('Análise bloqueada para o CNAE '.$cnae.': '.implode(' ', $conjunto['diagnostico']['motivos']));

                    return self::FAILURE;
                }
                $relatorios[$cnae] = ['nome' => $atividades->get($cnae)->nome,
                    'metadados' => $conjunto['diagnostico'], 'analise' => $analise->analisar($conjunto['linhas'])];
            }
        } catch (QueryException $e) {
            Log::error('Falha na leitura para análise de sensibilidade.', ['operacao' => 'sensibilidade_score', 'sqlstate' => $e->getCode()]);
            $this->error('Não foi possível ler os dados locais para a análise. Confira a conexão e as migrations.');

            return self::FAILURE;
        }

        if ($this->option('markdown')) {
            $this->output->write($this->markdown($relatorios));
        } else {
            $this->info('Sensibilidade dos pesos — método '.DiagnosticoPiloto::METODO);
            foreach ($relatorios as $cnae => $relatorio) {
                $this->line('CNAE '.$cnae.' — '.$relatorio['nome']);
                $this->table(['Renda/oferta', 'Posições alteradas / 92', 'Maior deslocamento', 'Deslocamento médio'],
                    $this->resumo($relatorio['analise']));
                $this->line('Conjunto SHA-256: '.$relatorio['metadados']['conjunto']['sha256_dados']);
            }
            $this->line('Nenhum dado, peso da aplicação ou análise foi gravado. Use --markdown para emitir o relatório completo.');
            $this->line('Mudança de posição mede sensibilidade ao método; não comprova chance de sucesso.');
        }

        return self::SUCCESS;
    }

    private function resumo(array $analise): array
    {
        return array_map(fn ($c) => [$c['peso_renda'].'/'.$c['peso_menor_oferta'],
            $c['municipios_com_posicao_alterada'], $c['maior_deslocamento_da_base'], $c['deslocamento_medio_da_base']], array_values($analise['cenarios']));
    }

    private function markdown(array $relatorios): string
    {
        $texto = '# Sensibilidade dos pesos do score — RJ'."\n\n";
        $texto .= 'Gerado em '.now('UTC')->format('Y-m-d H:i:s').' UTC. Método: `'.DiagnosticoPiloto::METODO.'`.'."\n\n";
        $texto .= "Comparação local dos mesmos 92 municípios por CNAE. Apenas os pesos mudam; a consulta da aplicação continua 50/50. População/renda: Censo 2022. CNPJ: 2026 assumido para a fotografia definida, sem confirmação da competência pela fonte. Nenhum dado do banco é alterado.\n\n";
        $texto .= "Posições são decrescentes por score sem arredondamento visual, com empates pela posição média. Arredondamento interno a dez casas remove ruído de ponto flutuante. A ordem das linhas prioriza maior amplitude de posições; código IBGE só ordena a apresentação em caso de empate. Deslocamentos comparam cada cenário com 50/50; amplitudes são a diferença entre máximo e mínimo dos três cenários. Notas aparecem com uma casa.\n\n";
        $texto .= "Mudanças não validam pesos nem eficácia comercial. Não foi adotado limiar de estabilidade ou confiança. Este relatório registra resultados e hashes, mas não contém todas as entradas de uma análise histórica.\n\n";
        foreach ($relatorios as $cnae => $relatorio) {
            $texto .= '## '.$cnae.' — '.$this->celula($relatorio['nome'])."\n\n";
            $texto .= 'Conjunto SHA-256: `'.$relatorio['metadados']['conjunto']['sha256_dados'].'`.'."\n\n";
            $texto .= "| Renda / menor oferta | Posições alteradas / 92 | Maior deslocamento da base | Deslocamento médio da base |\n| --- | ---: | ---: | ---: |\n";
            foreach ($this->resumo($relatorio['analise']) as $linha) {
                $texto .= '| '.implode(' | ', array_map(fn ($v) => is_numeric($v) ? $this->numero($v) : $v, $linha))." |\n";
            }
            $texto .= "\n| Município | IBGE | Nota 30/70 | Nota 50/50 | Nota 70/30 | Posição 30/70 | Posição 50/50 | Posição 70/30 | Amplitude em pontos | Amplitude em posições |\n| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |\n";
            foreach ($relatorio['analise']['municipios'] as $m) {
                $linha = [$this->celula($m['nome']), $m['codigo_ibge']];
                foreach (array_keys(AnaliseSensibilidadeScore::PESOS_RENDA) as $c) {
                    $linha[] = $this->numero($m['notas'][$c], 1);
                }
                foreach (array_keys(AnaliseSensibilidadeScore::PESOS_RENDA) as $c) {
                    $linha[] = $this->numero($m['posicoes'][$c]);
                }
                $linha[] = $this->numero($m['amplitude_pontos'], 1);
                $linha[] = $this->numero($m['amplitude_posicoes']);
                $texto .= '| '.implode(' | ', $linha)." |\n";
            }
            $texto .= "\n";
        }

        return $texto;
    }

    private function numero(int|float $valor, int $casas = 2): string
    {
        $texto = number_format($valor, $casas, ',', '.');

        return $casas === 2 ? rtrim(rtrim($texto, '0'), ',') : $texto;
    }

    private function celula(string $texto): string
    {
        return str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], $texto);
    }
}
