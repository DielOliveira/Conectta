<?php

namespace App\Services\OrdemServico;

use App\Enums\OrdemServicoStatus;
use App\Models\OrdemServico;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class OrdemServicoEquipamentoReserva
{
    private static ?int $ordemPermitidaId = null;

    public static function duranteOrdem(int $ordemId, Closure $callback): mixed
    {
        $anterior = self::$ordemPermitidaId;
        self::$ordemPermitidaId = $ordemId;

        try {
            return $callback();
        } finally {
            self::$ordemPermitidaId = $anterior;
        }
    }

    public static function ordemDoRastreador(int $rastreadorId, ?int $ignorarOrdemId = null): ?OrdemServico
    {
        return self::reservasAtivas($ignorarOrdemId)
            ->where(function (Builder $query) use ($rastreadorId): void {
                $query->where('rastreador_anterior_id', $rastreadorId)
                    ->orWhere('rastreador_novo_id', $rastreadorId);
            })
            ->orderByDesc('id')
            ->first();
    }

    public static function ordemDoChip(int $chipId, ?int $ignorarOrdemId = null): ?OrdemServico
    {
        return self::reservasAtivas($ignorarOrdemId)
            ->where(function (Builder $query) use ($chipId): void {
                $query->where('chip_anterior_id', $chipId)
                    ->orWhere('chip_novo_id', $chipId);
            })
            ->orderByDesc('id')
            ->first();
    }

    public static function mensagemRastreador(int $rastreadorId, ?int $ignorarOrdemId = null): ?string
    {
        $ordem = self::ordemDoRastreador($rastreadorId, $ignorarOrdemId ?? self::$ordemPermitidaId);

        return $ordem
            ? "Este rastreador está reservado para a {$ordem->numero_formatado}. Conclua ou cancele a ordem de serviço antes de movimentá-lo."
            : null;
    }

    public static function mensagemChip(int $chipId, ?int $ignorarOrdemId = null): ?string
    {
        $ordem = self::ordemDoChip($chipId, $ignorarOrdemId ?? self::$ordemPermitidaId);

        return $ordem
            ? "Este chip está reservado para a {$ordem->numero_formatado}. Conclua ou cancele a ordem de serviço antes de movimentá-lo."
            : null;
    }

    public static function validarRastreador(int $rastreadorId, string $campo = 'rastreador_id', ?int $ignorarOrdemId = null): void
    {
        if ($mensagem = self::mensagemRastreador($rastreadorId, $ignorarOrdemId)) {
            throw ValidationException::withMessages([$campo => $mensagem]);
        }
    }

    public static function validarChip(int $chipId, string $campo = 'chip_id', ?int $ignorarOrdemId = null): void
    {
        if ($mensagem = self::mensagemChip($chipId, $ignorarOrdemId)) {
            throw ValidationException::withMessages([$campo => $mensagem]);
        }
    }

    public static function excluirRastreadoresReservados(Builder $query, ?int $ignorarOrdemId = null): Builder
    {
        return $query->whereNotExists(function ($reservas) use ($ignorarOrdemId): void {
            $reservas->selectRaw('1')
                ->from('ordens_servico as os_reserva')
                ->whereNotIn('os_reserva.status', self::statusFinais())
                ->when($ignorarOrdemId, fn ($query) => $query->where('os_reserva.id', '!=', $ignorarOrdemId))
                ->where(function ($query): void {
                    $query->whereColumn('os_reserva.rastreador_anterior_id', 'rastreadores.id')
                        ->orWhereColumn('os_reserva.rastreador_novo_id', 'rastreadores.id');
                });
        });
    }

    public static function excluirChipsReservados(Builder $query, ?int $ignorarOrdemId = null): Builder
    {
        return $query->whereNotExists(function ($reservas) use ($ignorarOrdemId): void {
            $reservas->selectRaw('1')
                ->from('ordens_servico as os_reserva')
                ->whereNotIn('os_reserva.status', self::statusFinais())
                ->when($ignorarOrdemId, fn ($query) => $query->where('os_reserva.id', '!=', $ignorarOrdemId))
                ->where(function ($query): void {
                    $query->whereColumn('os_reserva.chip_anterior_id', 'chips.id')
                        ->orWhereColumn('os_reserva.chip_novo_id', 'chips.id');
                });
        });
    }

    private static function reservasAtivas(?int $ignorarOrdemId = null): Builder
    {
        return OrdemServico::query()
            ->whereNotIn('status', self::statusFinais())
            ->when($ignorarOrdemId, fn (Builder $query) => $query->whereKeyNot($ignorarOrdemId));
    }

    private static function statusFinais(): array
    {
        return [OrdemServicoStatus::FINALIZADA->value, OrdemServicoStatus::CANCELADA->value];
    }
}
