<?php

namespace App\Filament\Resources\Vendedores\Pages;

use App\Filament\Resources\Vendedores\VendedorResource;
use App\Services\Audit\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendedor extends EditRecord
{
    protected static string $resource = VendedorResource::class;

    protected array $vendedorAntes = [];

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Excluir')
                ->visible(fn (): bool => static::getResource()::canDelete($this->record)),
        ];
    }

    protected function beforeSave(): void
    {
        $this->vendedorAntes = AuditLogger::snapshot($this->record);
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        AuditLogger::registrar(
            'vendedor.editado',
            'Vendedor editado.',
            $this->record,
            antes: $this->vendedorAntes,
            depois: AuditLogger::snapshot($this->record),
        );
    }
}
