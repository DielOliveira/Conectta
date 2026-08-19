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
use App\Services\OrdemServico\OrdemServicoEquipamentoReserva;
use App\Support\ChipNumber;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
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

    public function confirmarSincronizacaoTecnicoAction(): Action
    {
        return Action::make('confirmarSincronizacaoTecnico')
            ->requiresConfirmation()
            ->modalHeading('Alterar tecnico do rastreador ativo')
            ->modalDescription(fn (): string => $this->sincronizacaoTecnicoDescricao
                ?? 'A alteracao tambem sera aplicada ao chip e ao tecnico de instalacao do veiculo ativo. Deseja continuar?')
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

    public function adicionarChipAction(): Action
    {
        return Action::make('adicionarChip')
            ->label('Adicionar chip')
            ->icon(Heroicon::Plus)
            ->modalHeading('Adicionar chip ao rastreador')
            ->modalSubmitActionLabel('Adicionar chip')
            ->modalSubmitAction(fn (Action $action): Action => $action
                ->label('Adicionar chip')
                ->disabled(fn (): bool => $this->chipSelecionadoIndisponivel()))
            ->fillForm(function (array $arguments): array {
                $rastreador = Rastreador::query()->findOrFail((int) $arguments['id']);

                return [
                    'chip_id' => null,
                    'fornecedor_id' => null,
                    'operadora_id' => null,
                    'numero_chip' => null,
                    'iccid' => '',
                    'imei' => $rastreador->imei,
                ];
            })
            ->schema([
                Grid::make(2)->schema([
                    Select::make('numero_chip')
                        ->label('Numero Chip')
                        ->required()
                        ->prefix('55')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->options(fn (): array => Chip::query()
                            ->tap(fn (Builder $query) => OrdemServicoEquipamentoReserva::excluirChipsReservados($query))
                            ->orderBy('numero_chip')
                            ->get()
                            ->mapWithKeys(fn (Chip $chip): array => [
                                $chip->numero_chip => ChipNumber::local($chip->numero_chip),
                            ])
                            ->all())
                        ->getOptionLabelUsing(fn (mixed $value): string => ChipNumber::local((string) $value))
                        ->createOptionForm([
                            TextInput::make('numero_chip')
                                ->label('Numero Chip')
                                ->required()
                                ->prefix('55')
                                ->mask('(99) 99999-9999')
                                ->stripCharacters(['(', ')', ' ', '-'])
                                ->regex(ChipNumber::LOCAL_REGEX)
                                ->validationMessages([
                                    'regex' => 'Informe um número de celular completo, com DDD válido.',
                                ]),
                        ])
                        ->createOptionModalHeading('Informar novo numero de chip')
                        ->createOptionAction(fn (Action $action): Action => $action
                            ->label('Usar novo numero'))
                        ->createOptionUsing(fn (array $data): string => ChipNumber::canonical($data['numero_chip']))
                        ->afterStateUpdated(function (Set $set, mixed $state): void {
                            $chip = Chip::query()
                                ->where('numero_chip', ChipNumber::canonical((string) $state))
                                ->first();

                            $set('chip_id', $chip?->id);
                            $set('fornecedor_id', $chip?->fornecedor_id);
                            $set('operadora_id', $chip?->operadora_id);
                            $set('iccid', $chip?->iccid ?? '');
                        })
                        ->helperText(function (Get $get): ?HtmlString {
                            $mensagem = $this->chipIndisponibilidadeMensagem((int) $get('chip_id'));

                            return $mensagem
                                ? new HtmlString('<span class="font-medium text-danger-600 dark:text-danger-400">'.e($mensagem).'</span>')
                                : null;
                        })
                        ->rules([
                            fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                if (! preg_match(ChipNumber::LOCAL_REGEX, ChipNumber::local((string) $value))) {
                                    $fail('Informe um número de celular completo, com DDD válido.');
                                }

                                if ($mensagem = $this->chipIndisponibilidadeMensagem((int) $get('chip_id'))) {
                                    $fail($mensagem);
                                }
                            },
                        ])
                        ->placeholder('Pesquisar numero do chip')
                        ->searchPrompt('Digite o numero do chip para pesquisar')
                        ->noSearchResultsMessage('Nenhum chip encontrado. Use a opção para informar um novo número.'),
                    TextInput::make('iccid')
                        ->label('ICCID')
                        ->required()
                        ->regex('/^\d{20}$/')
                        ->validationMessages([
                            'regex' => 'O ICCID deve conter exatamente 20 digitos.',
                        ])
                        ->rules(fn (Get $get): array => [
                            Rule::unique('chips', 'iccid')->ignore($get('chip_id')),
                        ])
                        ->disabled(fn (Get $get): bool => filled($get('chip_id')))
                        ->maxLength(20)
                        ->extraInputAttributes([
                            'inputmode' => 'numeric',
                            'pattern' => '[0-9]{20}',
                        ]),
                    Select::make('fornecedor_id')
                        ->label('Fornecedor')
                        ->options(fn (): array => Fornecedor::query()->orderBy('id')->pluck('nome', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->disabled(fn (Get $get): bool => filled($get('chip_id'))),
                    Select::make('operadora_id')
                        ->label('Operadora')
                        ->options(fn (): array => Operadora::query()->orderBy('id')->pluck('nome', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->disabled(fn (Get $get): bool => filled($get('chip_id'))),
                    TextInput::make('imei')
                        ->label('IMEI')
                        ->disabled()
                        ->dehydrated(false),
                ]),
                TextInput::make('chip_id')
                    ->hidden()
                    ->dehydrated(),
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

                    $numeroChip = ChipNumber::canonical($data['numero_chip']);
                    $chip = Chip::query()
                        ->lockForUpdate()
                        ->where('numero_chip', $numeroChip)
                        ->first();

                    if ($chip && $this->chipIndisponivel($chip->id)) {
                        return null;
                    }

                    if ($chip) {
                        $chip->update([
                            'tecnico_id' => $rastreador->tecnico_id,
                            'status_rastreador_id' => $rastreador->status_rastreador_id,
                        ]);
                    } else {
                        $data['numero_chip'] = $numeroChip;
                        $data['tecnico_id'] = $rastreador->tecnico_id;
                        $data['status_rastreador_id'] = $rastreador->status_rastreador_id;
                        unset($data['chip_id']);

                        $chip = Chip::query()->create($this->prepararDadosNovoChip($data));
                    }

                    $rastreador->update(['chip_id' => $chip->id]);

                    AuditLogger::registrar(
                        'rastreador.editado',
                        'Chip vinculado ao rastreador no estoque.',
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
                        ->title('Não foi possível vincular o chip. Verifique se ambos continuam disponíveis.')
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

    private function prepararDadosNovoChip(array $data): array
    {
        $data['fornecedor'] = filled($data['fornecedor_id'] ?? null)
            ? Fornecedor::query()->findOrFail($data['fornecedor_id'])->nome
            : null;
        $data['operadora'] = filled($data['operadora_id'] ?? null)
            ? Operadora::query()->findOrFail($data['operadora_id'])->nome
            : null;

        return $data;
    }

    private function chipSelecionadoIndisponivel(): bool
    {
        $data = $this->mountedActions[array_key_last($this->mountedActions)]['data'] ?? [];

        return filled($data['chip_id'] ?? null) && $this->chipIndisponivel((int) $data['chip_id']);
    }

    private function chipIndisponivel(int $chipId): bool
    {
        return $this->chipIndisponibilidadeMensagem($chipId) !== null;
    }

    private function chipIndisponibilidadeMensagem(int $chipId): ?string
    {
        if ($chipId <= 0) {
            return null;
        }

        $chip = Chip::query()
            ->with(['statusRastreador', 'rastreador'])
            ->find($chipId);

        if (! $chip) {
            return null;
        }

        if ($mensagem = OrdemServicoEquipamentoReserva::mensagemChip($chipId)) {
            return $mensagem;
        }

        $status = $chip->statusRastreador?->label ?? 'Sem status';
        $rastreador = $chip->rastreador;

        if ($status === 'Disponivel' && ! $rastreador) {
            return null;
        }

        if ($rastreador) {
            return "Este chip está com status “{$status}” e já está vinculado ao rastreador IMEI {$rastreador->imei}. Somente chips disponíveis e sem vínculo podem ser selecionados.";
        }

        return "Este chip está com status “{$status}”. Somente chips com status “Disponivel” podem ser vinculados.";
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

                if ($mensagem = OrdemServicoEquipamentoReserva::mensagemRastreador((int) $arguments['id'])) {
                    Notification::make()->title($mensagem)->danger()->send();

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

    public bool $sincronizacaoTecnicoConfirmada = false;

    public ?string $sincronizacaoTecnicoDescricao = null;

    public string $modelo = '';

    public ?int $ativacao = null;

    public string $imei = '';

    public ?int $status_rastreador_id = null;

    public ?int $tecnico_id = null;

    public string $search = '';

    public ?int $filtroTecnicoId = null;

    public ?int $filtroStatusId = null;

    public string $ordenacao = 'imei';

    public string $direcaoOrdenacao = 'asc';

    public int $pagina = 1;

    private const ITENS_POR_PAGINA = 15;

    private const CAMPOS_ORDENAVEIS = [
        'modelo',
        'numero_chip',
        'imei',
        'ativacao',
        'status',
        'tecnico',
    ];

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

        if ($this->deveConfirmarSincronizacaoTecnico($data)) {
            $this->sincronizacaoTecnicoDescricao = $this->descricaoSincronizacaoTecnico($data);
            $this->mountAction('confirmarSincronizacaoTecnico');

            return;
        }

        $data = [
            ...$data,
            'is_estoque' => true,
            'criado_em' => now(),
        ];

        if ($this->editingId) {
            DB::transaction(function () use ($data): void {
                $rastreador = Rastreador::query()->lockForUpdate()->findOrFail($this->editingId);
                $antes = AuditLogger::snapshot($rastreador);
                $rastreador->update($data);
                $tecnicoAlterado = $rastreador->wasChanged('tecnico_id');
                $statusAlterado = $rastreador->wasChanged('status_rastreador_id');
                $rastreador->refresh();

                if (($tecnicoAlterado || $statusAlterado) && $rastreador->chip_id !== null) {
                    $chip = Chip::query()->lockForUpdate()->find($rastreador->chip_id);

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

                if ($tecnicoAlterado) {
                    $this->sincronizarTecnicoInstalacao($rastreador);
                }

                AuditLogger::registrar(
                    'rastreador.editado',
                    'Rastreador editado no estoque.',
                    $rastreador,
                    antes: $antes,
                    depois: AuditLogger::snapshot($rastreador),
                );
            });
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

        Rastreador::query()->findOrFail($id)->delete();

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

    /**
     * @param  array<string, mixed>  $data
     */
    private function deveConfirmarSincronizacaoTecnico(array $data): bool
    {
        if ($this->sincronizacaoTecnicoConfirmada || $this->editingId === null) {
            return false;
        }

        $rastreador = Rastreador::query()->find($this->editingId);

        return $rastreador !== null
            && $rastreador->tecnico_id !== ($data['tecnico_id'] ?? null)
            && $rastreador->statusRastreador?->label === 'Ativo'
            && Veiculo::query()
                ->ativos()
                ->where('rastreador_id', $rastreador->id)
                ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function descricaoSincronizacaoTecnico(array $data): string
    {
        $rastreador = Rastreador::query()->with('tecnico')->findOrFail($this->editingId);
        $veiculo = Veiculo::query()
            ->ativos()
            ->where('rastreador_id', $rastreador->id)
            ->first();
        $tecnicoNovo = filled($data['tecnico_id'] ?? null)
            ? Tecnico::query()->find($data['tecnico_id'])?->nome
            : 'Sem tecnico';
        $identificacaoVeiculo = collect([$veiculo?->veiculo, $veiculo?->placa])
            ->filter()
            ->implode(' - ');

        return 'O rastreador ativo de IMEI '.$rastreador->imei
            .($identificacaoVeiculo !== '' ? ' esta vinculado ao veiculo '.$identificacaoVeiculo.'.' : '.')
            .' Ao confirmar, o tecnico sera alterado de '
            .($rastreador->tecnico?->nome ?? 'Sem tecnico').' para '.($tecnicoNovo ?? 'Sem tecnico')
            .' no rastreador, no chip vinculado e no tecnico de instalacao do veiculo. Deseja continuar?';
    }

    private function sincronizarTecnicoInstalacao(Rastreador $rastreador): void
    {
        Veiculo::query()
            ->ativos()
            ->where('rastreador_id', $rastreador->id)
            ->lockForUpdate()
            ->get()
            ->each(function (Veiculo $veiculo) use ($rastreador): void {
                if ($veiculo->tecnico_instala_id === $rastreador->tecnico_id) {
                    return;
                }

                $antes = AuditLogger::snapshot($veiculo);
                $veiculo->update(['tecnico_instala_id' => $rastreador->tecnico_id]);

                AuditLogger::registrar(
                    'veiculo.tecnico_instalacao_sincronizado',
                    'Tecnico de instalacao sincronizado com o rastreador ativo.',
                    $veiculo,
                    antes: $antes,
                    depois: AuditLogger::snapshot($veiculo->refresh()),
                    contexto: ['rastreador_id' => $rastreador->id],
                );
            });
    }

    private function rastreadoresQuery(): Builder
    {
        $query = Rastreador::query()
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
            });

        $direcao = $this->direcaoOrdenacao === 'desc' ? 'desc' : 'asc';

        match ($this->ordenacao) {
            'modelo' => $query->orderBy('modelo', $direcao),
            'numero_chip' => $query->orderBy(
                Chip::query()
                    ->select('numero_chip')
                    ->whereColumn('chips.id', 'rastreadores.chip_id')
                    ->limit(1),
                $direcao,
            ),
            'ativacao' => $query->orderBy('ativacao', $direcao),
            'status' => $query->orderBy(
                StatusRastreador::query()
                    ->select('label')
                    ->whereColumn('status_rastreadores.id', 'rastreadores.status_rastreador_id')
                    ->limit(1),
                $direcao,
            ),
            'tecnico' => $query->orderBy(
                Tecnico::query()
                    ->select('nome')
                    ->whereColumn('tecnicos.id', 'rastreadores.tecnico_id')
                    ->limit(1),
                $direcao,
            ),
            default => $query->orderBy('imei', $direcao),
        };

        return $query->orderBy('id');
    }
}
