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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
        return $schema->components([
            Section::make('Ordem de serviço')->schema([
                Grid::make(12)->schema([
                    TextInput::make('numero_formatado')->label('Número')->disabled()->dehydrated(false)->visibleOn('edit')->columnSpan(2),
                    Select::make('tipo')->label('Tipo')->options(collect(OrdemServicoTipo::cases())->mapWithKeys(fn ($v) => [$v->value => $v->label()]))->required()->columnSpan(3),
                    Hidden::make('status'),
                    Select::make('cliente_id')->label('Cliente')->options(fn () => Cliente::query()->orderBy('nome')->pluck('nome', 'id')->all())
                        ->searchable()->live()->required()->afterStateUpdated(function (Set $set, ?int $state): void {
                            $set('veiculo_id', null);
                            $cliente = $state ? Cliente::query()->find($state) : null;
                            $set('endereco', $cliente ? collect([$cliente->rua, $cliente->numero, $cliente->setor, $cliente->cidade])->filter()->implode(', ') : null);
                        })->columnSpan(7),
                    Select::make('veiculo_id')->label('Veículo')->options(fn (Get $get) => Veiculo::query()->where('cliente_id', $get('cliente_id'))->orderBy('placa')->get()
                        ->mapWithKeys(fn (Veiculo $v) => [$v->id => trim(($v->placa ?: 'Sem placa').' - '.($v->veiculo ?: 'Veículo'))])->all())
                        ->searchable()->required()->columnSpan(6),
                    DateTimePicker::make('atendimento_desejado_em')->label('Data e horário desejados')->seconds(false)->native(false)->minDate(fn (?OrdemServico $record) => $record ? null : now())->required()->columnSpan(6),
                    TextInput::make('endereco')->label('Endereço do atendimento')->required()->maxLength(500)->columnSpanFull(),
                    Textarea::make('descricao')->label('Motivo ou descrição do serviço')->required()->rows(3)->columnSpanFull(),
                    Textarea::make('observacoes')->label('Observações')->rows(3)->columnSpanFull(),
                    TextInput::make('localizacao_url')->label('Link de localização')->url()->columnSpan(9),
                    Toggle::make('notificar_cliente')->label('Notificar cliente pelo WhatsApp')->default(false)->columnSpan(3),
                ]),
            ])->columnSpanFull(),
            Section::make('Conferência da central')->schema([
                Grid::make(3)->schema([
                    Toggle::make('check_funcionamento')->label('Funcionamento do equipamento'),
                    Toggle::make('check_pos_chave')->label('Pós-chave'),
                    Select::make('check_bloqueio')->label('Bloqueio do veículo')->options(['conferido' => 'Conferido', 'nao_se_aplica' => 'Não se aplica']),
                ]),
                Textarea::make('motivo_pendencia')->label('Última pendência')->disabled()->dehydrated(false),
            ])->visibleOn('edit')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('numero')->label('OS')->formatStateUsing(fn ($state) => 'OS '.str_pad((string) $state, 6, '0', STR_PAD_LEFT))->searchable()->sortable(),
            TextColumn::make('cliente.nome')->label('Cliente')->searchable()->sortable(),
            TextColumn::make('veiculo.placa')->label('Placa')->searchable()->sortable(),
            TextColumn::make('tipo')->label('Tipo')->formatStateUsing(fn (OrdemServicoTipo $state) => $state->label())->badge()->sortable(),
            TextColumn::make('status')->label('Status')->formatStateUsing(fn (OrdemServicoStatus $state) => $state->label())->badge()->sortable(),
            TextColumn::make('tecnico.nome')->label('Técnico')->placeholder('Não atribuído')->sortable(),
            TextColumn::make('agendado_em')->label('Atendimento')->dateTime('d/m/Y H:i')->placeholder('Não agendado')->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(collect(OrdemServicoStatus::cases())->mapWithKeys(fn ($v) => [$v->value => $v->label()])),
            SelectFilter::make('tipo')->options(collect(OrdemServicoTipo::cases())->mapWithKeys(fn ($v) => [$v->value => $v->label()])),
            SelectFilter::make('tecnico_id')->label('Técnico')->relationship('tecnico', 'nome')->searchable()->preload(),
            Filter::make('periodo')->schema([DateTimePicker::make('inicio')->label('De'), DateTimePicker::make('fim')->label('Até')])
                ->query(fn (Builder $query, array $data) => $query->when($data['inicio'] ?? null, fn ($q, $v) => $q->where('agendado_em', '>=', $v))->when($data['fim'] ?? null, fn ($q, $v) => $q->where('agendado_em', '<=', $v))),
        ])->defaultSort('created_at', 'desc');
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
