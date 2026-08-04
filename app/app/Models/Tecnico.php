<?php

namespace App\Models;

use App\Support\ChipNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'cpf', 'telefone', 'is_ativo'])]
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
}
