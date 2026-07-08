<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['modelo', 'ativacao', 'imei', 'tecnico_id', 'chip_id', 'is_estoque', 'status_rastreador_id', 'criado_em'])]
class Rastreador extends Model
{
    protected $table = 'rastreadores';

    protected function casts(): array
    {
        return [
            'is_estoque' => 'boolean',
            'criado_em' => 'datetime',
        ];
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function chip(): BelongsTo
    {
        return $this->belongsTo(Chip::class);
    }

    public function statusRastreador(): BelongsTo
    {
        return $this->belongsTo(StatusRastreador::class);
    }
}
