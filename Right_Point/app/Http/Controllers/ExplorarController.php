<?php

namespace App\Http\Controllers;

use App\Models\AtividadeEconomica;
use App\Models\ContagemEstabelecimento;
use App\Models\IndicadorMunicipal;
use App\Models\Municipio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExplorarController extends Controller
{
    public function index(): View
    {
        $municipios = Municipio::query()
            ->where('uf', 'RJ')
            ->orderBy('nome')
            ->get(['codigo_ibge', 'nome']);

        return view('explorar', ['municipios' => $municipios]);
    }

    public function buscarCnaes(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'busca' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $busca = trim($dados['busca']);

        $atividades = AtividadeEconomica::query()
            ->where(function ($query) use ($busca) {
                $query->where('nome', 'like', '%' . $busca . '%')
                    ->orWhere('codigo_cnae', 'like', $busca . '%');
            })
            ->orderBy('nome')
            ->limit(20)
            ->get(['codigo_cnae', 'nome']);

        return response()->json($atividades);
    }

    public function consultar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'codigo_ibge' => [
                'required',
                'regex:/^33[0-9]{5}$/',
                Rule::exists('municipios', 'codigo_ibge')->where('uf', 'RJ'),
            ],
            'cnae' => ['required', 'regex:/^[0-9]{7}$/'],
        ]);

        $municipio = Municipio::query()
            ->where('codigo_ibge', $dados['codigo_ibge'])
            ->firstOrFail();

        $atividade = AtividadeEconomica::query()
            ->where('codigo_cnae', $dados['cnae'])
            ->first();

        $populacao = IndicadorMunicipal::query()
            ->with('fonteDado')
            ->where('municipio_id', $municipio->id)
            ->where('codigo_indicador', 'populacao_residente')
            ->where('periodo_referencia', '2022')
            ->orderByDesc('coletado_em')
            ->first();

        // Não mistura fontes ou critérios diferentes na leitura do piloto.
        $contagem = $atividade === null ? null : ContagemEstabelecimento::query()
            ->with('fonteDado')
            ->where('municipio_id', $municipio->id)
            ->where('atividade_economica_id', $atividade->id)
            ->where('criterio', 'ativos_cnae_principal_exato_v1')
            ->whereHas('fonteDado', fn ($query) => $query->where('codigo', 'opencnpj-receita'))
            ->orderByDesc('consultado_em')
            ->orderByDesc('id')
            ->first();

        $avisos = [];

        if ($atividade === null) {
            $avisos[] = 'O CNAE informado ainda não foi conferido em um catálogo carregado no sistema.';
        }

        if ($populacao === null) {
            $avisos[] = 'A população municipal ainda não foi importada para este município.';
        }

        if ($contagem === null) {
            $avisos[] = 'Não há coleta de estabelecimentos disponível para este município e CNAE. Dado indisponível não significa zero concorrentes.';
        } else {
            $avisos[] = 'A contagem inclui apenas CNPJs ativos com CNAE principal exato no município, na base consultada. Não comprova cobertura de todos os negócios ou concorrentes reais.';

            if ($contagem->periodo_referencia === null || $contagem->versao_fonte === null) {
                $avisos[] = 'Período de referência ou versão da base de estabelecimentos não confirmados. A data da consulta não substitui essas informações.';
            }

            if ($contagem->quantidade === 0) {
                $avisos[] = 'Uma coleta com zero registros não comprova ausência real de concorrentes sem validação independente de cobertura.';
            }

            if ($populacao !== null) {
                $avisos[] = 'As referências de população e estabelecimentos não foram compatibilizadas; estes dados não formam uma fotografia validada do mesmo período.';
            }
        }

        $avisos[] = 'A fórmula e os pesos do score ainda não foram aprovados pelo grupo e pela professora.';

        return response()->json([
            'municipio' => [
                'codigo_ibge' => $municipio->codigo_ibge,
                'nome' => $municipio->nome,
            ],
            'atividade' => [
                'codigo_cnae' => $dados['cnae'],
                'nome' => $atividade?->nome,
                'catalogada' => $atividade !== null,
            ],
            'populacao' => $populacao === null ? null : [
                'valor' => $populacao->valor,
                'periodo_referencia' => $populacao->periodo_referencia,
                'unidade' => $populacao->unidade,
                'fonte' => $populacao->fonteDado?->nome,
                'coletado_em' => $populacao->coletado_em,
            ],
            'concorrentes' => $contagem === null ? null : [
                'quantidade' => $contagem->quantidade,
                'criterio' => $contagem->criterio,
                'fonte' => $contagem->fonteDado->nome,
                'codigo_fonte' => $contagem->fonteDado->codigo,
                'url_referencia' => $contagem->fonteDado->url_referencia,
                'consultado_em' => $contagem->consultado_em,
                'periodo_referencia' => $contagem->periodo_referencia,
                'versao_fonte' => $contagem->versao_fonte,
            ],
            'score' => null,
            'avisos' => $avisos,
        ]);
    }
}
