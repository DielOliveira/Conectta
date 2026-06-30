<?php

namespace App\Filament\Resources\Vendedores\Pages;

use App\Filament\Resources\Vendedores\VendedorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendedores extends ListRecords
{
    protected static string $resource = VendedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Adicionar'),
        ];
    }
}
