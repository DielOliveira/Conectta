<?php

namespace App\Models;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['numero', 'tipo', 'status', 'cliente_id', 'veiculo_id', 'associado', 'tecnico_id', 'nome_tecnico_externo', 'disponibilidade_id',
    'agendado_em', 'endereco', 'descricao', 'observacoes', 'localizacao_url',
    'localizacao_latitude', 'localizacao_longitude', 'notificar_cliente', 'token_hash', 'token_credencial', 'token_invalidado_em',
    'aceita_em', 'iniciada_em', 'inicio_latitude', 'inicio_longitude', 'termino_tecnico_em', 'finalizada_em',
    'finalizada_por', 'cancelada_em', 'cancelada_por', 'motivo_cancelamento', 'motivo_pendencia',
    'resultado_manutencao', 'descricao_atendimento', 'equipamentos_confirmados', 'rastreador_anterior_id',
    'chip_anterior_id', 'rastreador_novo_id', 'chip_novo_id', 'check_funcionamento', 'check_pos_chave', 'check_bloqueio'])]
class OrdemServico extends Model
{
    protected $table = 'ordens_servico';

    protected function casts(): array
    {
        return [
            'tipo' => OrdemServicoTipo::class, 'status' => OrdemServicoStatus::class,
            'associado' => 'boolean',
            'token_credencial' => 'encrypted',
            'agendado_em' => 'datetime', 'token_invalidado_em' => 'datetime',
            'aceita_em' => 'datetime', 'iniciada_em' => 'datetime', 'termino_tecnico_em' => 'datetime',
            'finalizada_em' => 'datetime', 'cancelada_em' => 'datetime', 'notificar_cliente' => 'boolean',
            'equipamentos_confirmados' => 'boolean', 'check_funcionamento' => 'boolean', 'check_pos_chave' => 'boolean',
            'localizacao_latitude' => 'decimal:7', 'localizacao_longitude' => 'decimal:7',
            'inicio_latitude' => 'decimal:7', 'inicio_longitude' => 'decimal:7',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function disponibilidade(): BelongsTo
    {
        return $this->belongsTo(OrdemServicoDisponibilidade::class, 'disponibilidade_id');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(OrdemServicoHistorico::class)->orderBy('created_at');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(OrdemServicoFoto::class);
    }

    public function notificacoes(): HasMany
    {
        return $this->hasMany(OrdemServicoNotificacao::class);
    }

    public function rastreadorAnterior(): BelongsTo
    {
        return $this->belongsTo(Rastreador::class, 'rastreador_anterior_id');
    }

    public function chipAnterior(): BelongsTo
    {
        return $this->belongsTo(Chip::class, 'chip_anterior_id');
    }

    public function rastreadorNovo(): BelongsTo
    {
        return $this->belongsTo(Rastreador::class, 'rastreador_novo_id');
    }

    public function chipNovo(): BelongsTo
    {
        return $this->belongsTo(Chip::class, 'chip_novo_id');
    }

    public function rastreadorVinculadoAoConcluir(): ?Rastreador
    {
        if ($this->tipo === OrdemServicoTipo::RETIRADA) {
            return null;
        }

        return $this->rastreadorNovo ?? $this->rastreadorAnterior;
    }

    public function chipVinculadoAoConcluir(): ?Chip
    {
        if ($this->tipo === OrdemServicoTipo::RETIRADA) {
            return null;
        }

        return $this->chipNovo ?? $this->chipAnterior;
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->whereNotIn('status', [OrdemServicoStatus::FINALIZADA->value, OrdemServicoStatus::CANCELADA->value]);
    }

    public function getNumeroFormatadoAttribute(): string
    {
        return 'OS '.str_pad((string) $this->numero, 6, '0', STR_PAD_LEFT);
    }

    public function getNomeAtendimentoAttribute(): string
    {
        return $this->associado
            ? trim((string) $this->veiculo?->associado)
            : trim((string) $this->cliente?->nome);
    }

    public function getTelefoneAtendimentoAttribute(): ?string
    {
        $telefone = $this->associado ? $this->veiculo?->contato : $this->cliente?->telefone1;

        return filled($telefone) ? (string) $telefone : null;
    }

    public function getTelefonePaisAtendimentoAttribute(): string
    {
        return (string) ($this->associado ? ($this->veiculo?->contato_pais ?: 'BR') : ($this->cliente?->telefone1_pais ?: 'BR'));
    }

    public function getNomeTecnicoExibicaoAttribute(): ?string
    {
        if (! $this->tecnico) {
            return null;
        }

        return $this->tecnico->isOutros() && filled($this->nome_tecnico_externo)
            ? $this->tecnico->nome.' — '.$this->nome_tecnico_externo
            : $this->tecnico->nome;
    }
}
