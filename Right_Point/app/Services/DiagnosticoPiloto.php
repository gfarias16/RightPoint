<?php

namespace App\Services;

use App\Models\AtividadeEconomica;
use App\Models\ContagemEstabelecimento;
use App\Models\IndicadorMunicipal;
use App\Models\Municipio;

class DiagnosticoPiloto
{
    public const METODO = 'piloto-rj-censo2022-cnpj2026-v1';

    public const COLETA_CNPJ = '2026-11-03 20:41:41';

    public const SHA256_ARQUIVO_CNPJ = 'c6f18525e52c489384b8feaa9c6e700ccdf60acef3ee1b682facece2db6ce3b1';

    public const CNAES = ['5611201', '1091102', '4721102', '9602501'];

    public function __construct(private CalculadorScoreRelativo $calculador) {}

    public function consultar(Municipio $municipio, ?AtividadeEconomica $atividade, ?IndicadorMunicipal $populacao, ?IndicadorMunicipal $renda, ?ContagemEstabelecimento $contagem): array
    {
        $conjunto = $this->prepararConjunto($atividade);
        $resultado = $conjunto['diagnostico'];
        if ($resultado['estado'] !== 'apto') {
            return $resultado;
        }
        $linha = $conjunto['linhas'][$municipio->id] ?? null;
        if ($linha === null || $populacao?->id !== $linha['populacao']->id
            || $renda?->id !== $linha['renda']->id || $contagem?->id !== $linha['contagem']->id
            || (float) $populacao?->valor !== (float) $linha['populacao']->valor
            || (float) $renda?->valor !== (float) $linha['renda']->valor
            || $contagem?->quantidade !== $linha['contagem']->quantidade) {
            $resultado['estado'] = 'indisponivel';
            $resultado['motivos'][] = 'Os indicadores exibidos diferem do recorte definido para esta nota. A comparação precisa ser atualizada antes de apresentar o resultado.';

            return $resultado;
        }
        $calculo = $this->calculador->calcular(array_column($conjunto['linhas'], 'fatores', 'id'), $municipio->id);
        $resultado['estado'] = 'experimental';
        $resultado['score'] = $calculo['score'];
        $resultado['fatores'] = $calculo['fatores'];

        return $resultado;
    }

