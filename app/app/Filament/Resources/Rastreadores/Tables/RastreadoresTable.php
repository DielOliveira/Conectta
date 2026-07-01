<?php

namespace App\Filament\Resources\Rastreadores\Tables;

use App\Filament\Resources\Rastreadores\Pages\ListRastreadores;
use App\Models\Permission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RastreadoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(view('filament.resources.rastreadores.table-toolbar-filters'))
            ->columns([
                TextColumn::make('rastreador.imei')
                    ->label('Rastreador')
                    ->sortable(),
                TextColumn::make('cliente.nome')
                    ->label('Cliente')
                    ->wrap()
                    ->extraCellAttributes(['style' => 'width: 180px; max-width: 180px; white-space: normal; word-break: break-word;'])
                    ->extraHeaderAttributes(['style' => 'width: 180px; max-width: 180px;'])
                    ->sortable(),
                TextColumn::make('veiculo')
                    ->label('Veiculo')
                    ->sortable(),
                TextColumn::make('tipoVeiculo.label')
                    ->label('Tipo')
                    ->sortable(),
                TextColumn::make('placa')
                    ->label('Placa')
                    ->sortable(),
                TextColumn::make('statusRastreador.label')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('data_instalacao')
                    ->label('Instalacao')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_retirada')
                    ->label('Remocao')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn (Builder $query, ListRastreadores $livewire): Builder => $livewire->aplicarFiltrosRastreadores($query))
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false),
                DeleteAction::make()
                    ->label('Excluir')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false)
                    ->modalSubmitActionLabel('Excluir')
                    ->requiresConfirmation()
                    ->modalDescription('Deseja excluir este rastreador?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false)
                        ->label('Excluir selecionados')
                        ->modalSubmitActionLabel('Excluir')
                        ->requiresConfirmation()
                        ->modalDescription('Deseja excluir os rastreadores selecionados?'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
