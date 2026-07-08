<?php

namespace App\Filament\Resources\Rastreadores\Pages;

use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Models\Permission;
use App\Models\StatusRastreador;
use App\Models\Veiculo;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;

class ListRastreadores extends ListRecords
{
    private const BUSCA_COMPARTILHADA_SESSION = 'conectta.busca_cadastros';

    protected static string $resource = RastreadorResource::class;

    public ?int $rastreadorClienteFiltro = null;

    public ?int $rastreadorStatusFiltro = null;

    public ?string $rastreadorInstalacaoInicio = null;

    public ?string $rastreadorInstalacaoFinal = null;

    public ?string $rastreadorRemocaoInicio = null;

    public ?string $rastreadorRemocaoFinal = null;

    public string $rastreadorPesquisa = '';

    public function mount(): void
    {
        parent::mount();

        $this->rastreadorClienteFiltro = request()->integer('cliente_id') ?: null;
        $this->rastreadorPesquisa = (string) session(self::BUSCA_COMPARTILHADA_SESSION, '');
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'rastreador') && method_exists($this, 'resetPage')) {
            $this->resetPage();
        }

        if ($property === 'rastreadorPesquisa') {
            $this->sincronizarBuscaCompartilhada($this->rastreadorPesquisa);
        }
    }

    public function limparFiltrosRastreadores(): void
    {
        $this->rastreadorClienteFiltro = request()->integer('cliente_id') ?: null;
        $this->rastreadorStatusFiltro = null;
        $this->rastreadorInstalacaoInicio = null;
        $this->rastreadorInstalacaoFinal = null;
        $this->rastreadorRemocaoInicio = null;
        $this->rastreadorRemocaoFinal = null;
        $this->rastreadorPesquisa = '';
        session()->forget(self::BUSCA_COMPARTILHADA_SESSION);

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function aplicarFiltrosRastreadores(Builder $query): Builder
    {
        $search = trim($this->rastreadorPesquisa);
        $digits = preg_replace('/\D+/', '', $search);
        $buscarDocumentoOuImei = strlen($digits) >= 6;

        return $query
            ->when($this->rastreadorClienteFiltro, fn (Builder $query, int $clienteId): Builder => $query->where('cliente_id', $clienteId))
            ->when($this->rastreadorStatusFiltro, fn (Builder $query, int $statusId): Builder => $query->where('status_rastreador_id', $statusId))
            ->when($this->rastreadorInstalacaoInicio, fn (Builder $query, string $date): Builder => $query->whereDate('data_instalacao', '>=', $date))
            ->when($this->rastreadorInstalacaoFinal, fn (Builder $query, string $date): Builder => $query->whereDate('data_instalacao', '<=', $date))
            ->when($this->rastreadorRemocaoInicio, fn (Builder $query, string $date): Builder => $query->whereDate('data_retirada', '>=', $date))
            ->when($this->rastreadorRemocaoFinal, fn (Builder $query, string $date): Builder => $query->whereDate('data_retirada', '<=', $date))
            ->when($search !== '', function (Builder $query) use ($search, $digits, $buscarDocumentoOuImei): Builder {
                return $query->where(function (Builder $query) use ($search, $digits, $buscarDocumentoOuImei): void {
                    $query
                        ->where('veiculo', 'like', '%' . $search . '%')
                        ->orWhere('placa', 'like', '%' . $search . '%')
                        ->orWhereHas('cliente', function (Builder $query) use ($search, $digits, $buscarDocumentoOuImei): Builder {
                            return $query
                                ->where('nome', 'like', '%' . $search . '%')
                                ->when($buscarDocumentoOuImei, fn (Builder $query): Builder => $query->orWhere('cpf_cnpj', 'like', '%' . $digits . '%'));
                        });

                    if ($buscarDocumentoOuImei) {
                        $query->orWhereHas('rastreador', fn (Builder $query): Builder => $query->where('imei', 'like', '%' . $digits . '%'));
                    }
                });
            });
    }

    public function exportarCsv(): StreamedResponse
    {
        $rastreadores = $this->aplicarFiltrosRastreadores(
            Veiculo::query()
                ->whereNull('data_exclusao')
                ->with(['cliente', 'rastreador', 'tipoVeiculo', 'statusRastreador'])
        )
            ->orderByDesc('updated_at')
            ->limit(10000)
            ->get();

        $fileName = 'rastreadores-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($rastreadores): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Rastreador', 'Cliente', 'Veiculo', 'Tipo', 'Placa', 'Status', 'Instalacao', 'Remocao'], ';');

            foreach ($rastreadores as $rastreador) {
                fputcsv($handle, [
                    $rastreador->rastreador?->imei,
                    $rastreador->cliente?->nome,
                    $rastreador->veiculo,
                    $rastreador->tipoVeiculo?->label,
                    $rastreador->placa,
                    $rastreador->statusRastreador?->label,
                    $rastreador->data_instalacao?->format('d/m/Y'),
                    $rastreador->data_retirada?->format('d/m/Y'),
                ], ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
                ->label('Adicionar')
                ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false),
        ];
    }

    private function sincronizarBuscaCompartilhada(string $busca): void
    {
        $busca = trim($busca);

        if ($busca === '') {
            session()->forget(self::BUSCA_COMPARTILHADA_SESSION);

            return;
        }

        session()->put(self::BUSCA_COMPARTILHADA_SESSION, $busca);
    }
}
