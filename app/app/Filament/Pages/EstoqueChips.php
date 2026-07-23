<?php

namespace App\Filament\Pages;

use App\Models\Chip;
use App\Models\Fornecedor;
use App\Models\Operadora;
use App\Models\Permission;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\Veiculo;
use App\Services\Audit\AuditLogger;
use App\Support\ChipNumber;
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
use Illuminate\Support\Facades\DB;
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

    public function confirmarSincronizacaoTecnicoAction(): Action
    {
        return Action::make('confirmarSincronizacaoTecnico')
            ->requiresConfirmation()
            ->modalHeading('Alterar tecnico do chip ativo')
            ->modalDescription(fn (): string => $this->sincronizacaoTecnicoDescricao
                ?? 'A alteracao tambem sera aplicada ao rastreador e ao tecnico de instalacao do veiculo ativo. Deseja continuar?')
            ->modalSubmitActionLabel('Sim, alterar tecnico')
            ->action(function (): void {
                $this->sincronizacaoTecnicoConfirmada = true;

                try {
                    $this->salvar();
                } finally {
                    $this->sincronizacaoTecnicoConfirmada = false;
                }
            });
    }

    public ?int $editingId = null;

    public bool $sincronizacaoTecnicoConfirmada = false;

    public ?string $sincronizacaoTecnicoDescricao = null;

    /**
     * @var array<string, mixed>
     */
    public array $formData = [];

    public string $search = '';

    public ?int $filtroTecnicoId = null;

    public ?int $filtroStatusId = null;

    public string $ordenacao = 'numero_chip';

    public string $direcaoOrdenacao = 'asc';

    public int $pagina = 1;

    private const ITENS_POR_PAGINA = 15;

    private const CAMPOS_ORDENAVEIS = [
        'numero_chip',
        'iccid',
        'imei',
        'fornecedor',
        'operadora',
        'status',
        'tecnico',
    ];

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
                    Select::make('fornecedor_id')
                        ->label('Fornecedor')
                        ->options(fn (): array => Fornecedor::query()
                            ->orderBy('id')
                            ->pluck('nome', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->columnSpan(3),
                    Select::make('operadora_id')
                        ->label('Operadora')
                        ->options(fn (): array => Operadora::query()
                            ->orderBy('id')
                            ->pluck('nome', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->columnSpan(3),
                    TextInput::make('numero_chip')
                        ->label('Numero Chip')
                        ->required()
                        ->prefix('55')
                        ->mask('(99) 99999-9999')
                        ->stripCharacters(['(', ')', ' ', '-'])
                        ->maxLength(15)
                        ->regex(ChipNumber::LOCAL_REGEX)
                        ->formatStateUsing(fn (?string $state): string => ChipNumber::local($state))
                        ->dehydrateStateUsing(fn (?string $state): string => ChipNumber::canonical($state))
                        ->validationMessages([
                            'regex' => 'Informe um número de celular completo, com DDD válido.',
                        ])
                        ->rules(fn (): array => [
                            ChipNumber::uniqueRule($this->editingId),
                        ])
                        ->extraInputAttributes([
                            'inputmode' => 'numeric',
                            'autocomplete' => 'off',
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
        $fornecedorAtual = $data['fornecedor_id'] ?? null;
        $operadoraAtual = $data['operadora_id'] ?? null;

        if ($this->deveConfirmarSincronizacaoTecnico($data)) {
            $this->sincronizacaoTecnicoDescricao = $this->descricaoSincronizacaoTecnico($data);
            $this->mountAction('confirmarSincronizacaoTecnico');

            return;
        }

        if ($fornecedorAtual) {
            $data['fornecedor'] = Fornecedor::query()->findOrFail($fornecedorAtual)->nome;
        } else {
            $data['fornecedor'] = null;
        }

        if ($operadoraAtual) {
            $data['operadora'] = Operadora::query()->findOrFail($operadoraAtual)->nome;
        }

        if ($this->editingId) {
            DB::transaction(function () use ($data): void {
                $chip = Chip::query()->lockForUpdate()->findOrFail($this->editingId);
                $antes = AuditLogger::snapshot($chip);
                $sincronizarTecnico = $this->chipPossuiVeiculoAtivo($chip)
                    && $chip->tecnico_id !== ($data['tecnico_id'] ?? null);

                $chip->update($data);
                $chip->refresh();

                if ($sincronizarTecnico) {
                    $this->sincronizarTecnicoDoChipAtivo($chip);
                }

                AuditLogger::registrar(
                    'chip.editado',
                    'Chip editado no estoque.',
                    $chip,
                    antes: $antes,
                    depois: AuditLogger::snapshot($chip),
                );
            });
        } else {
            $chip = Chip::query()->create($data);
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
                'fornecedor_id' => $fornecedorAtual,
                'operadora_id' => $operadoraAtual,
            ]);
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
        $this->form->fill([
            'fornecedor_id' => $chip->fornecedor_id,
            'operadora_id' => $chip->operadora_id,
            'numero_chip' => (string) $chip->numero_chip,
            'iccid' => (string) $chip->iccid,
            'tecnico_id' => $chip->tecnico_id,
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

    public function ordenarPor(string $campo): void
    {
        if (! in_array($campo, self::CAMPOS_ORDENAVEIS, true)) {
            return;
        }

        if ($this->ordenacao === $campo) {
            $this->direcaoOrdenacao = $this->direcaoOrdenacao === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenacao = $campo;
            $this->direcaoOrdenacao = 'asc';
        }

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
                    $chip->fornecedorCadastro?->nome,
                    $chip->operadoraCadastro?->nome,
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
        $query = Chip::query()
            ->with(['fornecedorCadastro', 'operadoraCadastro', 'statusRastreador', 'tecnico', 'rastreador'])
            ->when($this->filtroTecnicoId, fn ($query): mixed => $query->where('tecnico_id', $this->filtroTecnicoId))
            ->when($this->filtroStatusId, fn ($query): mixed => $query->where('status_rastreador_id', $this->filtroStatusId))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('numero_chip', 'like', $search)
                        ->orWhere('iccid', 'like', $search)
                        ->orWhereHas('fornecedorCadastro', fn ($query): mixed => $query->where('nome', 'like', $search))
                        ->orWhereHas('operadoraCadastro', fn ($query): mixed => $query->where('nome', 'like', $search))
                        ->orWhereHas('rastreador', fn ($query): mixed => $query->where('imei', 'like', $search))
                        ->orWhereHas('statusRastreador', fn ($query): mixed => $query->where('label', 'like', $search))
                        ->orWhereHas('tecnico', fn ($query): mixed => $query->where('nome', 'like', $search));
                });
            });

        $direcao = $this->direcaoOrdenacao === 'desc' ? 'desc' : 'asc';

        match ($this->ordenacao) {
            'iccid' => $query->orderBy('iccid', $direcao),
            'imei' => $query->orderBy(
                Rastreador::query()
                    ->select('imei')
                    ->whereColumn('rastreadores.chip_id', 'chips.id')
                    ->limit(1),
                $direcao,
            ),
            'fornecedor' => $query->orderBy(
                Fornecedor::query()
                    ->select('nome')
                    ->whereColumn('fornecedores.id', 'chips.fornecedor_id')
                    ->limit(1),
                $direcao,
            ),
            'operadora' => $query->orderBy(
                Operadora::query()
                    ->select('nome')
                    ->whereColumn('operadoras.id', 'chips.operadora_id')
                    ->limit(1),
                $direcao,
            ),
            'status' => $query->orderBy(
                StatusRastreador::query()
                    ->select('label')
                    ->whereColumn('status_rastreadores.id', 'chips.status_rastreador_id')
                    ->limit(1),
                $direcao,
            ),
            'tecnico' => $query->orderBy(
                Tecnico::query()
                    ->select('nome')
                    ->whereColumn('tecnicos.id', 'chips.tecnico_id')
                    ->limit(1),
                $direcao,
            ),
            default => $query->orderBy('numero_chip', $direcao),
        };

        return $query->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function formDataPadrao(): array
    {
        return [
            'fornecedor_id' => null,
            'operadora_id' => null,
            'numero_chip' => '',
            'iccid' => '',
            'tecnico_id' => null,
            'status_rastreador_id' => StatusRastreador::query()
                ->where('label', 'Disponivel')
                ->value('id'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function deveConfirmarSincronizacaoTecnico(array $data): bool
    {
        if ($this->sincronizacaoTecnicoConfirmada || $this->editingId === null) {
            return false;
        }

        $chip = Chip::query()->find($this->editingId);

        return $chip !== null
            && $chip->tecnico_id !== ($data['tecnico_id'] ?? null)
            && $this->chipPossuiVeiculoAtivo($chip);
    }

    private function chipPossuiVeiculoAtivo(Chip $chip): bool
    {
        if ($chip->statusRastreador?->label !== 'Ativo') {
            return false;
        }

        $rastreadorId = Rastreador::query()
            ->where('chip_id', $chip->id)
            ->value('id');

        return $rastreadorId !== null
            && Veiculo::query()
                ->ativos()
                ->where('rastreador_id', $rastreadorId)
                ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function descricaoSincronizacaoTecnico(array $data): string
    {
        $chip = Chip::query()->with('tecnico')->findOrFail($this->editingId);
        $rastreador = Rastreador::query()->where('chip_id', $chip->id)->first();
        $veiculo = $rastreador === null
            ? null
            : Veiculo::query()->ativos()->where('rastreador_id', $rastreador->id)->first();
        $tecnicoNovo = filled($data['tecnico_id'] ?? null)
            ? Tecnico::query()->find($data['tecnico_id'])?->nome
            : 'Sem tecnico';
        $identificacaoVeiculo = collect([$veiculo?->veiculo, $veiculo?->placa])
            ->filter()
            ->implode(' - ');

        return 'O chip ativo'.($rastreador?->imei ? ' do IMEI '.$rastreador->imei : '')
            .($identificacaoVeiculo !== '' ? ' esta vinculado ao veiculo '.$identificacaoVeiculo.'.' : '.')
            .' Ao confirmar, o tecnico sera alterado de '
            .($chip->tecnico?->nome ?? 'Sem tecnico').' para '.($tecnicoNovo ?? 'Sem tecnico')
            .' no chip, no rastreador e no tecnico de instalacao do veiculo. Deseja continuar?';
    }

    private function sincronizarTecnicoDoChipAtivo(Chip $chip): void
    {
        $rastreador = Rastreador::query()
            ->where('chip_id', $chip->id)
            ->lockForUpdate()
            ->first();

        if ($rastreador === null) {
            return;
        }

        if ($rastreador->tecnico_id !== $chip->tecnico_id) {
            $antes = AuditLogger::snapshot($rastreador);
            $rastreador->update(['tecnico_id' => $chip->tecnico_id]);

            AuditLogger::registrar(
                'rastreador.editado',
                'Tecnico do rastreador sincronizado com o chip ativo.',
                $rastreador,
                antes: $antes,
                depois: AuditLogger::snapshot($rastreador->refresh()),
                contexto: ['chip_id' => $chip->id],
            );
        }

        Veiculo::query()
            ->ativos()
            ->where('rastreador_id', $rastreador->id)
            ->lockForUpdate()
            ->get()
            ->each(function (Veiculo $veiculo) use ($chip, $rastreador): void {
                if ($veiculo->tecnico_instala_id === $chip->tecnico_id) {
                    return;
                }

                $antes = AuditLogger::snapshot($veiculo);
                $veiculo->update(['tecnico_instala_id' => $chip->tecnico_id]);

                AuditLogger::registrar(
                    'veiculo.tecnico_instalacao_sincronizado',
                    'Tecnico de instalacao sincronizado com o chip ativo.',
                    $veiculo,
                    antes: $antes,
                    depois: AuditLogger::snapshot($veiculo->refresh()),
                    contexto: [
                        'chip_id' => $chip->id,
                        'rastreador_id' => $rastreador->id,
                    ],
                );
            });
    }
}
