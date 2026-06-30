<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fornecedor', 'operadora', 'iccid', 'tecnico_id'])]
class Chip extends Model
{
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }
}
