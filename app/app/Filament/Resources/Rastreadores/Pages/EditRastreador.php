<?php

namespace App\Filament\Resources\Rastreadores\Pages;

use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Models\Permission;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRastreador extends EditRecord
{
    protected static string $resource = RastreadorResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Excluir')
                ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false),
        ];
    }
}
