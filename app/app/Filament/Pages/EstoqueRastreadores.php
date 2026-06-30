<?php

namespace App\Filament\Pages;

use App\Models\Permission;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;
use UnitEnum;

class EstoqueRastreadores extends Page
{
    protected static ?string $slug = 'estoque-rastreadores';

    protected static string|UnitEnum|null $navigationGroup = 'Estoque';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'Rastreadores';

    protected static ?string $title = 'Estoque de Rastreadores';

    protected string $view = 'filament.pages.estoque-rastreadores';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::ESTOQUE_LEITURA) ?? false;
    }

    public ?int $editingId = null;

    public string $modelo = '';

    public ?int $ativacao = null;

    public string $imei = '';

    public ?int $status_rastreador_id = null;

    public ?int $tecnico_id = null;

    public string $search = '';

    public ?int $filtroTecnicoId = null;

    public ?int $filtroStatusId = null;

    public int $pagina = 1;

    private const ITENS_POR_PAGINA = 15;

    public function mount(): void
    {
        $this->ativacao = (int) now()->year;
        $this->status_rastreador_id = StatusRastreador::query()
            ->where('label', 'Ativo')
            ->value('id');
    }

    public function salvar(): void
    {
        if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $isEditing = $this->editingId !== null;
        $modeloAtual = $this->modelo;

        $data = $this->validate([
            'modelo' => ['required', 'string', 'max:50'],
            'ativacao' => ['nullable', 'integer'],
            'imei' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rastreadores', 'imei')->ignore($this->editingId),
            ],
            'status_rastreador_id' => ['nullable', 'exists:status_rastreadores,id'],
            'tecnico_id' => ['nullable', 'exists:tecnicos,id'],
        ], [], [
            'modelo' => 'modelo',
            'ativacao' => 'ativacao',
            'imei' => 'IMEI',
            'status_rastreador_id' => 'status estoque',
            'tecnico_id' => 'tecnico',
        ]);

        Rastreador::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                ...$data,
                'is_estoque' => true,
                'criado_em' => now(),
            ],
        );

        Notification::make()
            ->title($this->editingId ? 'Rastreador atualizado.' : 'Rastreador incluido.')
            ->success()
            ->send();

        $this->limparFormulario();
        $this->pagina = 1;

        if (! $isEditing) {
            $this->modelo = $modeloAtual;
        }
    }

    public function editar(int $id): void
    {
        if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $rastreador = Rastreador::query()->findOrFail($id);

        $this->editingId = $rastreador->id;
        $this->modelo = (string) $rastreador->modelo;
        $this->ativacao = $rastreador->ativacao;
        $this->imei = (string) $rastreador->imei;
        $this->status_rastreador_id = $rastreador->status_rastreador_id;
        $this->tecnico_id = $rastreador->tecnico_id;
    }

    public function excluir(int $id): void
    {
        if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        Rastreador::query()->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->limparFormulario();
        }

        Notification::make()
            ->title('Rastreador excluido.')
            ->success()
            ->send();

        $this->pagina = min($this->pagina, $this->totalPaginas());
    }

    public function limparFormulario(): void
    {
        $this->reset(['editingId', 'modelo', 'ativacao', 'imei', 'tecnico_id']);
        $this->ativacao = (int) now()->year;
        $this->status_rastreador_id = StatusRastreador::query()
            ->where('label', 'Ativo')
            ->value('id');
    }

    public function limparFiltros(): void
    {
        $this->reset(['search', 'filtroTecnicoId', 'filtroStatusId']);
        $this->pagina = 1;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filtroTecnicoId', 'filtroStatusId'], true)) {
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

    public function exportarCsv(): StreamedResponse
    {
        $fileName = 'estoque-rastreadores-' . now()->format('Y-m-d-His') . '.csv';
        $rastreadores = $this->rastreadoresQuery()->get();

        return response()->streamDownload(function () use ($rastreadores): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Modelo', 'IMEI', 'Ativacao', 'Status Estoque', 'Tecnico'], ';');

            foreach ($rastreadores as $rastreador) {
                fputcsv($handle, [
                    $rastreador->modelo,
                    $rastreador->imei,
                    $rastreador->ativacao,
                    $rastreador->statusRastreador?->label,
                    $rastreador->tecnico?->nome,
                ], ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function tecnicos(): Collection
    {
        return Tecnico::query()
            ->orderBy('nome')
            ->get();
    }

    public function statusOptions(): Collection
    {
        return StatusRastreador::query()
            ->orderBy('order')
            ->orderBy('label')
            ->get();
    }

    public function rastreadores(): Collection
    {
        return $this->rastreadoresQuery()
            ->offset(($this->pagina - 1) * self::ITENS_POR_PAGINA)
            ->limit(self::ITENS_POR_PAGINA)
            ->get();
    }

    public function totalRastreadores(): int
    {
        return (clone $this->rastreadoresQuery())->toBase()->getCountForPagination();
    }

    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->totalRastreadores() / self::ITENS_POR_PAGINA));
    }

    public function inicioPagina(): int
    {
        if ($this->totalRastreadores() === 0) {
            return 0;
        }

        return (($this->pagina - 1) * self::ITENS_POR_PAGINA) + 1;
    }

    public function fimPagina(): int
    {
        return min($this->totalRastreadores(), $this->pagina * self::ITENS_POR_PAGINA);
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

    private function rastreadoresQuery(): Builder
    {
        return Rastreador::query()
            ->with(['statusRastreador', 'tecnico'])
            ->when($this->filtroTecnicoId, fn ($query): mixed => $query->where('tecnico_id', $this->filtroTecnicoId))
            ->when($this->filtroStatusId, fn ($query): mixed => $query->where('status_rastreador_id', $this->filtroStatusId))
            ->when($this->search !== '', function ($query): void {
                $search = '%' . $this->search . '%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('modelo', 'like', $search)
                        ->orWhere('imei', 'like', $search)
                        ->orWhere('ativacao', 'like', $search)
                        ->orWhereHas('tecnico', fn ($query): mixed => $query->where('nome', 'like', $search));
                });
            })
            ->latest('updated_at')
            ->latest('id')
            ->orderBy('imei');
    }
}
