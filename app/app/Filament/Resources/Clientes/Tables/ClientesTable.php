<?php

namespace App\Filament\Resources\Clientes\Tables;

use App\Filament\Resources\Clientes\Pages\ListClientes;
use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Models\Cliente;
use App\Models\Permission;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'ct-selectable-table'], merge: true)
            ->recordAction(null)
            ->recordUrl(null)
            ->header(view('filament.resources.clientes.table-toolbar-filters'))
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->sortable(),
                TextColumn::make('cpf_cnpj_formatado')
                    ->label('CPF ou CNPJ')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('cpf_cnpj', $direction)),
                TextColumn::make('data_adesao')
                    ->label(html_entity_decode('Data de Ades&atilde;o', ENT_QUOTES, 'UTF-8'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('statusCliente.label')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('veiculos_ativos_count')
                    ->label('Qtd.')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn (Builder $query, ListClientes $livewire): Builder => $livewire->aplicarFiltrosClientes($query))
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false)
                    ->icon(Heroicon::PencilSquare),
                Action::make('veiculos')
                    ->label('Rastreadores')
                    ->icon(Heroicon::Truck)
                    ->url(fn (Cliente $record): string => RastreadorResource::getUrl('index', [
                        'cliente_id' => $record->id,
                    ])),
                Action::make('adicionar_veiculo')
                    ->label('Adicionar')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false)
                    ->icon(Heroicon::Plus)
                    ->url(fn (Cliente $record): string => RastreadorResource::getUrl('create', [
                        'cliente_id' => $record->id,
                    ])),
                DeleteAction::make()
                    ->label('Excluir')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false)
                    ->modalSubmitActionLabel('Excluir')
                    ->requiresConfirmation()
                    ->modalDescription('Deseja excluir este cliente?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Excluir selecionados')
                        ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false)
                        ->modalSubmitActionLabel('Excluir')
                        ->requiresConfirmation()
                        ->modalDescription('Deseja excluir os clientes selecionados?'),
                ]),
            ])
            ->defaultSort('nome');
    }
}
