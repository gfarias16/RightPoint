<?php

use App\Http\Controllers\ExplorarController;
use App\Http\Controllers\MalhaRjController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExplorarController::class, 'index'])->name('explorar');
Route::get('/consulta-demonstrativa', [ExplorarController::class, 'consultar'])->name('consulta.demonstrativa');
Route::get('/malha/rj', MalhaRjController::class)->name('malha.rj');
Route::get('/cnaes', [ExplorarController::class, 'buscarCnaes'])->name('cnaes.buscar');