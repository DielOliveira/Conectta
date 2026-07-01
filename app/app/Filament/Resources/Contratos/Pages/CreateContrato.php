<?php

namespace App\Filament\Resources\Contratos\Pages;

use App\Filament\Resources\Contratos\ContratoResource;
use App\Models\StatusContrato;
use App\Services\Audit\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateContrato extends CreateRecord
{
    protected static string $resource = ContratoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status_contrato_id'] = StatusContrato::query()->where('label', 'Nao Enviado')->value('id')
            ?: $data['status_contrato_id'];

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditLogger::registrar(
            'contrato.criado',
            'Contrato criado.',
            $this->record,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'veiculo_id' => $this->record->veiculo_id,
                'tipo_contrato' => $this->record->tipo_contrato,
            ],
        );
    }
}
