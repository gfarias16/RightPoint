<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticoSalvo;
use App\Services\ConsultaDiagnostico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiagnosticoSalvoController extends Controller
{
    public function index(Request $request)
    {
        $registros = DiagnosticoSalvo::where('user_id', $request->user()->id)
            ->select(['id', 'titulo', 'codigo_ibge', 'cnae', 'metodo', 'sha256', 'created_at'])
            ->latest()->paginate(10);

        return response()->json($registros)->header('Cache-Control', 'private, no-store');
    }

    public function salvar(Request $request, ConsultaDiagnostico $consulta)
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'codigo_ibge' => ['required', 'string', Rule::exists('municipios', 'codigo_ibge')->where('uf', 'RJ')],
            'cnae' => ['required', 'string', 'regex:/^[0-9]{7}$/'],
        ]);

        return DB::transaction(function () use ($consulta, $dados, $request) {
            // Não confiar em score, fatores ou fontes enviados pelo navegador.
            $snapshot = $consulta->consultar($dados['codigo_ibge'], $dados['cnae'], true);
            $snapshot['gerado_em_utc'] = now('UTC')->toIso8601String();
            $snapshot['versao_snapshot'] = 1;
            $registro = new DiagnosticoSalvo;
            $registro->fill($dados + ['snapshot' => $snapshot,
                'metodo' => $snapshot['diagnostico']['metodo'],
                'sha256' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)),
            ]);
            $registro->user_id = $request->user()->id;
            $registro->save();

            return response()->json(['id' => $registro->id, 'sha256' => $registro->sha256], 201);
        });
    }

    private function registro(Request $request, string $id): DiagnosticoSalvo
    {
        return DiagnosticoSalvo::where('user_id', $request->user()->id)->findOrFail($id);
    }

    public function mostrar(Request $request, string $id)
    {
        return response()->json($this->registro($request, $id))->header('Cache-Control', 'private, no-store');
    }

    public function relatorio(Request $request, string $id)
    {
        $registro = $this->registro($request, $id);

        return response()->view('relatorio', ['registro' => $registro, 'dados' => $registro->snapshot])
            ->header('Cache-Control', 'private, no-store');
    }

    public function exportar(Request $request, string $id)
    {
        $registro = $this->registro($request, $id);

        return response()->json(['id' => $registro->id, 'sha256' => $registro->sha256, 'snapshot' => $registro->snapshot], 200, [
            'Content-Disposition' => 'attachment; filename="rightpoint-'.$registro->id.'.json"',
            'Cache-Control' => 'private, no-store',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function excluir(Request $request, string $id)
    {
        $this->registro($request, $id)->delete();

        return response()->noContent();
    }
}
