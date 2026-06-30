<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'numr',
    'vendedor_id',
    'status_contrato_id',
    'status_cliente_id',
    'cliente_origem_id',
    'estado_id',
    'nome',
    'cpf_cnpj',
    'rg',
    'nascimento',
    'email',
    'cep',
    'rua',
    'numero',
    'complemento',
    'setor',
    'cidade',
    'telefone1',
    'telefone2',
    'empresa',
    'indicacao',
    'data_adesao',
    'data_exclusao',
    'dia_pagamento',
    'is_spc',
    'anotacoes',
    'replicar_pagamento',
])]
class Cliente extends Model
{
    protected function casts(): array
    {
        return [
            'nascimento' => 'date',
            'data_adesao' => 'date',
            'data_exclusao' => 'datetime',
            'is_spc' => 'boolean',
            'replicar_pagamento' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Cliente $cliente): void {
            $cliente->cpf_cnpj = preg_replace('/\D+/', '', $cliente->cpf_cnpj ?? '');
            $cliente->cep = preg_replace('/\D+/', '', $cliente->cep ?? '') ?: null;
            $cliente->telefone1 = preg_replace('/\D+/', '', $cliente->telefone1 ?? '');
        });
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class);
    }

    public function statusContrato(): BelongsTo
    {
        return $this->belongsTo(StatusContrato::class);
    }

    public function statusCliente(): BelongsTo
    {
        return $this->belongsTo(StatusCliente::class);
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(ClienteOrigem::class, 'cliente_origem_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class);
    }

    public function veiculosAtivos(): HasMany
    {
        return $this->veiculos()->ativos();
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    public function syncStatusFromVeiculos(): void
    {
        $status = StatusCliente::query()
            ->where('label', $this->veiculosAtivos()->exists() ? 'Ativo' : 'Inativo')
            ->first();

        if ($status === null || $this->status_cliente_id === $status->id) {
            return;
        }

        $this->forceFill(['status_cliente_id' => $status->id])->save();
    }

    public function getCpfCnpjFormatadoAttribute(): string
    {
        $value = preg_replace('/\D+/', '', $this->cpf_cnpj ?? '');

        if (strlen($value) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $value) ?? $value;
        }

        if (strlen($value) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $value) ?? $value;
        }

        return Str::of($this->cpf_cnpj ?? '')->toString();
    }
}
