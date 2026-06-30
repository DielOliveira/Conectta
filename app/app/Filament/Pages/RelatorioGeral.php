<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Lancamento;
use App\Models\Permission;
use App\Models\StatusCliente;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class RelatorioGeral extends Page
{
    protected static ?string $slug = 'relatorio-geral';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Relatorio geral';

    protected static ?string $title = 'Relatorio geral';

    protected string $view = 'filament.pages.relatorio-geral';

    protected Width|string|null $maxWidth = Width::Full;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::FINANCEIRO_LEITURA) ?? false;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => 'Financeiro',
            self::getUrl() => 'Relatorio geral',
        ];
    }

    public string $dataInicio = '';

    public string $dataFim = '';

    public string $numeroBoleto = '';

    public string $statusCliente = '0';

    public string $statusBoleto = '';

    public int $porPagina = 10;

    public int $pagina = 1;

    public function mount(): void
    {
        $this->dataInicio = now()->toDateString();
        $this->dataFim = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['dataInicio', 'dataFim', 'numeroBoleto', 'statusCliente', 'statusBoleto', 'porPagina'], true)) {
            $this->pagina = 1;
        }
    }

    public function limparFiltros(): void
    {
        $this->dataInicio = now()->toDateString();
        $this->dataFim = now()->toDateString();
        $this->numeroBoleto = '';
        $this->statusCliente = '0';
        $this->statusBoleto = '';
        $this->porPagina = 10;
        $this->pagina = 1;
    }

    public function exportarCsv(): StreamedResponse
    {
        $lancamentos = $this->lancamentosQuery()
            ->limit(10000)
            ->get();

        $fileName = 'lancamentos-abertos-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($lancamentos): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Cliente', 'Vencimento', 'Mes / Ano', 'Valor', 'N. Boleto', 'Status Boleto'], ';');

            foreach ($lancamentos as $lancamento) {
                fputcsv($handle, [
                    $lancamento->cliente?->nome,
                    $lancamento->cliente?->dia_pagamento,
                    $lancamento->mes_referencia . ' / ' . $lancamento->ano_referencia,
                    $this->moeda($lancamento->valor_planejado),
                    $lancamento->numero_boleto,
                    $this->statusBoletoLabel($lancamento->invoice?->status),
                ], ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function lancamentos(): EloquentCollection
    {
        return $this->lancamentosQuery()
            ->offset(($this->pagina - 1) * $this->porPagina)
            ->limit($this->porPagina)
            ->get();
    }

    public function totalLancamentos(): int
    {
        return (clone $this->lancamentosQuery())->count();
    }

    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->totalLancamentos() / $this->porPagina));
    }

    public function paginaAnterior(): void
    {
        $this->pagina = max(1, $this->pagina - 1);
    }

    public function proximaPagina(): void
    {
        $this->pagina = min($this->totalPaginas(), $this->pagina + 1);
    }

    public function irParaPagina(int $pagina): void
    {
        $this->pagina = min(max(1, $pagina), $this->totalPaginas());
    }

    public function statusClientes(): EloquentCollection
    {
        return StatusCliente::query()
            ->orderBy('order')
            ->orderBy('label')
            ->get();
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

    public function statusBoletoClasse(?string $status): string
    {
        return match (str($status ?? '')->lower()->ascii()->replace(['-', ' '], '_')->toString()) {
            'aguardando_pagamento', 'waiting_payment', 'waitingpayment', 'waiting', 'pending' => 'ct-report-badge-warning',
            'pago', 'paid' => 'ct-report-badge-success',
            'atrasado', 'overdue', 'late', 'cancelado', 'canceled', 'cancelled' => 'ct-report-badge-danger',
            'processando', 'processing' => 'ct-report-badge-orange',
            default => 'ct-report-badge-neutral',
        };
    }

    private function lancamentosQuery(): Builder
    {
        [$inicio, $fim] = $this->periodoReferencia();

        return Lancamento::query()
            ->select('lancamentos.*')
            ->leftJoin('clientes', 'lancamentos.cliente_id', '=', 'clientes.id')
            ->leftJoin('invoices', 'lancamentos.id', '=', 'invoices.lancamento_id')
            ->with(['cliente.statusCliente', 'invoice'])
            ->where('lancamentos.valor_planejado', '>', 0)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('lancamentos.valor_efetivado')
                    ->orWhere('lancamentos.valor_efetivado', 0);
            })
            ->whereRaw('(lancamentos.ano_referencia * 100 + lancamentos.mes_referencia) >= ?', [$inicio])
            ->whereRaw('(lancamentos.ano_referencia * 100 + lancamentos.mes_referencia) <= ?', [$fim])
            ->when($this->statusCliente !== '0', function (Builder $query): void {
                $label = $this->statusCliente === '1' ? 'Ativo' : 'Inativo';

                $query->whereHas('cliente.statusCliente', fn (Builder $query): Builder => $query->where('label', $label));
            })
            ->when($this->numeroBoleto !== '', fn (Builder $query): Builder => $query->where('lancamentos.numero_boleto', $this->numeroBoleto))
            ->when($this->statusBoleto !== '', fn (Builder $query): Builder => $query->where('invoices.status', $this->statusBoleto))
            ->orderBy('clientes.dia_pagamento');
    }

    private function periodoReferencia(): array
    {
        $inicio = filled($this->dataInicio) ? strtotime($this->dataInicio) : now()->timestamp;
        $fim = filled($this->dataFim) ? strtotime($this->dataFim) : now()->timestamp;

        return [
            ((int) date('Y', $inicio) * 100) + (int) date('n', $inicio),
            ((int) date('Y', $fim) * 100) + (int) date('n', $fim),
        ];
    }
}
