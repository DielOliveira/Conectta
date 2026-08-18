<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tecnico_id', 'tipo', 'data', 'hora_inicio', 'hora_fim'])]
class OrdemServicoDisponibilidade extends Model
{
    public const TIPO_DISPONIBILIDADE = 'disponibilidade';

    public const TIPO_BLOQUEIO = 'bloqueio';

    protected $table = 'ordem_servico_disponibilidades';

    protected function casts(): array
    {
        return ['data' => 'date'];
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function ordens(): HasMany
    {
        return $this->hasMany(OrdemServico::class, 'disponibilidade_id');
    }

    public function isBloqueio(): bool
    {
        return $this->tipo === self::TIPO_BLOQUEIO;
    }
}
