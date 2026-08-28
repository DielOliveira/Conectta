<?php

namespace App\Services\Estoque;

use App\Models\Chip;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use Closure;
use Illuminate\Validation\ValidationException;

class EquipamentoStatusWorkflow
{
    private static int $nivelAutorizacao = 0;

    public static function executar(Closure $callback): mixed
    {
        self::$nivelAutorizacao++;

        try {
            return $callback();
        } finally {
            self::$nivelAutorizacao--;
        }
    }

    public static function prepararNovo(Chip|Rastreador $equipamento): void
    {
        $disponivelId = StatusRastreador::query()->where('label', 'Disponivel')->value('id');

        if ($disponivelId === null) {
            throw ValidationException::withMessages([
                'status_rastreador_id' => 'O status Disponivel precisa existir antes de cadastrar equipamentos.',
            ]);
        }

        $equipamento->status_rastreador_id = $disponivelId;
    }

    public static function validarAlteracao(Chip|Rastreador $equipamento): void
    {
        if (! $equipamento->isDirty('status_rastreador_id') || self::$nivelAutorizacao > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'status_rastreador_id' => 'O status do equipamento é controlado pelos fluxos do sistema e não pode ser alterado manualmente.',
        ]);
    }
}
