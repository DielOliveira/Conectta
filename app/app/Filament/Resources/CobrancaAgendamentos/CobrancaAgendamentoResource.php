<?php

namespace App\Filament\Resources\CobrancaAgendamentos;

use App\Filament\Resources\CobrancaAgendamentos\Pages\CreateCobrancaAgendamento;
use App\Filament\Resources\CobrancaAgendamentos\Pages\EditCobrancaAgendamento;
use App\Filament\Resources\CobrancaAgendamentos\Pages\ListCobrancaAgendamentos;
use App\Models\CobrancaAgendamento;
use App\Models\Permission;
use App\Services\Cobranca\CobrancaAgendamentoService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

class CobrancaAgendamentoResource extends Resource
{
    protected static ?string $model = CobrancaAgendamento::class;

    protected static ?string $slug = 'agendamentos-cobranca';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Rotinas';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Agendamento de cobranca';

    protected static ?string $pluralModelLabel = 'Agendamentos de cobranca';

    protected static ?string $navigationLabel = 'Agendamentos de cobranca';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rotina')
                ->schema([
                    Grid::make(12)->schema([
                        Select::make('tipo')
                            ->label('Tipo')
                            ->options(CobrancaAgendamento::TIPOS)
                            ->required()
                            ->searchable()
                            ->rules(fn (?CobrancaAgendamento $record): array => [
                                Rule::unique('cobranca_agendamentos', 'tipo')->ignore($record?->id),
                            ])
                            ->columnSpan(5),
                        TextInput::make('horario')
                            ->label('Horario')
                            ->type('time')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('limite')
                            ->label('Limite')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Opcional. Limita itens processados nesta rotina.')
                            ->columnSpan(2),
                        CheckboxList::make('dias_semana')
                            ->label('Dias da semana')
                            ->options(CobrancaAgendamento::DIAS_SEMANA)
                            ->columns(4)
                            ->default(array_keys(CobrancaAgendamento::DIAS_SEMANA))
                            ->required()
                            ->columnSpan(12),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Execucao')
                ->schema([
                    Grid::make(3)->schema([
                        Toggle::make('ativo')
                            ->label('Ativo')
                            ->helperText('Permite que o agendamento rode automaticamente no horario configurado.'),
                        Toggle::make('dry_run')
                            ->label('Simular')
                            ->helperText('Quando ativo, nao gera nem envia de verdade.')
                            ->default(true),
                        Toggle::make('enviar_whatsapp')
                            ->label('WhatsApp')
                            ->helperText('Envia os pendentes gerados nesta execucao.'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo')
                    ->label('Rotina')
                    ->formatStateUsing(fn (string $state): string => CobrancaAgendamento::TIPOS[$state] ?? $state)
                    ->searchable()
                    ->sortable(),
                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('horario')
                    ->label('Horario')
                    ->formatStateUsing(fn (?string $state): string => substr((string) $state, 0, 5))
                    ->sortable(),
                TextColumn::make('dias_semana_resumo')
                    ->label('Dias')
                    ->state(fn (CobrancaAgendamento $record): string => $record->diasSemanaIniciais()),
                IconColumn::make('dry_run')
                    ->label('Simulacao')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('enviar_whatsapp')
                    ->label('WhatsApp')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('ultima_execucao_em')
                    ->label('Ultima execucao')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('proxima_execucao_em')
                    ->label('Proxima execucao')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ultimo_status')
                    ->label('Ultimo status')
                    ->badge()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('executarAgora')
                    ->label('Executar agora')
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalSubmitActionLabel('Executar')
                    ->modalDescription('Deseja executar esta rotina agora?')
                    ->action(function (CobrancaAgendamento $record): void {
                        $resultado = app(CobrancaAgendamentoService::class)->executar($record, manual: true);

                        Notification::make()
                            ->title('Rotina executada.')
                            ->body('Execucoes: '.implode(', ', $resultado['execucao_ids']))
                            ->success()
                            ->send();
                    }),
                EditAction::make()->label('Editar'),
            ])
            ->defaultSort('tipo');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission(Permission::TECNICO) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermission(Permission::TECNICO) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasPermission(Permission::TECNICO) ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCobrancaAgendamentos::route('/'),
            'create' => CreateCobrancaAgendamento::route('/create'),
            'edit' => EditCobrancaAgendamento::route('/{record}/edit'),
        ];
    }
}
