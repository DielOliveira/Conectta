<?php

namespace App\Filament\Resources\Disponibilidades\Pages;

use App\Filament\Resources\Disponibilidades\DisponibilidadeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDisponibilidades extends ListRecords
{
    protected static string $resource = DisponibilidadeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Novo intervalo')];
    }
}
