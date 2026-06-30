<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['label', 'order', 'is_active'])]
class StatusContrato extends Model
{
    protected $table = 'status_contratos';

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }
}
