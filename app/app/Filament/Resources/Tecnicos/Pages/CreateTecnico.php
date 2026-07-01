<?php

namespace App\Filament\Resources\Tecnicos\Pages;

use App\Filament\Resources\Tecnicos\TecnicoResource;
use App\Services\Audit\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateTecnico extends CreateRecord
{
    protected static string $resource = TecnicoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        AuditLogger::registrar(
            'tecnico.criado',
            'Tecnico criado.',
            $this->record,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'is_ativo' => $this->record->is_ativo,
            ],
        );
    }
}
