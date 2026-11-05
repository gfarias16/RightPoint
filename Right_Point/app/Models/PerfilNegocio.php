<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfilNegocio extends Model
{
    protected $table = 'perfis_negocio';

    protected $fillable = ['nome', 'codigo_ibge', 'cnae', 'notas'];
}
