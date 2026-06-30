<?php

namespace App\Filament\Resources\Tecnicos\Pages;

use App\Filament\Resources\Tecnicos\TecnicoResource;
use App\Models\Permission;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTecnico extends EditRecord
{
    protected static string $resource = TecnicoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Excluir')
                ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::ESTOQUE_ESCRITA) ?? false),
        ];
    }
}
