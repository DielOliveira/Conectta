<?php

namespace App\Filament\Resources\CobrancaEnvios;

use App\Filament\Resources\CobrancaEnvios\Pages\ListCobrancaEnvios;
use App\Models\CobrancaEnvio;
use App\Models\Permission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CobrancaEnvioResource extends Resource
{
    protected static ?string $model = CobrancaEnvio::class;

    protected static ?string $slug = 'envios-cobranca';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Envio de cobranca';

    protected static ?string $pluralModelLabel = 'Envios de cobranca';

    protected static ?string $navigationLabel = 'Envios de cobranca';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('processado_em')
                    ->label('Processado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('cliente.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('telefone')
                    ->label('Telefone')
                    ->searchable(),
                TextColumn::make('erro')
                    ->label('Erro')
                    ->limit(60)
                    ->searchable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission(Permission::FINANCEIRO_LEITURA) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCobrancaEnvios::route('/'),
        ];
    }
}
