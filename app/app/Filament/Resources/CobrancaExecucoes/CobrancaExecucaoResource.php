<?php

namespace App\Filament\Resources\CobrancaExecucoes;

use App\Filament\Resources\CobrancaExecucoes\Pages\ListCobrancaExecucoes;
use App\Filament\Resources\CobrancaExecucoes\Pages\ViewCobrancaExecucao;
use App\Models\CobrancaExecucao;
use App\Models\Permission;
use Filament\Actions\Action;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CobrancaExecucaoResource extends Resource
{
    protected static ?string $model = CobrancaExecucao::class;

    protected static ?string $slug = 'cobrancas-automaticas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Rotinas';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Execucao de cobranca';

    protected static ?string $pluralModelLabel = 'Execucoes de cobranca';

    protected static ?string $navigationLabel = 'Cobrancas automaticas';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('data_processamento')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('dry_run')
                    ->label('Simulacao')
                    ->boolean(),
                TextColumn::make('total_processados')
                    ->label('Processados')
                    ->sortable(),
                TextColumn::make('total_enviados')
                    ->label('Enviados/pendentes')
                    ->sortable(),
                TextColumn::make('total_ignorados')
                    ->label('Ignorados')
                    ->sortable(),
                TextColumn::make('total_erros')
                    ->label('Erros')
                    ->sortable(),
                TextColumn::make('iniciado_em')
                    ->label('Iniciou')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('finalizado_em')
                    ->label('Finalizou')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('envios')
                    ->label('Ver envios')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (CobrancaExecucao $record): string => self::getUrl('view', ['record' => $record])),
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

    public static function canView($record): bool
    {
        return auth()->user()?->hasPermission(Permission::FINANCEIRO_LEITURA) ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCobrancaExecucoes::route('/'),
            'view' => ViewCobrancaExecucao::route('/{record}'),
        ];
    }
}
