<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'cpf_cnpj',
    'fatura_id',
    'total_value',
    'created_at_external',
    'updated_at_external',
    'hash_id',
    'link_checkout',
    'link_boleto',
    'lancamento_id',
    'status',
    'vencimento',
    'user_id',
])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'total_value' => 'decimal:2',
            'vencimento' => 'datetime',
        ];
    }

    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}