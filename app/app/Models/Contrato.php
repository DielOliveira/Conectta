<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['veiculo_id', 'tipo_contrato_id', 'status_contrato_id', 'doc_token', 'dados'])]
class Contrato extends Model
{
    protected function casts(): array
    {
        return [
            'dados' => 'array',
        ];
    }


    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function tipoContrato(): BelongsTo
    {
        return $this->belongsTo(TipoContrato::class);
    }

    public function statusContrato(): BelongsTo
    {
        return $this->belongsTo(StatusContrato::class);
    }
}