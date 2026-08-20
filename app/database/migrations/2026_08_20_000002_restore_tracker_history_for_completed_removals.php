<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $statusCanceladoId = DB::table('status_rastreadores')
            ->where('label', 'Cancelado')
            ->value('id');

        if ($statusCanceladoId === null) {
            return;
        }

        $ultimasRetiradas = DB::table('ordens_servico')
            ->selectRaw('MAX(id) as id')
            ->where('tipo', 'retirada')
            ->where('status', 'finalizada')
            ->whereNotNull('rastreador_anterior_id')
            ->groupBy('veiculo_id');

        $ordens = DB::table('ordens_servico as os')
            ->joinSub($ultimasRetiradas, 'ultima_retirada', fn ($join) => $join->on('ultima_retirada.id', '=', 'os.id'))
            ->join('veiculos as v', 'v.id', '=', 'os.veiculo_id')
            ->whereNull('v.rastreador_id')
            ->orderBy('os.id')
            ->get([
                'os.id',
                'os.veiculo_id',
                'os.rastreador_anterior_id',
                'os.tecnico_id',
                'os.finalizada_em',
                'v.tecnico_remocao_id',
                'v.data_retirada',
            ]);

        $veiculosAtualizados = [];

        foreach ($ordens as $ordem) {
            $dados = [
                'rastreador_id' => $ordem->rastreador_anterior_id,
                'status_rastreador_id' => $statusCanceladoId,
                'updated_at' => now(),
            ];

            if ($ordem->tecnico_remocao_id === null && $ordem->tecnico_id !== null) {
                $dados['tecnico_remocao_id'] = $ordem->tecnico_id;
            }

            if ($ordem->data_retirada === null && $ordem->finalizada_em !== null) {
                $dados['data_retirada'] = substr((string) $ordem->finalizada_em, 0, 10);
            }

            $atualizados = DB::table('veiculos')
                ->where('id', $ordem->veiculo_id)
                ->whereNull('rastreador_id')
                ->update($dados);

            if ($atualizados === 1) {
                $veiculosAtualizados[] = (int) $ordem->veiculo_id;
            }
        }

        if ($veiculosAtualizados !== []) {
            DB::table('audit_logs')->insert([
                'user_id' => null,
                'acao' => 'os.retiradas_historico_rastreador_restaurado',
                'entidade_tipo' => 'sistema',
                'entidade_id' => null,
                'descricao' => 'Vinculos historicos de rastreadores retirados por OS foram restaurados nos veiculos cancelados.',
                'antes' => null,
                'depois' => null,
                'ip' => null,
                'user_agent' => null,
                'contexto' => json_encode([
                    'quantidade' => count($veiculosAtualizados),
                    'veiculo_ids' => $veiculosAtualizados,
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // O vinculo restaurado representa historico operacional e nao deve ser apagado no rollback.
    }
};
