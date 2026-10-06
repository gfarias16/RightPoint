<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    /*definicao da tabela com nome municipios.
    nesta tabela teremos os dados dos municipios */

    protected $table = 'municipios';
    protected $fillable = [
        'codigo_ibge',
        'nome',
        'uf',
    ];
}
