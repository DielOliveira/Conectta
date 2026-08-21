<?php

namespace App\Services\Veiculo;

use App\Models\User;
use App\Models\Veiculo;
use App\Services\OrdemServico\OrdemServicoService;
use Illuminate\Support\Facades\DB;

class VeiculoExclusaoService
{
    private const MOTIVO_CANCELAMENTO = 'Veículo excluído do cadastro.';

    public function __construct(private readonly OrdemServicoService $ordensServico) {}

    /**
     * @param  iterable<Veiculo>  $veiculos
     */
    public function excluir(iterable $veiculos, User $operador): void
    {
        DB::transaction(function () use ($veiculos, $operador): void {
            foreach ($veiculos as $registro) {
                $veiculo = Veiculo::query()
                    ->withTrashed()
                    ->lockForUpdate()
                    ->findOrFail($registro->getKey());

                if ($veiculo->trashed()) {
                    continue;
                }

                $ordensAtivas = $veiculo->ordensServico()
                    ->ativas()
                    ->lockForUpdate()
                    ->get();

                foreach ($ordensAtivas as $ordem) {
                    $this->ordensServico->cancelar(
                        $ordem,
                        self::MOTIVO_CANCELAMENTO,
                        $operador,
                    );
                }

                $veiculo->delete();
            }
        });
    }
}
