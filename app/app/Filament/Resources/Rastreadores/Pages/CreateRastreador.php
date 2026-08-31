<?php

namespace App\Filament\Resources\Rastreadores\Pages;

use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Filament\Resources\Rastreadores\Schemas\RastreadorForm;
use App\Services\Audit\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateRastreador extends CreateRecord
{
    protected static string $resource = RastreadorResource::class;

    protected ?bool $hasDatabaseTransactions = true;

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

        return RastreadorForm::removerCamposGerenciadosPelaOs($data);
    }

    protected function afterCreate(): void
    {
        AuditLogger::registrar(
            'rastreador.criado',
            'Rastreador criado.',
            $this->record,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'tecnico_instala_id' => $this->record->tecnico_instala_id,
                'tecnico_remocao_id' => $this->record->tecnico_remocao_id,
                'status_rastreador_id' => $this->record->status_rastreador_id,
            ],
        );
    }
}
