<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'configuracao_integracao_id',
    'contrato_id',
    'event_type',
    'doc_token',
    'status',
    'payload',
    'is_valid',
    'processed',
    'message',
])]
class ZapSignWebhookLog extends Model
{
    protected $table = 'zapsign_webhook_logs';


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

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }
}
