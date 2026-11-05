<?php

use App\Http\Controllers\ContaController;
use App\Http\Controllers\ContextoMunicipalController;
use App\Http\Controllers\DiagnosticoSalvoController;
use App\Http\Controllers\ExplorarController;
use App\Http\Controllers\MalhaRjController;
use App\Http\Controllers\PerfilNegocioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExplorarController::class, 'index'])->name('explorar');
Route::get('/consulta-demonstrativa', [ExplorarController::class, 'consultar'])->name('consulta.demonstrativa');
Route::get('/malha/rj', MalhaRjController::class)->name('malha.rj');
Route::get('/cnaes', [ExplorarController::class, 'buscarCnaes'])->name('cnaes.buscar');
Route::get('/contextos/{codigo}', ContextoMunicipalController::class)->where('codigo', '33[0-9]{5}')->name('contextos.municipio');

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [ContaController::class, 'formulario'])->name('login');
    Route::get('/cadastro', [ContaController::class, 'formulario'])->name('register');
    Route::post('/entrar', [ContaController::class, 'entrar'])->middleware('throttle:5,1,login');
    Route::post('/cadastro', [ContaController::class, 'cadastrar'])->middleware('throttle:5,1,cadastro');
});
Route::middleware('auth')->group(function () {
    Route::post('/sair', [ContaController::class, 'sair'])->name('logout');
    Route::get('/perfis', [PerfilNegocioController::class, 'index'])->name('perfis.index');
    Route::post('/perfis', [PerfilNegocioController::class, 'salvar'])->middleware('throttle:120,1,perfis');
    Route::put('/perfis/{id}', [PerfilNegocioController::class, 'salvar'])->whereNumber('id');
    Route::delete('/perfis/{id}', [PerfilNegocioController::class, 'excluir'])->whereNumber('id');
    Route::get('/diagnosticos', [DiagnosticoSalvoController::class, 'index'])->name('diagnosticos.index');
    Route::post('/diagnosticos', [DiagnosticoSalvoController::class, 'salvar'])->middleware('throttle:10,1,diagnosticos');
    Route::get('/diagnosticos/{id}', [DiagnosticoSalvoController::class, 'mostrar'])->whereUuid('id');
    Route::get('/diagnosticos/{id}/relatorio', [DiagnosticoSalvoController::class, 'relatorio'])->whereUuid('id');
    Route::get('/diagnosticos/{id}/json', [DiagnosticoSalvoController::class, 'exportar'])->whereUuid('id');
    Route::delete('/diagnosticos/{id}', [DiagnosticoSalvoController::class, 'excluir'])->whereUuid('id');
});
