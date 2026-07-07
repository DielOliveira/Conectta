<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Filament\Resources\Usuarios\UsuarioResource;
use App\Services\Audit\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateUsuario extends CreateRecord
{
    protected static string $resource = UsuarioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! (auth()->user()?->isAdmin() ?? false)) {
            $data['is_admin'] = false;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->record->loadMissing('permissions');

        AuditLogger::registrar(
            'usuario.criado',
            'Usuario criado.',
            $this->record,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'permissions' => $this->record->permissions->pluck('nome')->values()->all(),
            ],
        );
    }
}
