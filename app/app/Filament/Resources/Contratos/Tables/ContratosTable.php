<?php

namespace App\Filament\Resources\Contratos\Tables;

use App\Filament\Resources\Contratos\Pages\ListContratos;
use App\Models\Contrato;
use App\Models\Permission;
use App\Models\StatusContrato;
use App\Services\ZapSign\ZapSignException;
use App\Services\ZapSign\ZapSignService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContratosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(view('filament.resources.contratos.table-toolbar-filters'))
            ->columns([
                TextColumn::make('veiculo.cliente.nome')
                    ->label('Cliente')
                    ->wrap(),
                TextColumn::make('veiculo.rastreador.imei')
                    ->label('Rastreador'),
                TextColumn::make('veiculo.veiculo')
                    ->label('Veiculo')
                    ->sortable(),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->sortable(),
                TextColumn::make('tipoContrato.label')
                    ->label('Tipo')
                    ->sortable(),
                TextColumn::make('statusContrato.label')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Assinado' => 'success',
                        'Enviado' => 'warning',
                        'Rejeitado', 'Cancelado', 'Deletado' => 'danger',
                        'Expirado' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('doc_token')
                    ->label('Token')
                    ->limit(18)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn (Builder $query, ListContratos $livewire): Builder => $livewire->aplicarFiltrosContratos($query))
            ->recordActions([
                Action::make('enviar')
                    ->label('Enviar')
                    ->visible(fn (Contrato $record): bool => (auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false)
                        && blank($record->doc_token))
                    ->modalSubmitActionLabel('Enviar')
                    ->requiresConfirmation()
                    ->modalDescription('Enviar este contrato para assinatura pela ZapSign?')
                    ->action(fn (Contrato $record): mixed => self::enviarContrato($record)),
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false),
                DeleteAction::make()
                    ->label('Excluir')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false)
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Excluir selecionados')
                        ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    private static function enviarContrato(Contrato $record): mixed
    {
        $record->loadMissing(['veiculo', 'tipoContrato']);
        $dados = is_array($record->dados) ? $record->dados : [];

        try {
            $response = app(ZapSignService::class)->criarDocumento($record->veiculo, $record->tipoContrato, $dados);
        } catch (ZapSignException $exception) {
            Notification::make()
                ->title('Nao foi possivel enviar o contrato')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return null;
        }

        $record->forceFill([
            'status_contrato_id' => StatusContrato::query()->where('label', 'Enviado')->value('id'),
            'doc_token' => data_get($response, 'token'),
        ])->save();

        Notification::make()
            ->title('Contrato enviado')
            ->body('Documento enviado para a ZapSign.')
            ->success()
            ->send();

        return null;
    }
}
