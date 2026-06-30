<?php

namespace App\Filament\Resources\Vendedores\Pages;

use App\Filament\Resources\Vendedores\VendedorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendedor extends EditRecord
{
    protected static string $resource = VendedorResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Excluir'),
        ];
    }
}
