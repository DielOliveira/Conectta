<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'acao',
    'entidade_tipo',
    'entidade_id',
    'descricao',
    'antes',
    'depois',
    'ip',
    'user_agent',
    'contexto',
])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'antes' => 'array',
            'depois' => 'array',
            'contexto' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
