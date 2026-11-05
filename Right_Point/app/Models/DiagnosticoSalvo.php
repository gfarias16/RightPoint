<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DiagnosticoSalvo extends Model
{
    use HasUuids;

    protected $table = 'diagnosticos_salvos';

    protected $guarded = ['id', 'user_id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
