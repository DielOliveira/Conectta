<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Filament\Resources\Usuarios\UsuarioResource;
use App\Services\Audit\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUsuario extends EditRecord
{
    protected static string $resource = UsuarioResource::class;

    protected array $usuarioAntes = [];

    protected array $permissoesAntes = [];

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

    protected function beforeValidate(): void
    {
        if ((auth()->user()?->isAdmin() ?? false) || ! (bool) $this->record->is_admin) {
            return;
        }

        Notification::make()
            ->title('Coordenador nao pode alterar usuarios administradores.')
            ->danger()
            ->send();

        $this->halt();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! (auth()->user()?->isAdmin() ?? false)) {
            $data['is_admin'] = false;
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->record->loadMissing('permissions');
        $this->usuarioAntes = AuditLogger::snapshot($this->record);
        $this->permissoesAntes = $this->record->permissions->pluck('nome')->values()->all();
    }

    protected function afterSave(): void
    {
        $this->record->refresh()->load('permissions');

        AuditLogger::registrar(
            'usuario.editado',
            'Usuario editado.',
            $this->record,
            antes: $this->usuarioAntes,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'permissions_antes' => $this->permissoesAntes,
                'permissions_depois' => $this->record->permissions->pluck('nome')->values()->all(),
            ],
        );
    }
}
