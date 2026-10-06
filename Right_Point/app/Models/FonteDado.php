<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FonteDado extends Model
{
    protected $table = 'fontes_dados';

    protected $fillable = [
        'codigo',
        'nome',
        'url_referencia',
    ];
}
