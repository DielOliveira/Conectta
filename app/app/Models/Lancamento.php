<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'numr',
    'cliente_id',
    'data_lancamento',
    'valor_planejado',
    'valor_efetivado',
    'numero_boleto',
    'observacao',
    'is_baixado',
    'mes_referencia',
    'ano_referencia',
    'time_stamp',
    'log',
])]
class Lancamento extends Model
{
    protected function casts(): array
    {
        return [
            'data_lancamento' => 'date',
            'valor_planejado' => 'decimal:2',
            'valor_efetivado' => 'decimal:2',
            'is_baixado' => 'boolean',
            'time_stamp' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
