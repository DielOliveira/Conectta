<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use App\Models\Cliente;
use App\Models\Permission;
use App\Models\StatusCliente;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    public ?int $clienteStatusFiltro = null;

    public ?string $clienteCadastroInicio = null;

    public ?string $clienteCadastroFinal = null;

    public string $clientePesquisa = '';

    public function mount(): void
    {
        parent::mount();

        $this->clienteStatusFiltro = StatusCliente::query()
            ->where('label', 'Ativo')
            ->value('id');
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'cliente') && method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function limparFiltrosClientes(): void
    {
        $this->clienteStatusFiltro = StatusCliente::query()
            ->where('label', 'Ativo')
            ->value('id');
        $this->clienteCadastroInicio = null;
        $this->clienteCadastroFinal = null;
        $this->clientePesquisa = '';

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function aplicarFiltrosClientes(Builder $query): Builder
    {
        $search = trim($this->clientePesquisa);
        $digits = preg_replace('/\D+/', '', $search);

        return $query
            ->when($this->clienteStatusFiltro, fn (Builder $query, int $statusId): Builder => $query->where('status_cliente_id', $statusId))
            ->when($this->clienteCadastroInicio, fn (Builder $query, string $date): Builder => $query->whereDate('data_adesao', '>=', $date))
            ->when($this->clienteCadastroFinal, fn (Builder $query, string $date): Builder => $query->whereDate('data_adesao', '<=', $date))
            ->when($search !== '', function (Builder $query) use ($search, $digits): Builder {
                return $query->where(function (Builder $query) use ($search, $digits): void {
                    $query->where('nome', 'like', '%' . $search . '%');

                    if ($digits !== '') {
                        $query->orWhere('cpf_cnpj', 'like', '%' . $digits . '%');
                    }
                });
            });
    }

    public function exportarCsv(): StreamedResponse
    {
        $clientes = $this->aplicarFiltrosClientes(
            Cliente::query()
                ->whereNull('data_exclusao')
                ->with('statusCliente')
                ->withCount('veiculosAtivos')
        )
            ->orderBy('nome')
            ->limit(10000)
            ->get();

        $fileName = 'clientes-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($clientes): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Nome', 'CPF ou CNPJ', 'Data de Adesao', 'Status', 'Qtd.'], ';');

            foreach ($clientes as $cliente) {
                fputcsv($handle, [
                    $cliente->nome,
                    $cliente->cpf_cnpj_formatado,
                    $cliente->data_adesao?->format('d/m/Y'),
                    $cliente->statusCliente?->label,
                    $cliente->veiculos_ativos_count,
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
                ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false),
        ];
    }
}