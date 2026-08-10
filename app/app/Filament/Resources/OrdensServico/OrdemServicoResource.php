<?php

namespace App\Filament\Resources\OrdensServico;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Filament\Resources\OrdensServico\Pages\CreateOrdemServico;
use App\Filament\Resources\OrdensServico\Pages\EditOrdemServico;
use App\Filament\Resources\OrdensServico\Pages\ListOrdensServico;
use App\Filament\Resources\OrdensServico\RelationManagers\FotosRelationManager;
use App\Filament\Resources\OrdensServico\RelationManagers\HistoricosRelationManager;
use App\Filament\Resources\OrdensServico\RelationManagers\MensagensGeradasRelationManager;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Permission;
use App\Models\Veiculo;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class OrdemServicoResource extends Resource
{
    protected static ?string $model = OrdemServico::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Ordens de Serviço';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Ordem de serviço';

    protected static ?string $pluralModelLabel = 'Ordens de serviço';

    protected static ?string $navigationLabel = 'Ordens de serviço';

    public static function form(Schema $schema): Schema
    {
        $bloquearDadosAbertura = fn (?OrdemServico $record): bool => $record !== null && ! in_array($record->status, [
            OrdemServicoStatus::ABERTA,
            OrdemServicoStatus::ENVIADA,
            OrdemServicoStatus::ACEITA,
        ], true);
        $bloquearIdentificacao = fn (?OrdemServico $record): bool => $record !== null;

        return $schema->components([
            Section::make('Ordem de serviço')->schema([
                Grid::make(12)->schema([
                    TextInput::make('numero')
                        ->label('Número')
                        ->formatStateUsing(fn ($state): string => 'OS '.str_pad((string) $state, 6, '0', STR_PAD_LEFT))
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit')
                        ->columnSpan(2),
                    Select::make('tipo')->label('Tipo')->options(collect(OrdemServicoTipo::cases())->mapWithKeys(fn ($v) => [$v->value => $v->label()]))->disabled($bloquearIdentificacao)->required()->columnSpan(2),
                    Hidden::make('status'),
                    Select::make('cliente_id')->label('Cliente')->options(fn () => Cliente::query()->orderBy('nome')->pluck('nome', 'id')->all())
                        ->searchable()->live()->required()->afterStateUpdated(function (Set $set, ?int $state): void {
                            $set('veiculo_id', null);
                            $cliente = $state ? Cliente::query()->find($state) : null;
                            $set('endereco', $cliente ? collect([$cliente->rua, $cliente->numero, $cliente->setor, $cliente->cidade])->filter()->implode(', ') : null);
                        })->disabled($bloquearIdentificacao)->columnSpan(4),
                    Select::make('veiculo_id')->label('Veículo')->options(fn (Get $get) => Veiculo::query()->where('cliente_id', $get('cliente_id'))->orderBy('placa')->get()
                        ->mapWithKeys(fn (Veiculo $v) => [$v->id => trim(($v->placa ?: 'Sem placa').' - '.($v->veiculo ?: 'Veículo'))])->all())
                        ->searchable()->live()->disabled($bloquearIdentificacao)->required()
                        ->afterStateUpdated(function (Set $set, ?int $state): void {
                            $veiculo = $state ? Veiculo::query()->find($state) : null;
                            $set('veiculo_associado', $veiculo?->associado);
                            $set('veiculo_contato', $veiculo?->contato);
                        })->columnSpan(4),
                    Toggle::make('associado')->label('Associado')->live()->default(false)
                        ->afterStateUpdated(function (Set $set, Get $get, bool $state): void {
                            if ($state) {
                                $set('endereco', null);

                                return;
                            }

                            $cliente = $get('cliente_id') ? Cliente::query()->find($get('cliente_id')) : null;
                            $set('endereco', $cliente ? collect([$cliente->rua, $cliente->numero, $cliente->setor, $cliente->cidade])->filter()->implode(', ') : null);
                        })
                        ->disabled($bloquearIdentificacao)->required()->columnSpan(2),
                    TextInput::make('veiculo_associado')->label('Associado do veículo')
                        ->formatStateUsing(fn ($state, Get $get) => Veiculo::query()->find($get('veiculo_id'))?->associado)
                        ->visible(fn (Get $get): bool => (bool) $get('associado'))->disabled()->dehydrated(false)->required()->columnSpan(6),
                    TextInput::make('veiculo_contato')->label('Contato do associado')
                        ->formatStateUsing(fn ($state, Get $get) => Veiculo::query()->find($get('veiculo_id'))?->contato)
                        ->visible(fn (Get $get): bool => (bool) $get('associado'))->disabled()->dehydrated(false)->required()->columnSpan(6),
                    TextInput::make('endereco')->label('Endereço do atendimento')->disabled($bloquearDadosAbertura)->required()->maxLength(500)->columnSpanFull(),
                    Textarea::make('descricao')->label('Motivo ou descrição do serviço')->disabled($bloquearDadosAbertura)->required()->rows(3)->columnSpanFull(),
                    Textarea::make('observacoes')->label('Observações')->rows(3)->columnSpanFull(),
                    TextInput::make('localizacao_url')->label('Link de localização')->disabled($bloquearDadosAbertura)->url()->columnSpan(9),
                    Toggle::make('notificar_cliente')->label('Notificar cliente pelo WhatsApp')->disabled($bloquearDadosAbertura)->default(false)->columnSpan(3),
                ]),
            ])->columnSpanFull(),
            Section::make('Conferência da central')->schema([
                Grid::make(3)->schema([
                    ToggleButtons::make('check_funcionamento')->label('Funcionamento do equipamento')->options([1 => 'Conferido', 0 => 'Não conferido'])->inline()->grouped()->disabled(),
                    ToggleButtons::make('check_pos_chave')->label('Pós-chave')->options([1 => 'Conferido', 0 => 'Não conferido'])->inline()->grouped()->disabled(),
                    ToggleButtons::make('check_bloqueio')->label('Bloqueio do veículo')->options(['conferido' => 'Conferido', 'nao_se_aplica' => 'Não se aplica'])->inline()->grouped()->disabled(),
                ]),
                Textarea::make('motivo_pendencia')->label('Última pendência')->disabled()->dehydrated(false),
            ])->visibleOn('edit')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->header(view('filament.resources.ordens-servico.table-toolbar-filters'))
            ->columns([
                TextColumn::make('numero')->label('OS')->formatStateUsing(fn ($state) => 'OS '.str_pad((string) $state, 6, '0', STR_PAD_LEFT))->sortable(),
                TextColumn::make('cliente.nome')->label('Cliente')->formatStateUsing(fn ($state, OrdemServico $record): string => $record->nome_atendimento)->sortable(),
                TextColumn::make('veiculo.placa')->label('Placa')->sortable(),
                TextColumn::make('tipo')->label('Tipo')->formatStateUsing(fn (OrdemServicoTipo $state) => $state->label())->badge()->sortable(),
                TextColumn::make('status')->label('Status')->formatStateUsing(fn (OrdemServicoStatus $state) => $state->label())->badge()->sortable(),
                TextColumn::make('tecnico.nome')->label('Técnico')->placeholder('Não atribuído')->sortable(),
                TextColumn::make('agendado_em')->label('Atendimento')->dateTime('d/m/Y H:i')->placeholder('Não agendado')->sortable(),
            ])
            ->modifyQueryUsing(fn ($query, ListOrdensServico $livewire) => $livewire->aplicarFiltrosOrdensServico($query))
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission(Permission::OS_LEITURA) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasPermission(Permission::OS_LEITURA) ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListOrdensServico::route('/'), 'create' => CreateOrdemServico::route('/create'), 'edit' => EditOrdemServico::route('/{record}/edit')];
    }

    public static function getRelations(): array
    {
        return [MensagensGeradasRelationManager::class, FotosRelationManager::class, HistoricosRelationManager::class];
    }
}
