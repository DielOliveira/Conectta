<?php

namespace App\Filament\Resources\Tecnicos;

use App\Filament\Resources\Tecnicos\Pages\CreateTecnico;
use App\Filament\Resources\Tecnicos\Pages\EditTecnico;
use App\Filament\Resources\Tecnicos\Pages\ListTecnicos;
use App\Filament\Resources\Tecnicos\Schemas\TecnicoForm;
use App\Filament\Resources\Tecnicos\Tables\TecnicosTable;
use App\Models\Permission;
use App\Models\Tecnico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TecnicoResource extends Resource
{
    protected static ?string $model = Tecnico::class;

    protected static ?string $slug = 'tecnicos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Estoque';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Tecnico';

    protected static ?string $pluralModelLabel = 'Tecnicos';

    protected static ?string $navigationLabel = 'Tecnicos';

    public static function form(Schema $schema): Schema
    {
        return TecnicoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TecnicosTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return self::podeManter()
            || (auth()->user()?->hasPermission(Permission::ESTOQUE_LEITURA) ?? false);
    }

    public static function canCreate(): bool
    {
        return self::podeManter();
    }

    public static function canEdit($record): bool
    {
        return self::podeManter();
    }

    public static function canDelete($record): bool
    {
        return self::podeManter();
    }

    public static function canDeleteAny(): bool
    {
        return self::podeManter();
    }

    public static function podeManter(): bool
    {
        return (auth()->user()?->hasPermission(Permission::COORDENADOR) ?? false)
            || (auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA) ?? false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTecnicos::route('/'),
            'create' => CreateTecnico::route('/create'),
            'edit' => EditTecnico::route('/{record}/edit'),
        ];
    }
}
