<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'configuracao_integracao_id',
    'access_token',
    'refresh_token',
    'expire_at',
    'refresh_expire_at',
])]
class TokenLytex extends Model
{
    protected $table = 'tokens_lytex';

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expire_at' => 'datetime',
            'refresh_expire_at' => 'datetime',
        ];
    }
}