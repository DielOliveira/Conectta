<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use App\Models\StatusCliente;
use App\Services\Audit\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status_cliente_id'] = StatusCliente::query()
            ->where('label', 'Inativo')
            ->value('id');

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditLogger::registrar(
            'cliente.criado',
            'Cliente criado.',
            $this->record,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'status_cliente_id' => $this->record->status_cliente_id,
                'vendedor_id' => $this->record->vendedor_id,
                'cliente_origem_id' => $this->record->cliente_origem_id,
            ],
        );
    }
}
