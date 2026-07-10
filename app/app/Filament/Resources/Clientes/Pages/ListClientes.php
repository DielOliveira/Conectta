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
    private const BUSCA_COMPARTILHADA_SESSION = 'conectta.busca_cadastros';

    private const STATUS_CLIENTE_COMPARTILHADO_SESSION = 'conectta.status_cliente';

    protected static string $resource = ClienteResource::class;

    public ?int $clienteStatusFiltro = null;

    public ?string $clienteCadastroInicio = null;

    public ?string $clienteCadastroFinal = null;

    public string $clientePesquisa = '';

    public function mount(): void
    {
        parent::mount();

        $this->clienteStatusFiltro = $this->statusClienteCompartilhado();
        $this->clientePesquisa = (string) session(self::BUSCA_COMPARTILHADA_SESSION, '');
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'cliente') && method_exists($this, 'resetPage')) {
            $this->resetPage();
        }

        if ($property === 'clientePesquisa') {
            $this->sincronizarBuscaCompartilhada($this->clientePesquisa);
        }

        if ($property === 'clienteStatusFiltro') {
            $this->sincronizarStatusClienteCompartilhado($this->clienteStatusFiltro);
        }
    }

    public function limparFiltrosClientes(): void
    {
        $this->clienteStatusFiltro = $this->statusAtivoId();
        $this->clienteCadastroInicio = null;
        $this->clienteCadastroFinal = null;
        $this->clientePesquisa = '';
        session()->forget(self::BUSCA_COMPARTILHADA_SESSION);
        $this->sincronizarStatusClienteCompartilhado($this->clienteStatusFiltro);

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
        $query = $this->aplicarFiltrosClientes(
            Cliente::query()
                ->whereNull('data_exclusao')
                ->with(['origem', 'statusCliente', 'vendedor'])
                ->withCount('veiculosAtivos')
        );

        $this->applySortingToTableQuery($query);

        $clientes = $query
            ->limit(10000)
            ->get();

        $fileName = 'clientes-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($clientes): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Data Adesao',
                'Nome',
                'RG',
                'CPF CNPJ',
                'Telefone',
                'DN',
                'Email',
                'Status',
                'Empresa',
                'Qtd Veiculos',
                'Origem',
                'Vendedor',
            ], ';');

            foreach ($clientes as $cliente) {
                fputcsv($handle, [
                    $cliente->data_adesao?->format('d-m-Y'),
                    $cliente->nome,
                    $cliente->rg,
                    $cliente->cpf_cnpj_formatado,
                    $cliente->telefone1,
                    $cliente->nascimento?->format('d-m-Y'),
                    $cliente->email,
                    $cliente->statusCliente?->label,
                    $cliente->empresa,
                    $cliente->veiculos_ativos_count,
                    $cliente->origem?->label,
                    $cliente->vendedor?->nome,
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

    private function sincronizarBuscaCompartilhada(string $busca): void
    {
        $busca = trim($busca);

        if ($busca === '') {
            session()->forget(self::BUSCA_COMPARTILHADA_SESSION);

            return;
        }

        session()->put(self::BUSCA_COMPARTILHADA_SESSION, $busca);
    }

    private function statusClienteCompartilhado(): ?int
    {
        if (session()->exists(self::STATUS_CLIENTE_COMPARTILHADO_SESSION)) {
            $statusId = session(self::STATUS_CLIENTE_COMPARTILHADO_SESSION);

            return $statusId ? (int) $statusId : null;
        }

        return $this->statusAtivoId();
    }

    private function sincronizarStatusClienteCompartilhado(?int $statusId): void
    {
        session()->put(self::STATUS_CLIENTE_COMPARTILHADO_SESSION, $statusId ?: null);
    }

    private function statusAtivoId(): ?int
    {
        return StatusCliente::query()
            ->where('label', 'Ativo')
            ->value('id');
    }
}
