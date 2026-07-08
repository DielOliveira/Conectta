<?php

namespace App\Filament\Pages;

use App\Models\Chip;
use App\Models\Permission;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Services\Audit\AuditLogger;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    /**
     * @var array<string, mixed>
     */
    public array $formData = [];

    public string $search = '';

    public ?int $filtroTecnicoId = null;

    public ?int $filtroStatusId = null;

    public int $pagina = 1;

    private const ITENS_POR_PAGINA = 15;

    public function mount(): void
    {
        $this->form->fill($this->formDataPadrao());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('formData')
            ->components([
                Grid::make(12)->schema([
                    TextInput::make('fornecedor')
                        ->label('Fornecedor')
                        ->maxLength(50)
                        ->columnSpan(3),
                    TextInput::make('operadora')
                        ->label('Operadora')
                        ->maxLength(50)
                        ->columnSpan(3),
                    TextInput::make('numero_chip')
                        ->label('Numero Chip')
                        ->required()
                        ->maxLength(50)
                        ->rules(fn (): array => [
                            Rule::unique('chips', 'numero_chip')->ignore($this->editingId),
                        ])
                        ->columnSpan(4),
                    TextInput::make('iccid')
                        ->label('ICCID')
                        ->required()
                        ->regex('/^\d{20}$/')
                        ->validationMessages([
                            'regex' => 'O ICCID deve conter exatamente 20 digitos.',
                        ])
                        ->rules(fn (): array => [
                            Rule::unique('chips', 'iccid')->ignore($this->editingId),
                        ])
                        ->maxLength(20)
                        ->extraInputAttributes([
                            'inputmode' => 'numeric',
                            'pattern' => '[0-9]{20}',
                        ])
                        ->columnSpan(2),
                    Select::make('rastreador_id')
                        ->label('IMEI')
                        ->searchable()
                        ->native(false)
                        ->searchDebounce(500)
                        ->optionsLimit(20)
                        ->getSearchResultsUsing(fn (string $search): array => $this->rastreadorSearchResults($search))
                        ->getOptionLabelUsing(fn (mixed $value): ?string => $this->rastreadorOptionLabel($value))
                        ->placeholder('Pesquisar IMEI')
                        ->searchPrompt('Digite o IMEI para pesquisar')
                        ->noSearchResultsMessage('Nenhum IMEI disponivel.')
                        ->columnSpan(4),
                    Select::make('status_rastreador_id')
                        ->label('Status Estoque')
                        ->options(fn (): array => StatusRastreador::query()
                            ->orderBy('order')
                            ->orderBy('label')
                            ->pluck('label', 'id')
                            ->all())
                        ->native(false)
                        ->columnSpan(3),
                    Select::make('tecnico_id')
                        ->label('Tecnico')
                        ->options(fn (): array => Tecnico::query()
                            ->orderBy('nome')
                            ->pluck('nome', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->columnSpan(3),
                ]),
            ]);
    }

    public function salvar(): void
    {
        if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $isEditing = $this->editingId !== null;
        $data = $this->form->getState();
        $fornecedorAtual = (string) ($data['fornecedor'] ?? '');
        $operadoraAtual = (string) ($data['operadora'] ?? '');

        $rastreadorId = $data['rastreador_id'] ?? null;
        unset($data['rastreador_id']);

        $this->validarRastreadorDisponivelParaChip($rastreadorId);

        if ($this->editingId) {
            $chip = Chip::query()->findOrFail($this->editingId);
            $antes = AuditLogger::snapshot($chip);
            $chip->update($data);
            $this->sincronizarRastreador($chip->id, $rastreadorId);
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
            $this->sincronizarRastreador($chip->id, $rastreadorId);
            $chip->refresh();

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
            $this->form->fill([
                ...$this->formDataPadrao(),
                'fornecedor' => $fornecedorAtual,
                'operadora' => $operadoraAtual,
            ]);
        }
    }

    public function editar(int $id): void
    {
        if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $chip = Chip::query()
            ->with('rastreador')
            ->findOrFail($id);

        $this->editingId = $chip->id;
        $this->form->fill([
            'fornecedor' => (string) $chip->fornecedor,
            'operadora' => (string) $chip->operadora,
            'numero_chip' => (string) $chip->numero_chip,
            'iccid' => (string) $chip->iccid,
            'tecnico_id' => $chip->tecnico_id,
            'rastreador_id' => $chip->rastreador?->id,
            'status_rastreador_id' => $chip->status_rastreador_id,
        ]);
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
        $this->editingId = null;
        $this->form->fill($this->formDataPadrao());
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
            fputcsv($handle, ['Numero Chip', 'ICCID', 'IMEI', 'Fornecedor', 'Operadora', 'Status Estoque', 'Tecnico'], ';');

            foreach ($chips as $chip) {
                fputcsv($handle, [
                    $chip->numero_chip,
                    $chip->iccid,
                    $chip->rastreador?->imei,
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
            ->with(['statusRastreador', 'tecnico', 'rastreador'])
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
                        ->orWhereHas('rastreador', fn ($query): mixed => $query->where('imei', 'like', $search))
                        ->orWhereHas('statusRastreador', fn ($query): mixed => $query->where('label', 'like', $search))
                        ->orWhereHas('tecnico', fn ($query): mixed => $query->where('nome', 'like', $search));
                });
            })
            ->latest('updated_at')
            ->latest('id')
            ->orderBy('numero_chip');
    }

    private function validarRastreadorDisponivelParaChip(?int $rastreadorId): void
    {
        if ($rastreadorId === null) {
            return;
        }

        $vinculado = Rastreador::query()
            ->whereKey($rastreadorId)
            ->whereNotNull('chip_id')
            ->when($this->editingId !== null, fn (Builder $query): Builder => $query->where('chip_id', '!=', $this->editingId))
            ->exists();

        if ($vinculado) {
            throw ValidationException::withMessages([
                'formData.rastreador_id' => 'Este IMEI ja possui chip vinculado.',
            ]);
        }
    }

    private function sincronizarRastreador(int $chipId, ?int $rastreadorId): void
    {
        Rastreador::query()
            ->where('chip_id', $chipId)
            ->update(['chip_id' => null]);

        if ($rastreadorId !== null) {
            Rastreador::query()
                ->whereKey($rastreadorId)
                ->update(['chip_id' => $chipId]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formDataPadrao(): array
    {
        return [
            'fornecedor' => '',
            'operadora' => '',
            'numero_chip' => '',
            'iccid' => '',
            'tecnico_id' => null,
            'rastreador_id' => null,
            'status_rastreador_id' => StatusRastreador::query()
                ->where('label', 'Disponivel')
                ->value('id'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function rastreadorSearchResults(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        return Rastreador::query()
            ->where(function (Builder $query): void {
                $query->whereNull('chip_id');

                if ($this->editingId !== null) {
                    $query->orWhere('chip_id', $this->editingId);
                }
            })
            ->where('imei', 'like', '%'.$search.'%')
            ->orderBy('imei')
            ->limit(20)
            ->pluck('imei', 'id')
            ->all();
    }

    private function rastreadorOptionLabel(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Rastreador::query()
            ->whereKey((int) $value)
            ->value('imei');
    }
}
