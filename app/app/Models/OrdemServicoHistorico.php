<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ordem_servico_id', 'evento', 'status_anterior', 'status_novo', 'user_id', 'tecnico_id', 'observacao', 'contexto'])]
class OrdemServicoHistorico extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ordem_servico_historicos';

    protected function casts(): array
    {
        return ['contexto' => 'array'];
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }
}
