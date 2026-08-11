<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['origem_type', 'origem_id', 'etapa', 'driver', 'sessao', 'idempotency_key', 'job_id', 'status', 'whatsapp_message_id', 'tentativas', 'ultimo_erro', 'resposta', 'enfileirado_em', 'enviado_em', 'falhou_em'])]
class WhatsappJob extends Model
{
    protected function casts(): array
    {
        return ['resposta' => 'array', 'enfileirado_em' => 'datetime', 'enviado_em' => 'datetime', 'falhou_em' => 'datetime'];
    }

    public function origem(): MorphTo
    {
        return $this->morphTo();
    }
}
