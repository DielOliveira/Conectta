<?php

namespace App\Models;

use App\Support\ChipNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'cpf', 'telefone', 'is_ativo', 'agenda_token_hash', 'agenda_token_credencial'])]
class Tecnico extends Model
{
    protected $table = 'tecnicos';

    protected static function booted(): void
    {
        static::saving(function (Tecnico $tecnico): void {
            $tecnico->cpf = preg_replace('/\D+/', '', $tecnico->cpf ?? '') ?: null;
            $tecnico->telefone = filled($tecnico->telefone) ? ChipNumber::local($tecnico->telefone) : null;
        });
    }

    protected function casts(): array
    {
        return [
            'is_ativo' => 'boolean',
            'agenda_token_credencial' => 'encrypted',
        ];
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
    }

    public function disponibilidadesOrdemServico(): HasMany
    {
        return $this->hasMany(OrdemServicoDisponibilidade::class);
    }

    public static function formatarCpf(?string $cpf): string
    {
        $digits = preg_replace('/\D+/', '', (string) $cpf) ?? '';

        if (strlen($digits) !== 11) {
            return (string) $cpf;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits) ?? $digits;
    }
}
