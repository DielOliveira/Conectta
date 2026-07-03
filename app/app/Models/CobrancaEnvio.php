<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cobranca_execucao_id',
    'cliente_id',
    'lancamento_id',
    'invoice_id',
    'tipo',
    'status',
    'data_referencia',
    'vencimento',
    'valor',
    'tentativas',
    'processado_em',
    'enviado_em',
    'telefone',
    'link_invoice',
    'link_boleto',
    'mensagem',
    'erro',
    'whatsapp_payload',
    'whatsapp_response',
])]
class CobrancaEnvio extends Model
{
    protected function casts(): array
    {
        return [
            'data_referencia' => 'date',
            'vencimento' => 'date',
            'valor' => 'decimal:2',
            'processado_em' => 'datetime',
            'enviado_em' => 'datetime',
        ];
    }

    public function execucao(): BelongsTo
    {
        return $this->belongsTo(CobrancaExecucao::class, 'cobranca_execucao_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
