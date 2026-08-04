<?php

namespace App\Filament\Resources\Disponibilidades;

use App\Filament\Resources\Disponibilidades\Pages\CreateDisponibilidade;
use App\Filament\Resources\Disponibilidades\Pages\ListDisponibilidades;
use App\Models\OrdemServicoDisponibilidade;
use App\Models\Permission;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
        return $schema->components([Section::make('Intervalo disponível')->schema([Grid::make(4)->schema([
            Select::make('tecnico_id')->label('Técnico')->relationship(
                'tecnico',
                'nome',
                fn (Builder $query): Builder => $query->where('is_ativo', true),
            )->searchable()->preload()->required()->columnSpan(2),
            DatePicker::make('data')->label('Data')->native(false)->minDate(today())->required(),
            TextInput::make('hora_inicio')->label('Início')->type('time')->required(),
            TextInput::make('hora_fim')->label('Fim')->type('time')->required(),
        ])])]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('tecnico.nome')->label('Técnico')->searchable()->sortable(),
            TextColumn::make('data')->label('Data')->date('d/m/Y')->sortable(),
            TextColumn::make('hora_inicio')->label('Início')->formatStateUsing(fn ($s) => substr($s, 0, 5)),
            TextColumn::make('hora_fim')->label('Fim')->formatStateUsing(fn ($s) => substr($s, 0, 5)),
            TextColumn::make('ordens_count')->counts('ordens')->label('OS vinculadas'),
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
        return false;
    }

    public static function canDelete($record): bool
    {
        return (auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false) && ! $record->ordens()->whereNotIn('status', ['cancelada', 'finalizada'])->exists();
    }

    public static function getPages(): array
    {
        return ['index' => ListDisponibilidades::route('/'), 'create' => CreateDisponibilidade::route('/create')];
    }
}
