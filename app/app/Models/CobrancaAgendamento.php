<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tipo',
    'ativo',
    'horario',
    'dias_semana',
    'dry_run',
    'enviar_whatsapp',
    'limite',
    'ultima_execucao_em',
    'proxima_execucao_em',
    'ultimo_status',
    'ultima_mensagem',
    'ultima_cobranca_execucao_id',
])]
class CobrancaAgendamento extends Model
{
    protected $table = 'cobranca_agendamentos';

    public const TIPOS = [
        'boleto_7_dias' => 'Boleto 7 dias antes',
        'lembrete_vencimento' => 'Lembrete no vencimento',
        'atraso_2' => 'Atraso 2 dias',
        'atraso_5' => 'Atraso 5 dias',
        'atraso_7' => 'Atraso 7 dias',
        'atraso_10' => 'Atraso 10 dias',
        'atraso_12' => 'Atraso 12 dias',
        'atraso_15' => 'Atraso 15 dias',
    ];

    public const DIAS_SEMANA = [
        0 => 'Domingo',
        1 => 'Segunda',
        2 => 'Terca',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
        6 => 'Sabado',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'dias_semana' => 'array',
            'dry_run' => 'boolean',
            'enviar_whatsapp' => 'boolean',
            'ultima_execucao_em' => 'datetime',
            'proxima_execucao_em' => 'datetime',
        ];
    }

    public function ultimaExecucao(): BelongsTo
    {
        return $this->belongsTo(CobrancaExecucao::class, 'ultima_cobranca_execucao_id');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function diasSemanaLabel(): string
    {
        return collect($this->diasSemanaNormalizados())
            ->map(fn (int $dia): string => self::DIAS_SEMANA[$dia] ?? (string) $dia)
            ->implode(', ');
    }

    public function diasSemanaIniciais(): string
    {
        return collect($this->diasSemanaNormalizados())
            ->map(fn (int $dia): string => mb_substr(self::DIAS_SEMANA[$dia] ?? (string) $dia, 0, 1))
            ->implode(', ');
    }

    /**
     * @return array<int, int>
     */
    private function diasSemanaNormalizados(): array
    {
        $dias = $this->dias_semana ?: array_keys(self::DIAS_SEMANA);

        return collect($dias)
            ->map(fn (int|string $dia): int => (int) $dia)
            ->filter(fn (int $dia): bool => array_key_exists($dia, self::DIAS_SEMANA))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
