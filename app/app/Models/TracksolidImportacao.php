<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TracksolidImportacao extends Model
{
    protected $table = 'tracksolid_importacoes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'importado_em' => 'datetime',
            'resumo' => 'array',
        ];
    }

    public function dispositivos(): HasMany
    {
        return $this->hasMany(TracksolidDispositivoImportado::class, 'importacao_id');
    }
}
