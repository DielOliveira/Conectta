<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServicoFoto;
use Carbon\CarbonImmutable;
use Throwable;

class OrdemServicoFotoArquivoService
{
    public function __construct(private readonly OrdemServicoFotoStorage $storage) {}

    /** @return array{processadas:int, arquivadas:int, erros:int} */
    public function processar(?CarbonImmutable $limite = null, int $quantidade = 100): array
    {
        $limite ??= CarbonImmutable::now()->subMonth();
        $resultado = ['processadas' => 0, 'arquivadas' => 0, 'erros' => 0];

        OrdemServicoFoto::query()
            ->whereHas('ordemServico', fn ($query) => $query
                ->where('status', 'finalizada')
                ->whereNotNull('finalizada_em')
                ->where('finalizada_em', '<=', $limite))
            ->where('caminho', 'not like', $this->prefixoRemoto().'%')
            ->orderBy('id')
            ->limit(max(1, $quantidade))
            ->get()
            ->each(function (OrdemServicoFoto $foto) use (&$resultado): void {
                $resultado['processadas']++;
                try {
                    if ($this->storage->arquivar($foto)) {
                        $resultado['arquivadas']++;
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $resultado['erros']++;
                }
            });

        return $resultado;
    }

    private function prefixoRemoto(): string
    {
        return rtrim((string) config('ordens_servico.fotos.rclone_remote'), ':').':';
    }
}
