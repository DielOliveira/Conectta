<?php

namespace App\Filament\Resources\Contratos\Pages;

use App\Filament\Resources\Contratos\ContratoResource;
use App\Models\Contrato;
use App\Models\Permission;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListContratos extends ListRecords
{
    protected static string $resource = ContratoResource::class;

    public ?int $contratoStatusFiltro = null;

    public ?int $contratoTipoFiltro = null;

    public string $contratoPesquisa = '';

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'contrato') && method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function limparFiltrosContratos(): void
    {
        $this->contratoStatusFiltro = null;
        $this->contratoTipoFiltro = null;
        $this->contratoPesquisa = '';

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function aplicarFiltrosContratos(Builder $query): Builder
    {
        $search = trim($this->contratoPesquisa);
        $digits = preg_replace('/\D+/', '', $search);

        return $query
            ->with(['veiculo.cliente', 'veiculo.rastreador', 'tipoContrato', 'statusContrato'])
            ->when($this->contratoStatusFiltro, fn (Builder $query, int $statusId): Builder => $query->where('status_contrato_id', $statusId))
            ->when($this->contratoTipoFiltro, fn (Builder $query, int $tipoId): Builder => $query->where('tipo_contrato_id', $tipoId))
            ->when($search !== '', function (Builder $query) use ($search, $digits): Builder {
                return $query->where(function (Builder $query) use ($search, $digits): void {
                    $query
                        ->whereHas('veiculo', function (Builder $query) use ($search): void {
                            $query->where('veiculo', 'like', '%' . $search . '%')
                                ->orWhere('placa', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('veiculo.cliente', fn (Builder $query): Builder => $query->where('nome', 'like', '%' . $search . '%'));

                    if ($digits !== '') {
                        $query
                            ->orWhereHas('veiculo.rastreador', fn (Builder $query): Builder => $query->where('imei', 'like', '%' . $digits . '%'))
                            ->orWhereHas('veiculo.cliente', fn (Builder $query): Builder => $query->where('cpf_cnpj', 'like', '%' . $digits . '%'));
                    }
                });
            });
    }

    public function exportarCsv(): StreamedResponse
    {
        $query = $this->aplicarFiltrosContratos(Contrato::query());

        $this->applySortingToTableQuery($query);

        $contratos = $query
            ->limit(10000)
            ->get();

        $fileName = 'contratos-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($contratos): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Cliente', 'CPF/CNPJ', 'Rastreador', 'Veiculo', 'Placa', 'Tipo', 'Status', 'Token', 'Criado em'], ';');

            foreach ($contratos as $contrato) {
                fputcsv($handle, [
                    $contrato->veiculo?->cliente?->nome,
                    $contrato->veiculo?->cliente?->cpf_cnpj_formatado,
                    $contrato->veiculo?->rastreador?->imei,
                    $contrato->veiculo?->veiculo,
                    $contrato->veiculo?->placa,
                    $contrato->tipoContrato?->label,
                    $contrato->statusContrato?->label,
                    $contrato->doc_token,
                    $contrato->created_at?->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar')
                ->label('Exportar')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->action('exportarCsv'),
            CreateAction::make()
                ->label('Criar Contrato')
                ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false),
        ];
    }
}
