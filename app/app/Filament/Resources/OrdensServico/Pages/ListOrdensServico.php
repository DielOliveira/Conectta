<?php

namespace App\Filament\Resources\OrdensServico\Pages;

use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrdensServico extends ListRecords
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nova ordem de serviço')];
    }
}
