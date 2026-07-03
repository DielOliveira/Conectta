<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cobranca_agendamento_id',
    'data_processamento',
    'tipo',
    'status',
    'dry_run',
    'total_processados',
    'total_enviados',
    'total_ignorados',
    'total_erros',
    'iniciado_em',
    'finalizado_em',
    'mensagem',
])]
class CobrancaExecucao extends Model
{
    protected $table = 'cobranca_execucoes';

    protected function casts(): array
    {
        return [
            'data_processamento' => 'date',
            'dry_run' => 'boolean',
            'iniciado_em' => 'datetime',
            'finalizado_em' => 'datetime',
        ];
    }

    public function envios(): HasMany
    {
        return $this->hasMany(CobrancaEnvio::class);
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(CobrancaAgendamento::class, 'cobranca_agendamento_id');
    }
}
