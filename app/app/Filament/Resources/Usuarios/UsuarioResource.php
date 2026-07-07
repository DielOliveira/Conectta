<?php

namespace App\Filament\Resources\Usuarios;

use App\Filament\Resources\Usuarios\Pages\CreateUsuario;
use App\Filament\Resources\Usuarios\Pages\EditUsuario;
use App\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Models\Permission;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UsuarioResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'usuarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $navigationLabel = 'Usuarios';

    public static function form(Schema $schema): Schema
    {
        return Schemas\UsuarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\UsuariosTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission(Permission::COORDENADOR) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermission(Permission::COORDENADOR) ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false)
            || (($user?->hasPermission(Permission::COORDENADOR) ?? false) && ! (bool) $record->is_admin);
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false)
            || (($user?->hasPermission(Permission::COORDENADOR) ?? false) && ! (bool) $record->is_admin);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsuarios::route('/'),
            'create' => CreateUsuario::route('/create'),
            'edit' => EditUsuario::route('/{record}/edit'),
        ];
    }
}
