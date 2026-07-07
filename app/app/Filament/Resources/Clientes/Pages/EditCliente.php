<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use App\Models\Permission;
use App\Services\Audit\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected array $clienteAntes = [];

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->disabled(! $this->podeEditar());
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->visible(fn (): bool => $this->podeEditar());
    }

    public function getTitle(): string
    {
        return $this->podeEditar() ? 'Editar Cliente' : 'Ver Cliente';
    }

    protected function beforeSave(): void
    {
        if (! $this->podeEditar()) {
            Notification::make()
                ->title('Voce nao tem permissao para alterar clientes.')
                ->danger()
                ->send();

            $this->halt();
        }

        $this->clienteAntes = AuditLogger::snapshot($this->record);
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        AuditLogger::registrar(
            'cliente.editado',
            'Cliente editado.',
            $this->record,
            antes: $this->clienteAntes,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'status_cliente_id' => $this->record->status_cliente_id,
                'vendedor_id' => $this->record->vendedor_id,
                'cliente_origem_id' => $this->record->cliente_origem_id,
            ],
        );
    }

    private function podeEditar(): bool
    {
        return auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false;
    }
}