    /** Compartilha o mesmo recorte entre a consulta e a análise de sensibilidade, sem reler cidade por cidade. */
    public function prepararConjunto(?AtividadeEconomica $atividade): array
    {
        $resultado = [
            'estado' => 'indisponivel',
            'metodo' => self::METODO,
            'score' => null,
            'fatores' => null,
            'motivos' => [],
            'conjunto' => ['uf' => 'RJ', 'cnae' => $atividade?->codigo_cnae, 'municipios_esperados' => 92, 'municipios_aptos' => 0],
            'referencias' => ['populacao' => 'Censo 2022', 'renda' => 'Nominal mensal, julho/2022', 'cnpj' => '2026 assumido pelo projeto'],
            'premissa_cnpj' => [
                'ano_assumido' => 2026,
                'confirmado_pela_fonte' => false,
                'decisao' => 'Usuário, em 04/11/2026; somente para este experimento.',
                'coleta_utc' => self::COLETA_CNPJ,
                'sha256_arquivo_lf' => self::SHA256_ARQUIVO_CNPJ,
            ],
        ];

        if ($atividade === null || ! in_array($atividade->codigo_cnae, self::CNAES, true)) {
            $resultado['motivos'][] = 'A nota está disponível para restaurantes, as duas subclasses de padarias e o grupo de cabeleireiros, manicure e pedicure.';

            return ['diagnostico' => $resultado, 'linhas' => []];
        }

        // O mapeamento conferido fixa os códigos: não basta encontrar quaisquer 92 linhas.
        $codigos = array_values(config('municipios_receita', []));
        sort($codigos, SORT_STRING);
        $municipios = Municipio::query()->where('uf', 'RJ')->orderBy('codigo_ibge')->get();
        if (count($codigos) !== 92 || count(array_unique($codigos)) !== 92 || $municipios->pluck('codigo_ibge')->all() !== $codigos) {
            $resultado['motivos'][] = 'A comparação exige os 92 códigos municipais do RJ. A base local está incompleta ou divergente.';

            return ['diagnostico' => $resultado, 'linhas' => []];
        }

        $ids = $municipios->modelKeys();
        $populacoes = IndicadorMunicipal::query()->whereIn('municipio_id', $ids)
            ->where('codigo_indicador', 'populacao_residente')->where('periodo_referencia', '2022')
            ->where('unidade', 'Pessoas')->where('versao_fonte', 'Censo 2022 - agregado 4714 - variavel 93')
            ->whereHas('fonteDado', fn ($q) => $q->where('codigo', 'ibge-censo-2022-populacao'))
            ->get()->keyBy('municipio_id');
        $rendas = IndicadorMunicipal::query()->whereIn('municipio_id', $ids)
            ->where('codigo_indicador', ConsultaRendaIbge::CODIGO_INDICADOR)->where('periodo_referencia', '2022')
            ->where('unidade', 'Reais')->where('versao_fonte', ConsultaRendaIbge::VERSAO_RECORTE)
            ->whereHas('fonteDado', fn ($q) => $q->where('codigo', ConsultaRendaIbge::CODIGO_FONTE))
            ->get()->keyBy('municipio_id');
        $coletas = ContagemEstabelecimento::query()->whereIn('municipio_id', $ids)
            ->where('atividade_economica_id', $atividade->id)->where('criterio', 'ativos_cnae_principal_exato_v1')
            ->where('consultado_em', self::COLETA_CNPJ)
            ->whereNull('periodo_referencia')->whereNull('versao_fonte')
            ->whereHas('fonteDado', fn ($q) => $q->where('codigo', 'opencnpj-receita'))
            ->get()->keyBy('municipio_id');

        $fatores = [];
        $linhas = [];
        $manifesto = [];
        foreach ($municipios as $cidade) {
            $p = $populacoes->get($cidade->id);
            $r = $rendas->get($cidade->id);
            $c = $coletas->get($cidade->id);
            if ($p === null || $r === null || $c === null
                || ! is_numeric($p->valor) || ! is_finite((float) $p->valor) || $p->valor <= 0
                || ! is_numeric($r->valor) || ! is_finite((float) $r->valor) || $r->valor < 0
                || $c->quantidade < 0 || $c->cnpjs_ausentes !== 0 || $c->registros_no_recorte < $c->quantidade) {
                continue;
            }

            // Classificar pela razão original; arredondar antes poderia criar empates artificiais.
            $oferta = $c->quantidade / (float) $p->valor * 10000;
            if (! is_finite($oferta)) {
                continue;
            }
            $fatores[$cidade->id] = ['renda' => (float) $r->valor, 'oferta' => $oferta];
            $linhas[$cidade->id] = ['id' => $cidade->id, 'codigo_ibge' => $cidade->codigo_ibge, 'nome' => $cidade->nome,
                'populacao' => $p, 'renda' => $r, 'contagem' => $c, 'fatores' => $fatores[$cidade->id]];
            $manifesto[] = [$cidade->codigo_ibge, (string) $p->valor, $p->coletado_em, (string) $r->valor, $r->coletado_em, $c->quantidade, $c->registros_no_recorte];
        }

        $resultado['conjunto']['municipios_aptos'] = count($fatores);
        if (count($fatores) !== 92) {
            $resultado['motivos'][] = 'Há '.count($fatores).' de 92 municípios com indicadores compatíveis e válidos. A nota ficará indisponível até completar a base de comparação.';

            return ['diagnostico' => $resultado, 'linhas' => []];
        }
        $resultado['estado'] = 'apto';
        $resultado['conjunto']['sha256_dados'] = hash('sha256', json_encode([self::METODO, $atividade->codigo_cnae, $manifesto], JSON_THROW_ON_ERROR));
        $resultado['conjunto']['codigos_ibge'] = $codigos;

        return ['diagnostico' => $resultado, 'linhas' => $linhas];
    }
}
