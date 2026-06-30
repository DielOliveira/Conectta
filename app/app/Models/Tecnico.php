<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nome', 'cpf', 'telefone', 'is_ativo'])]
class Tecnico extends Model
{
    protected $table = 'tecnicos';

    protected static function booted(): void
    {
        static::saving(function (Tecnico $tecnico): void {
            $tecnico->cpf = preg_replace('/\D+/', '', $tecnico->cpf ?? '') ?: null;
        });
    }

    protected function casts(): array
    {
        return [
            'is_ativo' => 'boolean',
        ];
    }
}
