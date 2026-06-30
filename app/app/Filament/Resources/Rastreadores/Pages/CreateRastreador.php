<?php

namespace App\Filament\Resources\Rastreadores\Pages;

use App\Filament\Resources\Rastreadores\RastreadorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRastreador extends CreateRecord
{
    protected static string $resource = RastreadorResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function fillForm(): void
    {
        $this->form->fill([
            'cliente_id' => request()->integer('cliente_id') ?: null,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (request()->filled('cliente_id')) {
            $data['cliente_id'] = request()->integer('cliente_id');
        }

        return $data;
    }
}
