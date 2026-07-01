<?php

namespace App\Filament\Resources\Contratos\Tables;

use App\Filament\Resources\Contratos\ContratoResource;
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
use Filament\Support\Icons\Heroicon;
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
                    ->icon(Heroicon::PaperAirplane)
                    ->visible(fn (Contrato $record): bool => (auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false)
                        && $record->statusContrato?->label === 'Nao Enviado')
                    ->modalSubmitActionLabel('Enviar')
                    ->requiresConfirmation()
                    ->modalDescription('Enviar este contrato para assinatura pela ZapSign?')
                    ->action(fn (Contrato $record): mixed => self::enviarContrato($record)),
                Action::make('documento')
                    ->label('Documento')
                    ->icon(Heroicon::DocumentText)
                    ->color(fn (Contrato $record): string => $record->statusContrato?->label === 'Assinado' ? 'success' : 'gray')
                    ->visible(fn (Contrato $record): bool => filled($record->doc_token))
                    ->url(fn (Contrato $record): string => route('contratos.documento', $record))
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (Contrato $record): bool => ContratoResource::canEdit($record)),
                DeleteAction::make()
                    ->label('Excluir')
                    ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false)
                    ->modalSubmitActionLabel('Excluir')
                    ->requiresConfirmation()
                    ->modalDescription('Deseja excluir este contrato?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Excluir selecionados')
                        ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false)
                        ->modalSubmitActionLabel('Excluir')
                        ->requiresConfirmation()
                        ->modalDescription('Deseja excluir os contratos selecionados?'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    private static function enviarContrato(Contrato $record): mixed
    {
        $record->loadMissing(['veiculo', 'tipoContrato', 'statusContrato']);

        if ($record->statusContrato?->label !== 'Nao Enviado') {
            Notification::make()
                ->title('Contrato nao pode ser enviado')
                ->body('Apenas contratos com status Nao Enviado podem ser enviados.')
                ->warning()
                ->send();

            return null;
        }

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
