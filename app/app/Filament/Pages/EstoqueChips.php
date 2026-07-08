<?php

namespace App\Filament\Pages;

use App\Models\Chip;
use App\Models\Permission;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Services\Audit\AuditLogger;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class EstoqueChips extends Page
{
    protected static ?string $slug = 'estoque-chips';

    protected static string|UnitEnum|null $navigationGroup = 'Estoque';

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'Chips';

    protected static ?string $title = 'Estoque de Chips';

    protected string $view = 'filament.pages.estoque-chips';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::ESTOQUE_LEITURA) ?? false;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => 'Estoque',
            self::getUrl() => 'Chips',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar')
                ->label('Exportar')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->action('exportarCsv'),
        ];
    }

    public function confirmarExclusaoAction(): Action
    {
        return Action::make('confirmarExclusao')
            ->label('Excluir')
            ->modalSubmitActionLabel('Excluir')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Deseja excluir este chip?')
            ->action(fn (array $arguments): mixed => $this->excluir((int) $arguments['id']));
    }

    public ?int $editingId = null;

    public string $fornecedor = '';

    public string $operadora = '';

    public string $numero_chip = '';

    public string $iccid = '';

    public ?int $tecnico_id = null;

    public ?int $status_rastreador_id = null;

    public string $search = '';

    public ?int $filtroTecnicoId = null;

    public ?int $filtroStatusId = null;

    public int $pagina = 1;

    private const ITENS_POR_PAGINA = 15;

    public function mount(): void
    {
        $this->status_rastreador_id = StatusRastreador::query()
            ->where('label', 'Disponivel')
            ->value('id');
    }

    public function salvar(): void
    {
        if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $isEditing = $this->editingId !== null;
        $fornecedorAtual = $this->fornecedor;
        $operadoraAtual = $this->operadora;

        $data = $this->validate([
            'fornecedor' => ['nullable', 'string', 'max:50'],
            'operadora' => ['nullable', 'string', 'max:50'],
            'numero_chip' => [
                'required',
                'string',
                'max:50',
                Rule::unique('chips', 'numero_chip')->ignore($this->editingId),
            ],
            'iccid' => [
                'required',
                'regex:/^\d{20}$/',
                Rule::unique('chips', 'iccid')->ignore($this->editingId),
            ],
            'tecnico_id' => ['nullable', 'exists:tecnicos,id'],
            'status_rastreador_id' => ['nullable', 'exists:status_rastreadores,id'],
        ], [
            'iccid.regex' => 'O ICCID deve conter exatamente 20 digitos.',
        ], [
            'fornecedor' => 'fornecedor',
            'operadora' => 'operadora',
            'numero_chip' => 'numero chip',
            'iccid' => 'ICCID',
            'tecnico_id' => 'tecnico',
            'status_rastreador_id' => 'status estoque',
        ]);

        if ($this->editingId) {
            $chip = Chip::query()->findOrFail($this->editingId);
            $antes = AuditLogger::snapshot($chip);
            $chip->update($data);
            $chip->refresh();

            AuditLogger::registrar(
                'chip.editado',
                'Chip editado no estoque.',
                $chip,
                antes: $antes,
                depois: AuditLogger::snapshot($chip),
            );
        } else {
            $chip = Chip::query()->create($data);

            AuditLogger::registrar(
                'chip.criado',
                'Chip incluido no estoque.',
                $chip,
                depois: AuditLogger::snapshot($chip),
            );
        }

        Notification::make()
            ->title($this->editingId ? 'Chip atualizado.' : 'Chip incluido.')
            ->success()
            ->send();

        $this->limparFormulario();
        $this->pagina = 1;

        if (! $isEditing) {
            $this->fornecedor = $fornecedorAtual;
            $this->operadora = $operadoraAtual;
        }
    }

    public function editar(int $id): void
    {
        if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $chip = Chip::query()->findOrFail($id);

        $this->editingId = $chip->id;
        $this->fornecedor = (string) $chip->fornecedor;
        $this->operadora = (string) $chip->operadora;
        $this->numero_chip = (string) $chip->numero_chip;
        $this->iccid = (string) $chip->iccid;
        $this->tecnico_id = $chip->tecnico_id;
        $this->status_rastreador_id = $chip->status_rastreador_id;
    }

    public function excluir(int $id): void
    {
        if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        Chip::query()->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->limparFormulario();
        }

        Notification::make()
            ->title('Chip excluido.')
            ->success()
            ->send();

        $this->pagina = min($this->pagina, $this->totalPaginas());
    }

    public function limparFormulario(): void
    {
        $this->reset(['editingId', 'fornecedor', 'operadora', 'numero_chip', 'iccid', 'tecnico_id']);
        $this->status_rastreador_id = StatusRastreador::query()
            ->where('label', 'Disponivel')
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
        $fileName = 'estoque-chips-'.now()->format('Y-m-d-His').'.csv';
        $chips = $this->chipsQuery()->get();

        return response()->streamDownload(function () use ($chips): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Numero Chip', 'ICCID', 'Fornecedor', 'Operadora', 'Status Estoque', 'Tecnico'], ';');

            foreach ($chips as $chip) {
                fputcsv($handle, [
                    $chip->numero_chip,
                    $chip->iccid,
                    $chip->fornecedor,
                    $chip->operadora,
                    $chip->statusRastreador?->label,
                    $chip->tecnico?->nome,
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

    public function chips(): Collection
    {
        return $this->chipsQuery()
            ->offset(($this->pagina - 1) * self::ITENS_POR_PAGINA)
            ->limit(self::ITENS_POR_PAGINA)
            ->get();
    }

    public function totalChips(): int
    {
        return (clone $this->chipsQuery())->toBase()->getCountForPagination();
    }

    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->totalChips() / self::ITENS_POR_PAGINA));
    }

    public function inicioPagina(): int
    {
        if ($this->totalChips() === 0) {
            return 0;
        }

        return (($this->pagina - 1) * self::ITENS_POR_PAGINA) + 1;
    }

    public function fimPagina(): int
    {
        return min($this->totalChips(), $this->pagina * self::ITENS_POR_PAGINA);
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

    private function chipsQuery(): Builder
    {
        return Chip::query()
            ->with(['statusRastreador', 'tecnico'])
            ->when($this->filtroTecnicoId, fn ($query): mixed => $query->where('tecnico_id', $this->filtroTecnicoId))
            ->when($this->filtroStatusId, fn ($query): mixed => $query->where('status_rastreador_id', $this->filtroStatusId))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('numero_chip', 'like', $search)
                        ->orWhere('iccid', 'like', $search)
                        ->orWhere('fornecedor', 'like', $search)
                        ->orWhere('operadora', 'like', $search)
                        ->orWhereHas('statusRastreador', fn ($query): mixed => $query->where('label', 'like', $search))
                        ->orWhereHas('tecnico', fn ($query): mixed => $query->where('nome', 'like', $search));
                });
            })
            ->latest('updated_at')
            ->latest('id')
            ->orderBy('numero_chip');
    }
}
