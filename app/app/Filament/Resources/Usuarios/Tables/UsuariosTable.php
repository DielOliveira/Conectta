<?php

namespace App\Filament\Resources\Usuarios\Tables;

use App\Models\Permission;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsuariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('permissions.label')
                    ->label('Permissoes')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3)
                    ->expandableLimitedList(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (User $record): bool => self::podeOperarUsuario($record)),
                DeleteAction::make()
                    ->label('Excluir')
                    ->visible(fn (User $record): bool => self::podeOperarUsuario($record))
                    ->modalSubmitActionLabel('Excluir')
                    ->requiresConfirmation()
                    ->modalDescription('Deseja excluir este usuario?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                        ->label('Excluir selecionados')
                        ->modalSubmitActionLabel('Excluir')
                        ->requiresConfirmation()
                        ->modalDescription('Deseja excluir os usuarios selecionados?'),
                ]),
            ])
            ->defaultSort('name');
    }

    private static function podeOperarUsuario(User $record): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false)
            || (($user?->hasPermission(Permission::COORDENADOR) ?? false) && ! (bool) $record->is_admin);
    }
}
