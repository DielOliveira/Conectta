<?php

namespace App\Filament\Resources\Contratos\Pages;

use App\Filament\Resources\Contratos\ContratoResource;
use App\Models\Permission;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContrato extends EditRecord
{
    protected static string $resource = ContratoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('veiculo');
        $data['cliente_id'] = $this->record->veiculo?->cliente_id;

        return $data;
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
