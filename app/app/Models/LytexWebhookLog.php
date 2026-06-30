<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'configuracao_integracao_id',
    'invoice_id',
    'webhook_type',
    'signature',
    'invoice_external_id',
    'reference_id',
    'status',
    'payload',
    'is_valid',
    'processed',
    'message',
])]
class LytexWebhookLog extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_valid' => 'boolean',
            'processed' => 'boolean',
        ];
    }

    public function configuracaoIntegracao(): BelongsTo
    {
        return $this->belongsTo(ConfiguracaoIntegracao::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}