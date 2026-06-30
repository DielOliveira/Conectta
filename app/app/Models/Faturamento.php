<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ano',
    'mes',
    'is_aberto',
])]
class Faturamento extends Model
{
    protected function casts(): array
    {
        return [
            'ano' => 'integer',
            'mes' => 'integer',
            'is_aberto' => 'boolean',
        ];
    }
}
