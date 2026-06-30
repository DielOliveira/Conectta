<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'cliente_id',
    'status_rastreador_id',
    'tipo_veiculo_id',
    'rastreador_id',
    'chip_id',
    'tecnico_instala_id',
    'tecnico_remocao_id',
    'veiculo',
    'placa',
    'cor',
    'ano',
    'imei',
    'data_instalacao',
    'data_retirada',
    'login',
    'senha',
    'tecnico_remocao',
    'instalador',
    'valor_instalacao',
    'associado',
    'contato',
    'observacao',
    'data_exclusao',
])]
class Veiculo extends Model
{
    protected function casts(): array
    {
        return [
            'data_instalacao' => 'date',
            'data_retirada' => 'date',
            'data_exclusao' => 'datetime',
            'valor_instalacao' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Veiculo $veiculo): void {
            $veiculo->validateRastreadorRules();
            $veiculo->syncInstaladorFromRastreador();
        });

        static::saved(function (Veiculo $veiculo): void {
            $veiculo->syncRastreadorStatus();
            $veiculo->syncClientesStatusAfterChange();
        });

        static::deleting(function (Veiculo $veiculo): void {
            $veiculo->releaseRastreador();
        });

        static::deleted(function (Veiculo $veiculo): void {
            $veiculo->syncClientesStatusAfterChange();
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function statusRastreador(): BelongsTo
    {
        return $this->belongsTo(StatusRastreador::class);
    }

    public function tipoVeiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVeiculo::class);
    }

    public function rastreador(): BelongsTo
    {
        return $this->belongsTo(Rastreador::class);
    }

    public function chip(): BelongsTo
    {
        return $this->belongsTo(Chip::class);
    }

    public function tecnicoInstala(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class, 'tecnico_instala_id');
    }

    public function tecnicoRemocao(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class, 'tecnico_remocao_id');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }
    #[Scope]
    protected function ativos(Builder $query): void
    {
        $query
            ->whereNull('data_exclusao')
            ->whereHas('statusRastreador', fn (Builder $query) => $query->where('label', 'Ativo'));
    }

    public function isAtivo(): bool
    {
        return $this->statusRastreador?->label === 'Ativo'
            || $this->status_rastreador_id === self::statusId('Ativo');
    }

    public function isCancelado(): bool
    {
        return $this->statusRastreador?->label === 'Cancelado'
            || $this->status_rastreador_id === self::statusId('Cancelado');
    }

    private function validateRastreadorRules(): void
    {
        $errors = [];

        if ($this->isCancelado()) {
            if (blank($this->data_retirada)) {
                $errors['data_retirada'] = 'Informe a data de retirada para cancelar o rastreador.';
            }

            if (blank($this->tecnico_remocao_id)) {
                $errors['tecnico_remocao_id'] = 'Informe o tecnico de remocao para cancelar o rastreador.';
            }
        }

        if ($this->isAtivo()) {
            if ($this->rastreador_id !== null && $this->hasAnotherActiveWith('rastreador_id', $this->rastreador_id)) {
                $errors['rastreador_id'] = 'Este IMEI ja esta vinculado a outro veiculo ativo.';
            }

            if ($this->chip_id !== null && $this->hasAnotherActiveWith('chip_id', $this->chip_id)) {
                $errors['chip_id'] = 'Este chip ja esta vinculado a outro veiculo ativo.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function hasAnotherActiveWith(string $column, int $value): bool
    {
        return self::query()
            ->whereKeyNot($this->getKey())
            ->where($column, $value)
            ->whereNull('data_exclusao')
            ->where('status_rastreador_id', self::statusId('Ativo'))
            ->exists();
    }

    private function syncInstaladorFromRastreador(): void
    {
        if ($this->rastreador_id === null) {
            $this->tecnico_instala_id = null;
            $this->instalador = null;

            return;
        }

        $rastreador = Rastreador::query()
            ->with('tecnico')
            ->find($this->rastreador_id);

        $this->tecnico_instala_id = $rastreador?->tecnico_id;
        $this->instalador = $rastreador?->tecnico?->nome;
    }

    private function syncRastreadorStatus(): void
    {
        if ($this->rastreador_id === null) {
            return;
        }

        if ($this->isCancelado()) {
            $this->releaseRastreador();

            return;
        }

        if ($this->isAtivo()) {
            Rastreador::query()
                ->whereKey($this->rastreador_id)
                ->update(['status_rastreador_id' => self::statusId('Ativo')]);
        }
    }

    private function releaseRastreador(): void
    {
        if ($this->rastreador_id === null) {
            return;
        }

        Rastreador::query()
            ->whereKey($this->rastreador_id)
            ->update(array_filter([
                'status_rastreador_id' => self::statusId('Disponivel'),
                'tecnico_id' => $this->tecnico_remocao_id,
            ], fn ($value): bool => $value !== null));
    }

    private function syncClientesStatusAfterChange(): void
    {
        $this->cliente?->syncStatusFromVeiculos();

        $originalClienteId = $this->getOriginal('cliente_id');

        if ($originalClienteId !== null && (int) $originalClienteId !== (int) $this->cliente_id) {
            Cliente::query()->find($originalClienteId)?->syncStatusFromVeiculos();
        }
    }

    public static function statusId(string $label): ?int
    {
        return StatusRastreador::query()
            ->where('label', $label)
            ->value('id');
    }
}
