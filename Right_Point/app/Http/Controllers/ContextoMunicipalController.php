<?php

namespace App\Http\Controllers;

use App\Services\ContextoMunicipal;

class ContextoMunicipalController extends Controller
{
    public function __invoke(string $codigo, ContextoMunicipal $contexto)
    {
        return response()->json($contexto->consultar($codigo));
    }
}
