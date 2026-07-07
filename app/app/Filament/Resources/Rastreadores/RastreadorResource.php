<?php

namespace App\Filament\Resources\Rastreadores;

use App\Filament\Resources\Rastreadores\Pages\CreateRastreador;
use App\Filament\Resources\Rastreadores\Pages\EditRastreador;
use App\Filament\Resources\Rastreadores\Pages\ListRastreadores;
use App\Filament\Resources\Rastreadores\Schemas\RastreadorForm;
use App\Filament\Resources\Rastreadores\Tables\RastreadoresTable;
use App\Models\Permission;
use App\Models\Veiculo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RastreadorResource extends Resource
{
    protected static ?string $model = Veiculo::class;

    protected static ?string $slug = 'rastreadores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Truck;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastro';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Rastreador';

    protected static ?string $pluralModelLabel = 'Rastreadores';

    protected static ?string $navigationLabel = 'Rastreadores';

    public static function form(Schema $schema): Schema
    {
        return RastreadorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RastreadoresTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('data_exclusao')
            ->when(request()->integer('cliente_id'), fn (Builder $query, int $clienteId): Builder => $query->where('cliente_id', $clienteId))
            ->with(['cliente', 'rastreador', 'tipoVeiculo', 'statusRastreador']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission(Permission::CADASTRO_LEITURA) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false;
    }

    public static function canEdit($record): bool
    {
        return (auth()->user()?->hasPermission(Permission::CADASTRO_LEITURA) ?? false)
            || (auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRastreadores::route('/'),
            'create' => CreateRastreador::route('/create'),
            'edit' => EditRastreador::route('/{record}/edit'),
        ];
    }
}
