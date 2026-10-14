<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicadorMunicipal extends Model
{
    // Cada linha identifica um indicador, fonte e período para um município.
    protected $table = 'indicadores_municipais';

    protected $fillable = [
        'municipio_id',
        'fonte_dado_id',
        'codigo_indicador',
        'valor',
        'unidade',
        'periodo_referencia',
        'versao_fonte',
        'coletado_em',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function fonteDado(): BelongsTo
    {
        return $this->belongsTo(FonteDado::class);
    }
}
