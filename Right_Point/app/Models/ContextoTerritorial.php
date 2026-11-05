<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContextoTerritorial extends Model
{
    protected $table = 'contextos_territoriais';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['dados' => 'array', 'coletado_em' => 'datetime'];
    }
}
