<?php

namespace App\Filament\Resources\Tecnicos\Pages;

use App\Filament\Resources\Tecnicos\TecnicoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTecnico extends CreateRecord
{
    protected static string $resource = TecnicoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
