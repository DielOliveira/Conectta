<?php

namespace App\Filament\Resources\Vendedores\Pages;

use App\Filament\Resources\Vendedores\VendedorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendedor extends CreateRecord
{
    protected static string $resource = VendedorResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
