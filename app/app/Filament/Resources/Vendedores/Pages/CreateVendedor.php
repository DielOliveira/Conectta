<?php

namespace App\Filament\Resources\Vendedores\Pages;

use App\Filament\Resources\Vendedores\VendedorResource;
use App\Services\Audit\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateVendedor extends CreateRecord
{
    protected static string $resource = VendedorResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        AuditLogger::registrar(
            'vendedor.criado',
            'Vendedor criado.',
            $this->record,
            depois: AuditLogger::snapshot($this->record),
        );
    }
}
