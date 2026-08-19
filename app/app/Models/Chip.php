<?php

namespace App\Models;

use App\Services\Estoque\EquipamentoStatusWorkflow;
use App\Services\OrdemServico\OrdemServicoEquipamentoReserva;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['fornecedor', 'fornecedor_id', 'operadora', 'operadora_id', 'numero_chip', 'iccid', 'tecnico_id', 'status_rastreador_id'])]
class Chip extends Model
{
    protected static function booted(): void
    {
        static::creating(fn (Chip $chip) => EquipamentoStatusWorkflow::prepararNovo($chip));

        static::updating(fn (Chip $chip) => EquipamentoStatusWorkflow::validarAlteracao($chip));

        static::saving(function (Chip $chip): void {
            if ($chip->exists && $chip->isDirty(['tecnico_id', 'status_rastreador_id'])) {
                OrdemServicoEquipamentoReserva::validarChip((int) $chip->getKey());
            }
        });

        static::deleting(fn (Chip $chip) => OrdemServicoEquipamentoReserva::validarChip((int) $chip->getKey()));
    }

    public function fornecedorCadastro(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function operadoraCadastro(): BelongsTo
    {
        return $this->belongsTo(Operadora::class, 'operadora_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function rastreador(): HasOne
    {
        return $this->hasOne(Rastreador::class);
    }

    public function statusRastreador(): BelongsTo
    {
        return $this->belongsTo(StatusRastreador::class);
    }
}
