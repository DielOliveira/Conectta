<?php

namespace App\Models;

use App\Services\Estoque\EquipamentoStatusWorkflow;
use App\Services\OrdemServico\OrdemServicoEquipamentoReserva;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'cliente_id',
    'status_rastreador_id',
    'tipo_veiculo_id',
    'rastreador_id',
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
    'contato_pais',
    'observacao',
    'data_exclusao',
])]
class Veiculo extends Model
{
    use SoftDeletes;

    public const DELETED_AT = 'data_exclusao';

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
            $veiculo->contato = preg_replace('/\D+/', '', (string) $veiculo->contato) ?: null;
            $veiculo->contato_pais = Pais::normalizarCodigoTelefone($veiculo->contato_pais) ?: 'BR';
            $veiculo->validatePlacaUnique();
            $veiculo->validateEquipamentoReserva();
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

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
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

    private function validatePlacaUnique(): void
    {
        if (blank($this->placa) || ($this->exists && ! $this->isDirty('placa'))) {
            return;
        }

        if (self::placaJaCadastrada((string) $this->placa, $this->exists ? (int) $this->getKey() : null)) {
            throw ValidationException::withMessages([
                'placa' => 'Esta placa já está cadastrada em outro veículo.',
            ]);
        }
    }

    private function validateEquipamentoReserva(): void
    {
        if (! $this->isDirty('rastreador_id')) {
            return;
        }

        $rastreadorAnteriorId = $this->getOriginal('rastreador_id');
        if ($rastreadorAnteriorId !== null) {
            OrdemServicoEquipamentoReserva::validarRastreador((int) $rastreadorAnteriorId);
        }

        if ($this->rastreador_id !== null) {
            OrdemServicoEquipamentoReserva::validarRastreador((int) $this->rastreador_id);
        }
    }

    public static function placaJaCadastrada(?string $placa, ?int $ignorarVeiculoId = null): bool
    {
        $placaNormalizada = self::normalizarPlaca($placa);

        if ($placaNormalizada === '') {
            return false;
        }

        $expressaoNormalizada = "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(placa), '-', ''), ' ', ''), '.', ''), '/', ''), '_', ''))";
        $query = self::query()->whereRaw("{$expressaoNormalizada} = ?", [$placaNormalizada]);

        $statusCanceladoId = self::statusId('Cancelado');
        if ($statusCanceladoId !== null) {
            $query->where(function (Builder $query) use ($statusCanceladoId): void {
                $query->whereNull('status_rastreador_id')
                    ->orWhere('status_rastreador_id', '!=', $statusCanceladoId);
            });
        }

        if ($ignorarVeiculoId !== null) {
            $query->whereKeyNot($ignorarVeiculoId);
        }

        return $query->exists();
    }

    public static function normalizarPlaca(?string $placa): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', (string) $placa));
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

        if ($rastreador?->tecnico_id !== null) {
            $this->tecnico_instala_id = $rastreador->tecnico_id;
            $this->instalador = $rastreador->tecnico?->nome;
        }
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
            EquipamentoStatusWorkflow::executar(
                fn () => Rastreador::query()
                    ->whereKey($this->rastreador_id)
                    ->update(['status_rastreador_id' => self::statusId('Ativo')]),
            );
        }
    }

    private function releaseRastreador(): void
    {
        if ($this->rastreador_id === null) {
            return;
        }

        if ($this->hasAnotherActiveWith('rastreador_id', (int) $this->rastreador_id)) {
            return;
        }

        $rastreador = Rastreador::query()->find($this->rastreador_id);

        if ($rastreador === null) {
            return;
        }

        $disponivelId = self::statusId('Disponivel');

        EquipamentoStatusWorkflow::executar(
            function () use ($rastreador, $disponivelId): void {
                $rastreador->update(array_filter([
                    'status_rastreador_id' => $disponivelId,
                    'tecnico_id' => $this->tecnico_remocao_id,
                    'is_estoque' => true,
                ], fn ($value): bool => $value !== null));

                if ($rastreador->chip_id !== null) {
                    Chip::query()
                        ->whereKey($rastreador->chip_id)
                        ->update(array_filter([
                            'status_rastreador_id' => $disponivelId,
                            'tecnico_id' => $this->tecnico_remocao_id,
                        ], fn ($value): bool => $value !== null));
                }
            },
        );
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
