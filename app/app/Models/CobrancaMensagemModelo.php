<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tipo',
    'nome',
    'canal',
    'ordem',
    'ativo',
    'conteudo',
])]
class CobrancaMensagemModelo extends Model
{
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }
}
