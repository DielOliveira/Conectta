<?php

namespace App\Filament\Resources\Disponibilidades;

use App\Filament\Resources\Disponibilidades\Pages\CreateDisponibilidade;
use App\Filament\Resources\Disponibilidades\Pages\EditDisponibilidade;
use App\Filament\Resources\Disponibilidades\Pages\ListDisponibilidades;
use App\Models\OrdemServicoDisponibilidade;
use App\Models\Permission;
use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DisponibilidadeResource extends Resource
{
    protected static ?string $model = OrdemServicoDisponibilidade::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Ordens de Serviço';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Disponibilidade';

    protected static ?string $pluralModelLabel = 'Agenda dos técnicos';

    protected static ?string $navigationLabel = 'Agenda dos técnicos';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Configuração da agenda')->schema([
            Grid::make(['default' => 1, 'xl' => 2])->schema([
                ToggleButtons::make('tipo')->label('O que deseja incluir?')->options([
                    OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE => 'Disponibilidade',
                    OrdemServicoDisponibilidade::TIPO_BLOQUEIO => 'Bloqueio',
                ])->icons([
                    OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE => Heroicon::OutlinedCheckCircle,
                    OrdemServicoDisponibilidade::TIPO_BLOQUEIO => Heroicon::OutlinedNoSymbol,
                ])->colors([
                    OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE => 'success',
                    OrdemServicoDisponibilidade::TIPO_BLOQUEIO => 'danger',
                ])->grouped()->default(OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE)->required()->disabledOn('edit'),
                ToggleButtons::make('modo')->label('Preencher')->options(['dia' => 'Um dia', 'semana' => 'Semana (segunda a sexta)'])
                    ->icons(['dia' => Heroicon::OutlinedCalendar, 'semana' => Heroicon::OutlinedCalendarDays])
                    ->grouped()->default('dia')->live()->dehydrated()->visibleOn('create')->required(),
            ]),
            Grid::make(['default' => 1, 'md' => 2, 'xl' => 6])->schema([
                Select::make('tecnico_id')->label('Técnico')->relationship(
                    'tecnico',
                    'nome',
                    fn (Builder $query): Builder => $query->where('is_ativo', true),
                )->searchable()->preload()->required()->columnSpan(['default' => 1, 'md' => 2, 'xl' => 2]),
                DatePicker::make('data')->label(fn (Get $get): string => $get('modo') === 'semana' ? 'Semana de referência' : 'Data')
                    ->native(false)->minDate(today())->required()->live()
                    ->prefixAction(Action::make('semanaAnterior')->icon(Heroicon::ChevronLeft)->iconButton()->color('gray')
                        ->tooltip('Semana anterior')->visible(fn (Get $get): bool => $get('modo') === 'semana')
                        ->action(fn (Get $get, Set $set) => $set('data', CarbonImmutable::parse($get('data') ?: today())->subWeek()->toDateString())))
                    ->suffixAction(Action::make('proximaSemana')->icon(Heroicon::ChevronRight)->iconButton()->color('gray')
                        ->tooltip('Próxima semana')->visible(fn (Get $get): bool => $get('modo') === 'semana')
                        ->action(fn (Get $get, Set $set) => $set('data', CarbonImmutable::parse($get('data') ?: today())->addWeek()->toDateString())))
                    ->helperText(function (Get $get): ?string {
                        if ($get('modo') !== 'semana' || blank($get('data'))) {
                            return null;
                        }
                        $inicio = CarbonImmutable::parse($get('data'))->startOfWeek(CarbonInterface::MONDAY);

                        return 'Será aplicado de '.$inicio->format('d/m').' a '.$inicio->addDays(4)->format('d/m').' (segunda a sexta).';
                    })->columnSpan(['default' => 1, 'md' => 2, 'xl' => 2]),
                TextInput::make('hora_inicio')->label('Início')->type('time')->required(),
                TextInput::make('hora_fim')->label('Fim')->type('time')->required(),
            ])])->columns(1)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('tecnico.nome')->label('Técnico')->searchable()->sortable(),
            TextColumn::make('tipo')->label('Tipo')->badge()->formatStateUsing(fn (string $state): string => $state === OrdemServicoDisponibilidade::TIPO_BLOQUEIO ? 'Bloqueio' : 'Disponível')
                ->color(fn (string $state): string => $state === OrdemServicoDisponibilidade::TIPO_BLOQUEIO ? 'danger' : 'success'),
            TextColumn::make('data')->label('Data')->date('d/m/Y')->sortable(),
            TextColumn::make('hora_inicio')->label('Início')->formatStateUsing(fn (?string $state): string => substr((string) $state, 0, 5)),
            TextColumn::make('hora_fim')->label('Fim')->formatStateUsing(fn (?string $state): string => substr((string) $state, 0, 5)),
            TextColumn::make('ordens_count')->counts('ordens')->label('OS vinculadas'),
        ])->recordActions([
            EditAction::make()->label('Editar'),
            DeleteAction::make()->label('Excluir')->requiresConfirmation()->modalDescription('Deseja excluir esta disponibilidade?'),
        ])->defaultSort('data');
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
        return auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false;
    }

    public static function canDelete($record): bool
    {
        return (auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false) && ! $record->ordens()->exists();
    }

    public static function getPages(): array
    {
        return ['index' => ListDisponibilidades::route('/'), 'create' => CreateDisponibilidade::route('/create'), 'edit' => EditDisponibilidade::route('/{record}/edit')];
    }
}
