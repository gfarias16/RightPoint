<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FonteDado extends Model
{
    /*definicao da tabela com nome fontes_dados
    nesta tabela teremos os dados das fontes de informacao */

    protected $table = 'fontes_dados';

    protected $fillable = [
        'codigo',
        'nome',
        'url_referencia',
    ];
}
