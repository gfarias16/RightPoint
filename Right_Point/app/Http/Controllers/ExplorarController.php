<?php

namespace App\Http\Controllers;

use App\Models\AtividadeEconomica;
use App\Models\Municipio;
use App\Services\ConsultaDiagnostico;
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
                $query->where('nome', 'like', '%'.$busca.'%')
                    ->orWhere('codigo_cnae', 'like', $busca.'%');
            })
            ->orderBy('nome')
            ->limit(20)
            ->get(['codigo_cnae', 'nome']);

        return response()->json($atividades);
    }

    public function consultar(Request $request, ConsultaDiagnostico $consulta): JsonResponse
    {
        $dados = $request->validate([
            'codigo_ibge' => ['required', 'string', 'regex:/^33[0-9]{5}$/', Rule::exists('municipios', 'codigo_ibge')->where('uf', 'RJ')],
            'cnae' => ['required', 'string', 'regex:/^[0-9]{7}$/'],
        ]);

        return response()->json($consulta->consultar($dados['codigo_ibge'], $dados['cnae']));
    }
}
