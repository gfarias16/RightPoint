<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtividadeEconomica extends Model
{
    protected $table = 'atividades_economicas';

    protected $fillable = [
        'codigo_cnae',
        'nome',
        'descricao',
    ];
}
