<?php

namespace App\Services\Veiculo;

use App\Models\Chip;
use App\Models\Cliente;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VeiculoRetencaoService
{
    public function reter(Veiculo $registro, int $novoClienteId, string $dataRetencao, User $operador): Veiculo
    {
        $dataRetencao = Validator::make(
            ['data_retencao' => $dataRetencao],
            ['data_retencao' => ['required', 'date_format:Y-m-d', 'before_or_equal:today']],
            [
                'data_retencao.required' => 'Informe a data da retenção.',
                'data_retencao.date_format' => 'Informe uma data de retenção válida.',
                'data_retencao.before_or_equal' => 'A data de retenção não pode estar no futuro.',
            ],
        )->validate()['data_retencao'];

        return DB::transaction(function () use ($registro, $novoClienteId, $dataRetencao, $operador): Veiculo {
            $veiculo = Veiculo::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($registro->getKey());

            if ($veiculo->trashed()) {
                throw ValidationException::withMessages([
                    'veiculo' => 'Este veículo foi excluído e não pode passar por retenção.',
                ]);
            }

            $novoCliente = Cliente::query()
                ->whereNull('data_exclusao')
                ->lockForUpdate()
                ->find($novoClienteId);

            if ($novoCliente === null) {
                throw ValidationException::withMessages([
                    'novo_cliente_id' => 'Selecione um cliente ativo no cadastro.',
                ]);
            }

            if ((int) $veiculo->cliente_id === (int) $novoCliente->id) {
                throw ValidationException::withMessages([
                    'novo_cliente_id' => 'Selecione um cliente diferente do cliente atual.',
                ]);
            }

            $ativoId = StatusRastreador::query()->where('label', 'Ativo')->value('id');
            $canceladoId = StatusRastreador::query()->where('label', 'Cancelado')->value('id');
            $tecnicos = Tecnico::query()->get();
            $tecnicoRetencao = $tecnicos
                ->first(fn (Tecnico $tecnico): bool => mb_strtolower(trim($tecnico->nome)) === 'retenção')
                ?? $tecnicos->first(fn (Tecnico $tecnico): bool => mb_strtolower(trim($tecnico->nome)) === 'retencao');

            if ($ativoId === null || $canceladoId === null || $tecnicoRetencao === null) {
                throw ValidationException::withMessages([
                    'retencao' => 'Os cadastros Ativo, Cancelado e técnico Retenção são obrigatórios para esta operação.',
                ]);
            }

            if ((int) $veiculo->status_rastreador_id !== (int) $ativoId) {
                throw ValidationException::withMessages([
                    'veiculo' => 'Somente veículos ativos podem passar por retenção.',
                ]);
            }

            if ($veiculo->ordensServico()->ativas()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'veiculo' => 'Este veículo possui uma O.S. ativa. Cancele a O.S. antes de realizar a retenção.',
                ]);
            }

            $rastreador = $veiculo->rastreador_id !== null
                ? Rastreador::query()->lockForUpdate()->find($veiculo->rastreador_id)
                : null;
            $chip = $rastreador?->chip_id !== null
                ? Chip::query()->lockForUpdate()->find($rastreador->chip_id)
                : null;

            if ($rastreador === null || (int) $rastreador->status_rastreador_id !== (int) $ativoId) {
                throw ValidationException::withMessages([
                    'rastreador' => 'A retenção exige um rastreador ativo vinculado ao veículo.',
                ]);
            }

            if ($chip === null || (int) $chip->status_rastreador_id !== (int) $ativoId) {
                throw ValidationException::withMessages([
                    'chip' => 'A retenção exige um chip ativo vinculado ao rastreador.',
                ]);
            }

            $antes = $this->snapshotAuditoria($veiculo);
            $clienteAnterior = $veiculo->cliente;

            DB::table('veiculos')
                ->where('id', $veiculo->id)
                ->update([
                    'status_rastreador_id' => $canceladoId,
                    'tecnico_remocao_id' => $tecnicoRetencao->id,
                    'tecnico_remocao' => $tecnicoRetencao->nome,
                    'data_retirada' => $dataRetencao,
                    'motivo_cancelamento' => 'Retenção para o cliente '.$novoCliente->nome.'.',
                    'cancelado_em' => now(),
                    'cancelado_por' => $operador->id,
                    'updated_at' => now(),
                ]);

            $novoVeiculo = $veiculo->replicate([
                'cliente_id',
                'status_rastreador_id',
                'data_instalacao',
                'data_retirada',
                'tecnico_remocao_id',
                'tecnico_remocao',
                'motivo_cancelamento',
                'cancelado_em',
                'cancelado_por',
                'data_exclusao',
            ]);
            $novoVeiculo->forceFill([
                'cliente_id' => $novoCliente->id,
                'status_rastreador_id' => $ativoId,
                'data_instalacao' => $dataRetencao,
                'data_retirada' => null,
                'tecnico_remocao_id' => null,
                'tecnico_remocao' => null,
                'motivo_cancelamento' => null,
                'cancelado_em' => null,
                'cancelado_por' => null,
                'data_exclusao' => null,
            ]);
            $novoVeiculo->save();

            // O evento de criação sincroniza o instalador a partir da posse atual do
            // equipamento. Na retenção, porém, prevalece o instalador do vínculo anterior.
            DB::table('veiculos')
                ->where('id', $novoVeiculo->id)
                ->update([
                    'tecnico_instala_id' => $veiculo->tecnico_instala_id,
                    'instalador' => $veiculo->instalador,
                ]);
            $novoVeiculo->refresh();

            $veiculo->refresh();
            $clienteAnterior?->syncStatusFromVeiculos();
            $novoCliente->syncStatusFromVeiculos();

            AuditLogger::registrar(
                'veiculo.retencao_origem',
                'Vínculo anterior do veículo cancelado por retenção.',
                $veiculo,
                antes: $antes,
                depois: $this->snapshotAuditoria($veiculo),
                contexto: [
                    'data_retencao' => $dataRetencao,
                    'cliente_anterior_id' => $clienteAnterior?->id,
                    'novo_cliente_id' => $novoCliente->id,
                    'novo_veiculo_id' => $novoVeiculo->id,
                    'rastreador_id' => $rastreador->id,
                    'chip_id' => $chip->id,
                ],
            );

            AuditLogger::registrar(
                'veiculo.retencao_destino',
                'Novo vínculo de veículo criado por retenção.',
                $novoVeiculo,
                depois: $this->snapshotAuditoria($novoVeiculo),
                contexto: [
                    'data_retencao' => $dataRetencao,
                    'cliente_anterior_id' => $clienteAnterior?->id,
                    'veiculo_anterior_id' => $veiculo->id,
                    'novo_cliente_id' => $novoCliente->id,
                    'rastreador_id' => $rastreador->id,
                    'chip_id' => $chip->id,
                ],
            );

            return $novoVeiculo;
        });
    }

    /** @return array<string, mixed> */
    private function snapshotAuditoria(Veiculo $veiculo): array
    {
        $snapshot = AuditLogger::snapshot($veiculo);

        if (filled($snapshot['senha'] ?? null)) {
            $snapshot['senha'] = '[redigido]';
        }

        return $snapshot;
    }
}
