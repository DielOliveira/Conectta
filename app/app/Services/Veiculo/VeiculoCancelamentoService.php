<?php

namespace App\Services\Veiculo;

use App\Models\Chip;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\Audit\AuditLogger;
use App\Services\Estoque\EquipamentoStatusWorkflow;
use App\Services\OrdemServico\OrdemServicoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VeiculoCancelamentoService
{
    public function __construct(private readonly OrdemServicoService $ordensServico) {}

    public function cancelarSemRetirada(Veiculo $registro, string $motivo, User $operador): void
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw ValidationException::withMessages([
                'motivo' => 'Informe o motivo do cancelamento.',
            ]);
        }

        DB::transaction(function () use ($registro, $motivo, $operador): void {
            $veiculo = Veiculo::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($registro->getKey());

            if ($veiculo->trashed()) {
                throw ValidationException::withMessages([
                    'veiculo' => 'Este veículo foi excluído e não pode ser cancelado.',
                ]);
            }

            $ativoId = StatusRastreador::query()->where('label', 'Ativo')->value('id');
            $canceladoId = StatusRastreador::query()->where('label', 'Cancelado')->value('id');
            $tecnicoLixoId = Tecnico::query()->where('nome', 'Lixo')->value('id');

            if ($ativoId === null || $canceladoId === null || $tecnicoLixoId === null) {
                throw ValidationException::withMessages([
                    'cancelamento' => 'Os cadastros Ativo, Cancelado e técnico Lixo são obrigatórios para esta operação.',
                ]);
            }

            if ((int) $veiculo->status_rastreador_id !== (int) $ativoId) {
                throw ValidationException::withMessages([
                    'veiculo' => 'Somente veículos ativos podem ser cancelados sem retirada.',
                ]);
            }

            $ordensAtivas = $veiculo->ordensServico()
                ->ativas()
                ->lockForUpdate()
                ->get();

            foreach ($ordensAtivas as $ordem) {
                $this->ordensServico->cancelar(
                    $ordem,
                    'Veículo cancelado sem retirada: '.$motivo,
                    $operador,
                );
            }

            $equipamentoCompartilhado = $veiculo->rastreador_id !== null
                && Veiculo::query()
                    ->whereKeyNot($veiculo->id)
                    ->where('rastreador_id', $veiculo->rastreador_id)
                    ->whereNull('data_exclusao')
                    ->where('status_rastreador_id', $ativoId)
                    ->exists();

            $antes = AuditLogger::snapshot($veiculo);

            Veiculo::query()
                ->whereKey($veiculo->id)
                ->update([
                    'status_rastreador_id' => $canceladoId,
                    'motivo_cancelamento' => $motivo,
                    'cancelado_em' => now(),
                    'cancelado_por' => $operador->id,
                    'updated_at' => now(),
                ]);

            if ($veiculo->rastreador_id !== null && ! $equipamentoCompartilhado) {
                $this->cancelarEquipamento(
                    (int) $veiculo->rastreador_id,
                    (int) $canceladoId,
                    (int) $tecnicoLixoId,
                    $veiculo,
                    $motivo,
                );
            }

            $veiculo->refresh();
            $veiculo->cliente?->syncStatusFromVeiculos();

            AuditLogger::registrar(
                'veiculo.cancelado_sem_retirada',
                'Veículo cancelado sem retirada do equipamento.',
                $veiculo,
                antes: $antes,
                depois: AuditLogger::snapshot($veiculo),
                contexto: [
                    'motivo' => $motivo,
                    'equipamento_destino' => $equipamentoCompartilhado ? 'preservado_em_veiculo_ativo' : 'tecnico_lixo',
                    'ordens_canceladas' => $ordensAtivas->pluck('id')->all(),
                ],
            );
        });
    }

    private function cancelarEquipamento(
        int $rastreadorId,
        int $canceladoId,
        int $tecnicoLixoId,
        Veiculo $veiculo,
        string $motivo,
    ): void {
        $rastreador = Rastreador::query()->lockForUpdate()->find($rastreadorId);

        if ($rastreador === null) {
            return;
        }

        $rastreadorAntes = AuditLogger::snapshot($rastreador);
        $chip = $rastreador->chip_id !== null
            ? Chip::query()->lockForUpdate()->find($rastreador->chip_id)
            : null;
        $chipAntes = $chip ? AuditLogger::snapshot($chip) : null;

        EquipamentoStatusWorkflow::executar(function () use ($rastreador, $chip, $canceladoId, $tecnicoLixoId): void {
            $rastreador->update([
                'status_rastreador_id' => $canceladoId,
                'tecnico_id' => $tecnicoLixoId,
            ]);

            $chip?->update([
                'status_rastreador_id' => $canceladoId,
                'tecnico_id' => $tecnicoLixoId,
            ]);
        });

        AuditLogger::registrar(
            'rastreador.cancelado_sem_retirada',
            'Rastreador enviado ao técnico Lixo após cancelamento sem retirada.',
            $rastreador,
            antes: $rastreadorAntes,
            depois: AuditLogger::snapshot($rastreador->refresh()),
            contexto: ['veiculo_id' => $veiculo->id, 'motivo' => $motivo],
        );

        if ($chip !== null) {
            AuditLogger::registrar(
                'chip.cancelado_sem_retirada',
                'Chip enviado ao técnico Lixo após cancelamento sem retirada.',
                $chip,
                antes: $chipAntes,
                depois: AuditLogger::snapshot($chip->refresh()),
                contexto: ['veiculo_id' => $veiculo->id, 'rastreador_id' => $rastreador->id],
            );
        }
    }
}
