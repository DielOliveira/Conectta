<?php

namespace App\Models;

use App\Services\Estoque\EquipamentoStatusWorkflow;
use App\Services\OrdemServico\OrdemServicoEquipamentoReserva;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['modelo', 'ativacao', 'imei', 'tecnico_id', 'chip_id', 'status_rastreador_id', 'criado_em'])]
class Rastreador extends Model
{
    protected $table = 'rastreadores';

    protected function casts(): array
    {
        return [
            'criado_em' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Rastreador $rastreador) => EquipamentoStatusWorkflow::prepararNovo($rastreador));

        static::updating(fn (Rastreador $rastreador) => EquipamentoStatusWorkflow::validarAlteracao($rastreador));

        static::saving(function (Rastreador $rastreador): void {
            if ($rastreador->exists && $rastreador->isDirty(['tecnico_id', 'chip_id', 'status_rastreador_id'])) {
                OrdemServicoEquipamentoReserva::validarRastreador((int) $rastreador->getKey());
            }

            if ($rastreador->isDirty('chip_id') && $rastreador->chip_id !== null) {
                OrdemServicoEquipamentoReserva::validarChip((int) $rastreador->chip_id);
            }
        });

        static::deleting(fn (Rastreador $rastreador) => OrdemServicoEquipamentoReserva::validarRastreador((int) $rastreador->getKey()));
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function chip(): BelongsTo
    {
        return $this->belongsTo(Chip::class);
    }

    public function statusRastreador(): BelongsTo
    {
        return $this->belongsTo(StatusRastreador::class);
    }
}
