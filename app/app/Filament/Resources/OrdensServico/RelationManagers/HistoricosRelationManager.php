<?php

namespace App\Filament\Resources\OrdensServico\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistoricosRelationManager extends RelationManager
{
    protected static string $relationship = 'historicos';

    protected static ?string $title = 'Histórico da ordem';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Data e hora')->dateTime('d/m/Y H:i:s'),
            TextColumn::make('evento')->label('Evento')->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state)))->badge(),
            TextColumn::make('user.name')->label('Operador')->placeholder('—'),
            TextColumn::make('tecnico.nome')->label('Técnico')->placeholder('—'),
            TextColumn::make('observacao')->label('Observação')->wrap()->placeholder('—'),
        ])->defaultSort('created_at');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
