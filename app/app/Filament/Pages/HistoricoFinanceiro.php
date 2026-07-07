<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Cliente;
use App\Models\Permission;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use UnitEnum;

class HistoricoFinanceiro extends Page
{
    protected static ?string $slug = 'historico-financeiro';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 5;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Historico Financeiro';

    protected static ?string $title = 'Historico Financeiro';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.historico-financeiro';

    protected Width|string|null $maxWidth = Width::Full;

    public string $data = '';

    public int $pagina = 1;

    public int $porPagina = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::FINANCEIRO_LEITURA) ?? false;
    }

    public function mount(): void
    {
        $this->data = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['data', 'porPagina'], true)) {
            $this->pagina = 1;
        }
    }

    public function limparFiltros(): void
    {
        $this->data = now()->toDateString();
        $this->pagina = 1;
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

    public function registros(): Collection
    {
        $logs = $this->logsQuery()
            ->with('user')
            ->offset(($this->pagina - 1) * $this->porPagina)
            ->limit($this->porPagina)
            ->get();

        return $this->mapearLogs($logs);
    }

    public function totalRegistros(): int
    {
        return (clone $this->logsQuery())->toBase()->getCountForPagination();
    }

    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->totalRegistros() / $this->porPagina));
    }

    public function inicioPagina(): int
    {
        if ($this->totalRegistros() === 0) {
            return 0;
        }

        return (($this->pagina - 1) * $this->porPagina) + 1;
    }

    public function fimPagina(): int
    {
        return min($this->totalRegistros(), $this->pagina * $this->porPagina);
    }

    public function paginasVisiveis(): array
    {
        $total = $this->totalPaginas();

        if ($total <= 7) {
            return range(1, $total);
        }

        $paginas = collect([1, $this->pagina - 1, $this->pagina, $this->pagina + 1, $total])
            ->filter(fn (int $page): bool => $page >= 1 && $page <= $total)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $visiveis = [];

        foreach ($paginas as $index => $page) {
            $previous = $paginas[$index - 1] ?? null;

            if ($previous !== null && $page > $previous + 1) {
                $visiveis[] = '...';
            }

            $visiveis[] = $page;
        }

        return $visiveis;
    }

    public function moeda(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return number_format((float) $valor, 2, ',', '.');
    }

    public function dataHora(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        try {
            return \Carbon\CarbonImmutable::parse($valor)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    public function dataSomente(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        try {
            return \Carbon\CarbonImmutable::parse($valor)->format('Y-m-d');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    private function logsQuery(): Builder
    {
        return AuditLog::query()
            ->whereIn('acao', [
                'financeiro.lancamento_criado',
                'financeiro.lancamento_editado',
                'financeiro.parcelamento_criado',
                'financeiro.parcelamento_excluido',
            ])
            ->when($this->data !== '', fn (Builder $query): Builder => $query->whereDate('created_at', $this->data))
            ->latest('created_at')
            ->latest('id');
    }

    private function mapearLogs(EloquentCollection $logs): Collection
    {
        $clienteIds = $logs
            ->flatMap(fn (AuditLog $log): array => [
                data_get($log->depois, 'cliente_id'),
                data_get($log->antes, 'cliente_id'),
                data_get($log->contexto, 'cliente_id'),
            ])
            ->filter()
            ->unique()
            ->values();

        $clientes = Cliente::query()
            ->whereIn('id', $clienteIds)
            ->pluck('nome', 'id');

        return $logs->map(function (AuditLog $log) use ($clientes): array {
            $antes = $log->antes ?? [];
            $depois = $log->depois ?? [];
            $contexto = $log->contexto ?? [];
            $clienteId = data_get($depois, 'cliente_id') ?? data_get($antes, 'cliente_id') ?? data_get($contexto, 'cliente_id');
            $mes = data_get($depois, 'mes_referencia') ?? data_get($antes, 'mes_referencia') ?? data_get($contexto, 'mes_referencia');
            $ano = data_get($depois, 'ano_referencia') ?? data_get($antes, 'ano_referencia') ?? data_get($contexto, 'ano_referencia');

            return [
                'cliente' => $clienteId ? (string) ($clientes[$clienteId] ?? 'Cliente #'.$clienteId) : '',
                'referencia' => $mes && $ano ? ((int) $mes).'/'.((int) $ano) : '',
                'valor_anterior' => data_get($antes, 'valor_efetivado') ?? data_get($antes, 'valor_planejado'),
                'valor_modificado' => data_get($depois, 'valor_efetivado') ?? data_get($depois, 'valor_planejado'),
                'data_anterior' => data_get($antes, 'data_lancamento'),
                'data_modificada' => data_get($depois, 'data_lancamento'),
                'total_antes' => data_get($contexto, 'total_antes'),
                'total_depois' => data_get($contexto, 'total_depois'),
                'data_transacao' => $log->created_at,
                'operador' => $log->user?->name ?? 'Sistema',
            ];
        });
    }
}
