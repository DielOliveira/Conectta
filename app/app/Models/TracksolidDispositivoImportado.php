<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TracksolidDispositivoImportado extends Model
{
    protected $table = 'tracksolid_dispositivos_importados';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_tag' => 'boolean',
            'dados_brutos' => 'array',
        ];
    }

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(TracksolidImportacao::class, 'importacao_id');
    }
}
