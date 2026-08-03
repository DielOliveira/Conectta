<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ordem_servico_id', 'destinatario_tipo', 'evento', 'telefone', 'mensagem', 'status', 'tentativas', 'erro', 'enviada_em'])]
class OrdemServicoNotificacao extends Model
{
    protected $table = 'ordem_servico_notificacoes';

    protected function casts(): array
    {
        return ['enviada_em' => 'datetime'];
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }
}
