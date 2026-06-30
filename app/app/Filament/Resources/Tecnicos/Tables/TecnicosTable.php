<?php

namespace App\Filament\Resources\Tecnicos\Tables;

use App\Filament\Resources\Tecnicos\Pages\ListTecnicos;
use App\Models\Permission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TecnicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(view('filament.resources.tecnicos.table-toolbar-filters'))
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->sortable(),
                TextColumn::make('cpf')
                    ->label('CPF')
                    ->sortable(),
                TextColumn::make('telefone')
                    ->label('Telefone'),
                IconColumn::make('is_ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn (Builder $query, ListTecnicos $livewire): Builder => $livewire->aplicarFiltrosTecnicos($query))
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA) ?? false),
                DeleteAction::make()
                    ->label('Excluir')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA) ?? false)
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA) ?? false)
                        ->label('Excluir selecionados'),
                ]),
            ])
            ->defaultSort('nome');
    }
}