<?php

namespace App\Filament\Resources\Vendedores;

use App\Filament\Resources\Vendedores\Pages\CreateVendedor;
use App\Filament\Resources\Vendedores\Pages\EditVendedor;
use App\Filament\Resources\Vendedores\Pages\ListVendedores;
use App\Filament\Resources\Vendedores\Schemas\VendedorForm;
use App\Filament\Resources\Vendedores\Tables\VendedoresTable;
use App\Models\Permission;
use App\Models\Vendedor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class VendedorResource extends Resource
{
    protected static ?string $model = Vendedor::class;

    protected static ?string $slug = 'vendedores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastro';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Vendedor';

    protected static ?string $pluralModelLabel = 'Vendedores';

    protected static ?string $navigationLabel = 'Vendedores';

    public static function form(Schema $schema): Schema
    {
        return VendedorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendedoresTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return self::podeManter()
            || (auth()->user()?->hasPermission(Permission::CADASTRO_LEITURA) ?? false);
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
        return self::podeManter()
            || (auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false);
    }

    public static function canDeleteAny(): bool
    {
        return self::podeManter()
            || (auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false);
    }

    public static function podeManter(): bool
    {
        return (auth()->user()?->hasPermission(Permission::COORDENADOR) ?? false)
            || (auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendedores::route('/'),
            'create' => CreateVendedor::route('/create'),
            'edit' => EditVendedor::route('/{record}/edit'),
        ];
    }
}
