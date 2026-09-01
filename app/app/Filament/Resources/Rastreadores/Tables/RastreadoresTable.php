<?php

namespace App\Filament\Resources\Rastreadores\Tables;

use App\Filament\Resources\Rastreadores\Pages\ListRastreadores;
use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Models\Permission;
use App\Models\Veiculo;
use App\Services\Veiculo\VeiculoCancelamentoService;
use App\Services\Veiculo\VeiculoExclusaoService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RastreadoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'ct-selectable-table'], merge: true)
            ->recordAction(null)
            ->recordUrl(null)
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
                Action::make('ver')
                    ->label(fn (): string => auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ? 'Editar' : 'Ver')
                    ->icon(fn (): Heroicon => auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ? Heroicon::PencilSquare : Heroicon::OutlinedEye)
                    ->url(fn (Veiculo $record): string => RastreadorResource::getUrl('edit', ['record' => $record])),
                Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Veiculo $record): bool => $record->isAtivo()
                        && (auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false))
                    ->modalHeading('Cancelar veículo sem retirada')
                    ->modalDescription('O veículo será cancelado e o rastreador e o chip serão enviados ao técnico Lixo. Nenhuma retirada será registrada.')
                    ->modalSubmitActionLabel('Cancelar veículo')
                    ->schema([
                        Textarea::make('motivo')
                            ->label('Motivo do cancelamento')
                            ->required()
                            ->maxLength(5000)
                            ->rows(4),
                    ])
                    ->action(function (Veiculo $record, array $data): void {
                        app(VeiculoCancelamentoService::class)->cancelarSemRetirada($record, $data['motivo'], auth()->user());
                        Notification::make()->title('Veículo cancelado sem retirada.')->success()->send();
                    }),
                DeleteAction::make()
                    ->label('Excluir')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false)
                    ->using(fn (Veiculo $record): bool => self::excluirVeiculos([$record]))
                    ->modalSubmitActionLabel('Excluir')
                    ->requiresConfirmation()
                    ->modalDescription('Deseja excluir este veículo? As ordens de serviço em andamento serão canceladas, o histórico será preservado e os avisos de cancelamento seguirão as regras da OS.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false)
                        ->using(fn ($records): bool => self::excluirVeiculos($records))
                        ->label('Excluir selecionados')
                        ->modalSubmitActionLabel('Excluir')
                        ->requiresConfirmation()
                        ->modalDescription('Deseja excluir os veículos selecionados? As ordens de serviço em andamento serão canceladas, o histórico será preservado e os avisos de cancelamento seguirão as regras da OS.'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    private static function excluirVeiculos(iterable $veiculos): bool
    {
        app(VeiculoExclusaoService::class)->excluir($veiculos, auth()->user());

        return true;
    }
}
