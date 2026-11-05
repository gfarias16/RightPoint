<?php

namespace App\Http\Controllers;

use App\Models\PerfilNegocio;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerfilNegocioController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(PerfilNegocio::where('user_id', $request->user()->id)->latest('updated_at')->paginate(20))
            ->header('Cache-Control', 'private, no-store');
    }

    public function salvar(Request $request, ?int $id = null)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:80'],
            'notas' => ['nullable', 'string', 'max:240'],
            'origem_local' => ['nullable', 'string', 'max:80'],
            'codigo_ibge' => ['required', 'string', Rule::exists('municipios', 'codigo_ibge')->where('uf', 'RJ')],
            'cnae' => ['required', 'string', 'regex:/^[0-9]{7}$/', Rule::exists('atividades_economicas', 'codigo_cnae')],
        ]);
        if ($id === null && ! empty($dados['origem_local'])) {
            $existente = PerfilNegocio::where('user_id', $request->user()->id)->where('origem_local', $dados['origem_local'])->first();
            if ($existente) {
                return response()->json($existente);
            }
        }
        $perfil = $id === null ? new PerfilNegocio : PerfilNegocio::where('user_id', $request->user()->id)->findOrFail($id);
        $perfil->fill($dados);
        $perfil->user_id = $request->user()->id;
        if ($id === null) {
            $perfil->origem_local = $dados['origem_local'] ?? null;
        }
        try {
            $perfil->save();
        } catch (UniqueConstraintViolationException $e) {
            // Duas abas copiando a mesma origem não devem duplicar nem sobrescrever o perfil.
            if ($id !== null || empty($dados['origem_local'])) {
                throw $e;
            }

            return response()->json(PerfilNegocio::where('user_id', $request->user()->id)->where('origem_local', $dados['origem_local'])->firstOrFail());
        }

        return response()->json($perfil, $id === null ? 201 : 200);
    }

    public function excluir(Request $request, int $id)
    {
        PerfilNegocio::where('user_id', $request->user()->id)->findOrFail($id)->delete();

        return response()->noContent();
    }
}
