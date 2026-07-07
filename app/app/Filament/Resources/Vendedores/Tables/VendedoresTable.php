<?php

namespace App\Filament\Resources\Vendedores\Tables;

use App\Filament\Resources\Vendedores\VendedorResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendedoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (): bool => VendedorResource::podeManter()),
                DeleteAction::make()
                    ->label('Excluir')
                    ->visible(fn (): bool => VendedorResource::canDeleteAny())
                    ->modalSubmitActionLabel('Excluir')
                    ->requiresConfirmation()
                    ->modalDescription('Deseja excluir este vendedor?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => VendedorResource::canDeleteAny())
                        ->label('Excluir selecionados')
                        ->modalSubmitActionLabel('Excluir')
                        ->requiresConfirmation()
                        ->modalDescription('Deseja excluir os vendedores selecionados?'),
                ]),
            ])
            ->defaultSort('nome');
    }
}
