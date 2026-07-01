<?php

namespace App\Filament\Resources\Contratos\Pages;

use App\Filament\Resources\Contratos\ContratoResource;
use App\Models\Permission;
use App\Services\Audit\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContrato extends EditRecord
{
    protected static string $resource = ContratoResource::class;

    protected array $contratoAntes = [];

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

    protected function beforeSave(): void
    {
        $this->contratoAntes = AuditLogger::snapshot($this->record);
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        AuditLogger::registrar(
            'contrato.editado',
            'Contrato editado.',
            $this->record,
            antes: $this->contratoAntes,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'veiculo_id' => $this->record->veiculo_id,
                'tipo_contrato' => $this->record->tipo_contrato,
            ],
        );
    }
}
