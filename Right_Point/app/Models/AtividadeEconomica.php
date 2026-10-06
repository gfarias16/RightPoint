<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtividadeEconomica extends Model
{
    /*definicao da tabela com nome atividades_economicas.
    nesta tabela teremos os dados das atividades economicas */

    protected $table = 'atividades_economicas';

    protected $fillable = [
        'codigo_cnae',
        'nome',
        'descricao',
    ];
}
