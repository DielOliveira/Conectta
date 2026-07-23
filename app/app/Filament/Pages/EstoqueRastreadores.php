<?php

namespace App\Filament\Pages;

use App\Models\Chip;
use App\Models\Permission;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Services\Audit\AuditLogger;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public function getBreadcrumbs(): array
    {
        return [
            '#' => 'Estoque',
            self::getUrl() => 'Rastreadores',
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
            ->modalDescription('Deseja excluir este rastreador?')
            ->action(fn (array $arguments): mixed => $this->excluir((int) $arguments['id']));
    }

    public function adicionarChipAction(): Action
    {
        return Action::make('adicionarChip')
            ->label('Adicionar chip')
            ->icon(Heroicon::Plus)
            ->modalHeading('Adicionar chip ao rastreador')
            ->modalSubmitActionLabel('Adicionar chip')
            ->fillForm(function (array $arguments): array {
                $rastreador = Rastreador::query()->findOrFail((int) $arguments['id']);

                return [
                    'fornecedor' => '',
                    'operadora' => '',
                    'numero_chip' => '',
                    'iccid' => '',
                    'imei' => $rastreador->imei,
                ];
            })
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('fornecedor')
                        ->label('Fornecedor')
                        ->maxLength(50),
                    TextInput::make('operadora')
                        ->label('Operadora')
                        ->maxLength(50),
                    TextInput::make('numero_chip')
                        ->label('Numero Chip')
                        ->required()
                        ->maxLength(50)
                        ->rules(fn (): array => [Rule::unique('chips', 'numero_chip')]),
                    TextInput::make('iccid')
                        ->label('ICCID')
                        ->required()
                        ->regex('/^\d{20}$/')
                        ->validationMessages([
                            'regex' => 'O ICCID deve conter exatamente 20 digitos.',
                        ])
                        ->rules(fn (): array => [Rule::unique('chips', 'iccid')])
                        ->maxLength(20)
                        ->extraInputAttributes([
                            'inputmode' => 'numeric',
                            'pattern' => '[0-9]{20}',
                        ]),
                    TextInput::make('imei')
                        ->label('IMEI')
                        ->disabled()
                        ->dehydrated(false),
                ]),
            ])
            ->action(function (array $data, array $arguments): void {
                if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
                    Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

                    return;
                }

                $chip = DB::transaction(function () use ($data, $arguments): ?Chip {
                    $rastreador = Rastreador::query()
                        ->lockForUpdate()
                        ->findOrFail((int) $arguments['id']);

                    if ($rastreador->chip_id !== null) {
                        return null;
                    }

                    $data['tecnico_id'] = $rastreador->tecnico_id;
                    $data['status_rastreador_id'] = $rastreador->status_rastreador_id;
                    $chip = Chip::query()->create($data);
                    $rastreador->update(['chip_id' => $chip->id]);

                    AuditLogger::registrar(
                        'chip.criado',
                        'Chip incluido no estoque e vinculado ao rastreador.',
                        $chip,
                        depois: AuditLogger::snapshot($chip),
                        contexto: [
                            'rastreador_id' => $rastreador->id,
                            'imei' => $rastreador->imei,
                        ],
                    );

                    return $chip;
                });

                if ($chip === null) {
                    Notification::make()
                        ->title('Este rastreador ja possui um chip vinculado.')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Chip adicionado e vinculado ao rastreador.')
                    ->success()
                    ->send();
            });
    }

    public function removerChipAction(): Action
    {
        return Action::make('removerChip')
            ->label('Remover chip')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Remover chip do rastreador')
            ->modalDescription('Deseja desvincular este chip do rastreador? O chip continuara cadastrado no estoque.')
            ->modalSubmitActionLabel('Remover chip')
            ->action(function (array $arguments): void {
                if (! auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA)) {
                    Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

                    return;
                }

                $removido = DB::transaction(function () use ($arguments): bool {
                    $rastreador = Rastreador::query()
                        ->lockForUpdate()
                        ->findOrFail((int) $arguments['id']);

                    if ($rastreador->chip_id === null) {
                        return false;
                    }

                    $antes = AuditLogger::snapshot($rastreador);
                    $rastreador->update(['chip_id' => null]);

                    AuditLogger::registrar(
                        'rastreador.editado',
                        'Chip desvinculado do rastreador no estoque.',
                        $rastreador,
                        antes: $antes,
                        depois: AuditLogger::snapshot($rastreador->refresh()),
                    );

                    return true;
                });

                Notification::make()
                    ->title($removido ? 'Chip removido do rastreador.' : 'Este rastreador nao possui chip vinculado.')
                    ->color($removido ? 'success' : 'warning')
                    ->send();
            });
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

        $data = [
            ...$data,
            'is_estoque' => true,
            'criado_em' => now(),
        ];

        if ($this->editingId) {
            $rastreador = Rastreador::query()->findOrFail($this->editingId);
            $antes = AuditLogger::snapshot($rastreador);
            $rastreador->update($data);
            $tecnicoAlterado = $rastreador->wasChanged('tecnico_id');
            $statusAlterado = $rastreador->wasChanged('status_rastreador_id');
            $rastreador->refresh();

            if (($tecnicoAlterado || $statusAlterado) && $rastreador->chip_id !== null) {
                $chip = Chip::query()->find($rastreador->chip_id);

                if ($chip !== null && ($chip->tecnico_id !== $rastreador->tecnico_id || $chip->status_rastreador_id !== $rastreador->status_rastreador_id)) {
                    $chipAntes = AuditLogger::snapshot($chip);
                    $chip->update([
                        'tecnico_id' => $rastreador->tecnico_id,
                        'status_rastreador_id' => $rastreador->status_rastreador_id,
                    ]);

                    AuditLogger::registrar(
                        'chip.editado',
                        'Tecnico e status do chip sincronizados com o rastreador vinculado.',
                        $chip,
                        antes: $chipAntes,
                        depois: AuditLogger::snapshot($chip->refresh()),
                        contexto: [
                            'rastreador_id' => $rastreador->id,
                            'imei' => $rastreador->imei,
                        ],
                    );
                }
            }

            AuditLogger::registrar(
                'rastreador.editado',
                'Rastreador editado no estoque.',
                $rastreador,
                antes: $antes,
                depois: AuditLogger::snapshot($rastreador),
            );
        } else {
            $rastreador = Rastreador::query()->create($data);

            AuditLogger::registrar(
                'rastreador.criado',
                'Rastreador incluido no estoque.',
                $rastreador,
                depois: AuditLogger::snapshot($rastreador),
            );
        }

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
        $fileName = 'estoque-rastreadores-'.now()->format('Y-m-d-His').'.csv';
        $rastreadores = $this->rastreadoresQuery()->get();

        return response()->streamDownload(function () use ($rastreadores): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Modelo', 'Numero Chip', 'ICCID', 'IMEI', 'Ativacao', 'Status Estoque', 'Tecnico'], ';');

            foreach ($rastreadores as $rastreador) {
                fputcsv($handle, [
                    $rastreador->modelo,
                    $rastreador->chip?->numero_chip,
                    $rastreador->chip?->iccid,
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
            ->with(['chip', 'statusRastreador', 'tecnico'])
            ->when($this->filtroTecnicoId, fn ($query): mixed => $query->where('tecnico_id', $this->filtroTecnicoId))
            ->when($this->filtroStatusId, fn ($query): mixed => $query->where('status_rastreador_id', $this->filtroStatusId))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('modelo', 'like', $search)
                        ->orWhere('imei', 'like', $search)
                        ->orWhere('ativacao', 'like', $search)
                        ->orWhereHas('chip', function (Builder $query) use ($search): void {
                            $query
                                ->where('numero_chip', 'like', $search)
                                ->orWhere('iccid', 'like', $search);
                        })
                        ->orWhereHas('tecnico', fn ($query): mixed => $query->where('nome', 'like', $search));
                });
            })
            ->latest('updated_at')
            ->latest('id')
            ->orderBy('imei');
    }
}
