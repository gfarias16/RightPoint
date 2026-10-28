<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContagemEstabelecimento extends Model
{
    protected $table = 'contagens_estabelecimentos';

    protected $fillable = [
        'municipio_id',
        'atividade_economica_id',
        'fonte_dado_id',
        'quantidade',
        'registros_no_recorte',
        'cnpjs_ausentes',
        'criterio',
        'periodo_referencia',
        'versao_fonte',
        'consultado_em',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
            'registros_no_recorte' => 'integer',
            'cnpjs_ausentes' => 'integer',
            'consultado_em' => 'datetime',
        ];
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function atividadeEconomica(): BelongsTo
    {
        return $this->belongsTo(AtividadeEconomica::class);
    }

    public function fonteDado(): BelongsTo
    {
        return $this->belongsTo(FonteDado::class);
    }
}