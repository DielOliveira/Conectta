<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['label', 'order', 'is_active'])]
class TipoContrato extends Model
{
    protected $table = 'tipo_contratos';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }
}