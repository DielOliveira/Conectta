<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Permission;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class Boletos extends Page
{
    protected static ?string $slug = 'boletos';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Boletos';

    protected static ?string $title = 'Boletos Gerados';

    protected string $view = 'filament.pages.boletos';

    protected Width|string|null $maxWidth = Width::Full;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::BOLETOS_LEITURA) ?? false;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => 'Financeiro',
            self::getUrl() => 'Boletos',
        ];
    }

    public string $criadoInicio = '';

    public string $criadoFim = '';

    public string $status = '';

    public string $pesquisa = '';

    public int $pagina = 1;

    public int $porPagina = 10;

    public function mount(): void
    {
        $this->criadoInicio = '';
        $this->criadoFim = '';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['criadoInicio', 'criadoFim', 'status', 'pesquisa', 'porPagina'], true)) {
            $this->pagina = 1;
        }
    }

    public function paginaAnterior(): void
    {
        $this->pagina = max(1, $this->pagina - 1);
    }

    public function paginaProxima(): void
    {
        $this->pagina = min($this->totalPaginas(), $this->pagina + 1);
    }

    public function irParaPagina(int $pagina): void
    {
        $this->pagina = max(1, min($pagina, $this->totalPaginas()));
    }

    public function invoices(): EloquentCollection
    {
        return $this->invoicesQuery()
            ->offset(($this->pagina - 1) * $this->porPagina)
            ->limit($this->porPagina)
            ->get();
    }

    public function totalInvoices(): int
    {
        return (clone $this->invoicesQuery())->toBase()->getCountForPagination();
    }

    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->totalInvoices() / $this->porPagina));
    }

    public function inicioPagina(): int
    {
        if ($this->totalInvoices() === 0) {
            return 0;
        }

        return (($this->pagina - 1) * $this->porPagina) + 1;
    }

    public function fimPagina(): int
    {
        return min($this->totalInvoices(), $this->pagina * $this->porPagina);
    }

    public function paginasVisiveis(): array
    {
        $total = $this->totalPaginas();

        if ($total <= 7) {
            return range(1, $total);
        }

        $pages = collect([1, $this->pagina - 1, $this->pagina, $this->pagina + 1, $total])
            ->filter(fn (int $page): bool => $page >= 1 && $page <= $total)
            ->unique()
            ->sort()
            ->values();

        $visible = [];
        $previous = null;

        foreach ($pages as $page) {
            if ($previous !== null && $page > $previous + 1) {
                $visible[] = '...';
            }

            $visible[] = $page;
            $previous = $page;
        }

        return $visible;
    }

    public function statusBoletos(): array
    {
        return Invoice::query()
            ->whereNotNull('status')
            ->where('status', '<>', '')
            ->distinct()
            ->pluck('status')
            ->map(fn (string $status): array => [
                'value' => $status,
                'label' => $this->statusBoletoLabel($status),
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }

    public function statusBoletoLabel(?string $status): string
    {
        $status = trim((string) $status);

        if ($status === '') {
            return '';
        }

        $key = str($status)
            ->lower()
            ->ascii()
            ->replace(['-', ' '], '_')
            ->toString();

        return match ($key) {
            'paid', 'pago' => 'Pago',
            'canceled', 'cancelled', 'cancelado' => 'Cancelado',
            'overdue', 'late', 'atrasado' => 'Atrasado',
            'processing', 'processando' => 'Processando',
            'waiting_payment', 'waitingpayment', 'waiting', 'pending', 'aguardando_pagamento' => 'Aguardando Pagamento',
            default => str($status)->headline()->toString(),
        };
    }

    public function moeda(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return number_format((float) $valor, 2, ',', '.');
    }

    public function data(?\DateTimeInterface $date): string
    {
        return $date?->format('Y/m/d') ?? '';
    }

    public function referencia(?int $mes, ?int $ano): string
    {
        if ($mes === null || $ano === null) {
            return '';
        }

        return $mes . '/' . $ano;
    }

    public function invoiceUrl(Invoice $invoice): ?string
    {
        $url = trim((string) $invoice->link_checkout);
        $hash = trim((string) $invoice->hash_id);

        return $url !== '' ? $url : ($hash === '' ? null : 'https://checkout-pay.lytex.com.br/fatura/' . $hash);
    }

    public function boletoUrl(Invoice $invoice): ?string
    {
        $url = trim((string) $invoice->link_boleto);
        $hash = trim((string) $invoice->hash_id);

        return $url !== '' ? $url : ($hash === '' ? null : 'https://public-api-pay.lytex.com.br/v1/invoices/print/' . $hash);
    }

    public function statusBoletoClasse(?string $status): string
    {
        return match (str($status ?? '')->lower()->ascii()->replace(['-', ' '], '_')->toString()) {
            'aguardando_pagamento', 'waiting_payment', 'waitingpayment', 'waiting', 'pending' => 'ct-invoice-badge-warning',
            'pago', 'paid' => 'ct-invoice-badge-success',
            'atrasado', 'overdue', 'late', 'cancelado', 'canceled', 'cancelled' => 'ct-invoice-badge-danger',
            'processando', 'processing' => 'ct-invoice-badge-orange',
            default => 'ct-invoice-badge-neutral',
        };
    }

    public function limparFiltros(): void
    {
        $this->criadoInicio = '';
        $this->criadoFim = '';
        $this->status = '';
        $this->pesquisa = '';
        $this->porPagina = 10;
        $this->pagina = 1;
    }

    public function exportarCsv(): StreamedResponse
    {
        $invoices = $this->invoicesQuery()
            ->limit(10000)
            ->get();

        $fileName = 'boletos-gerados-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($invoices): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Cliente', 'CPF/CNPJ', 'Referencia', 'Vencimento', 'Invoice', 'Boleto', 'Status', 'Valor', 'Data Gerado'], ';');

            foreach ($invoices as $invoice) {
                $lancamento = $invoice->lancamento;
                $cliente = $lancamento?->cliente;

                fputcsv($handle, [
                    $cliente?->nome,
                    $cliente?->cpf_cnpj ?? $invoice->cpf_cnpj,
                    $this->referencia($lancamento?->mes_referencia, $lancamento?->ano_referencia),
                    $this->data($invoice->vencimento),
                    $this->invoiceUrl($invoice),
                    $this->boletoUrl($invoice),
                    $this->statusBoletoLabel($invoice->status),
                    $this->moeda($invoice->total_value),
                    $this->data($invoice->created_at),
                ], ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function invoicesQuery(): Builder
    {
        return Invoice::query()
            ->select('invoices.*')
            ->leftJoin('lancamentos', 'invoices.lancamento_id', '=', 'lancamentos.id')
            ->leftJoin('clientes', 'lancamentos.cliente_id', '=', 'clientes.id')
            ->leftJoin('users', 'invoices.user_id', '=', 'users.id')
            ->with(['lancamento.cliente', 'user'])
            ->when($this->criadoInicio !== '', function (Builder $query): void {
                $query->whereDate('invoices.created_at', '>=', $this->criadoInicio);
            })
            ->when($this->criadoFim !== '', function (Builder $query): void {
                $query->whereDate('invoices.created_at', '<=', $this->criadoFim);
            })
            ->when($this->status !== '', fn (Builder $query): Builder => $query->where('invoices.status', $this->status))
            ->when($this->pesquisa !== '', function (Builder $query): void {
                $search = '%' . $this->pesquisa . '%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('clientes.nome', 'like', $search)
                        ->orWhere('clientes.cpf_cnpj', 'like', $search)
                        ->orWhere('invoices.cpf_cnpj', 'like', $search)
                        ->orWhere('invoices.fatura_id', 'like', $search)
                        ->orWhere('lancamentos.numero_boleto', 'like', $search)
                        ->orWhere('users.name', 'like', $search);
                });
            })
            ->orderByDesc('invoices.created_at')
            ->orderByDesc('invoices.id');
    }
}

