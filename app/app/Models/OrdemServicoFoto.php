<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ordem_servico_id', 'caminho', 'nome_original', 'mime_type', 'tamanho'])]
class OrdemServicoFoto extends Model
{
    protected $table = 'ordem_servico_fotos';

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }
}
