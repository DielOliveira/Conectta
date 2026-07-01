<?php

namespace App\Filament\Resources\Contratos;

use App\Filament\Resources\Contratos\Pages\CreateContrato;
use App\Filament\Resources\Contratos\Pages\EditContrato;
use App\Filament\Resources\Contratos\Pages\ListContratos;
use App\Filament\Resources\Contratos\Schemas\ContratoForm;
use App\Filament\Resources\Contratos\Tables\ContratosTable;
use App\Models\Contrato;
use App\Models\Permission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContratoResource extends Resource
{
    protected static ?string $model = Contrato::class;

    protected static ?string $slug = 'contratos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastro';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Contrato';

    protected static ?string $pluralModelLabel = 'Contratos';

    protected static ?string $navigationLabel = 'Contratos';

    public static function form(Schema $schema): Schema
    {
        return ContratoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContratosTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return (auth()->user()?->isAdmin() ?? false)
            || (auth()->user()?->hasPermission(Permission::CADASTRO_LEITURA) ?? false)
            || (auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false);
    }

    public static function canCreate(): bool
    {
        return (auth()->user()?->isAdmin() ?? false)
            || (auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false);
    }

    public static function canEdit($record): bool
    {
        $podeEditar = (auth()->user()?->isAdmin() ?? false)
            || (auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false);

        if (! $podeEditar) {
            return false;
        }

        $record?->loadMissing('statusContrato');

        return $record?->statusContrato?->label === 'Nao Enviado';
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
            'index' => ListContratos::route('/'),
            'create' => CreateContrato::route('/create'),
            'edit' => EditContrato::route('/{record}/edit'),
        ];
    }
}
