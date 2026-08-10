<?php

namespace App\Filament\Resources\OrdensServico\Pages;

use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrdensServico extends ListRecords
{
    protected static string $resource = OrdemServicoResource::class;

    public ?string $ordemServicoStatusFiltro = null;

    public ?string $ordemServicoTipoFiltro = null;

    public ?int $ordemServicoTecnicoFiltro = null;

    public ?string $ordemServicoPeriodoInicio = null;

    public ?string $ordemServicoPeriodoFim = null;

    public string $ordemServicoPesquisa = '';

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'ordemServico') && method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function limparFiltrosOrdensServico(): void
    {
        $this->ordemServicoStatusFiltro = null;
        $this->ordemServicoTipoFiltro = null;
        $this->ordemServicoTecnicoFiltro = null;
        $this->ordemServicoPeriodoInicio = null;
        $this->ordemServicoPeriodoFim = null;
        $this->ordemServicoPesquisa = '';

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function aplicarFiltrosOrdensServico(Builder $query): Builder
    {
        $search = trim($this->ordemServicoPesquisa);
        $numero = preg_replace('/\D+/', '', $search);

        return $query
            ->when($this->ordemServicoStatusFiltro, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($this->ordemServicoTipoFiltro, fn (Builder $query, string $tipo): Builder => $query->where('tipo', $tipo))
            ->when($this->ordemServicoTecnicoFiltro, fn (Builder $query, int $tecnicoId): Builder => $query->where('tecnico_id', $tecnicoId))
            ->when($this->ordemServicoPeriodoInicio, fn (Builder $query, string $data): Builder => $query->where('agendado_em', '>=', $data.' 00:00:00'))
            ->when($this->ordemServicoPeriodoFim, fn (Builder $query, string $data): Builder => $query->where('agendado_em', '<=', $data.' 23:59:59'))
            ->when($search !== '', function (Builder $query) use ($search, $numero): void {
                $query->where(function (Builder $query) use ($search, $numero): void {
                    $query
                        ->whereHas('cliente', fn (Builder $query): Builder => $query->where('nome', 'like', '%'.$search.'%'))
                        ->orWhereHas('veiculo', fn (Builder $query): Builder => $query
                            ->where('placa', 'like', '%'.$search.'%')
                            ->orWhere('associado', 'like', '%'.$search.'%'));

                    if ($numero !== '') {
                        $query->orWhere('numero', 'like', '%'.$numero.'%');
                    }
                });
            });
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nova ordem de serviço')];
    }
}
