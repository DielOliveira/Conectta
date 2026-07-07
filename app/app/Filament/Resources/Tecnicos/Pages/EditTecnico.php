<?php

namespace App\Filament\Resources\Tecnicos\Pages;

use App\Filament\Resources\Tecnicos\TecnicoResource;
use App\Services\Audit\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTecnico extends EditRecord
{
    protected static string $resource = TecnicoResource::class;

    protected array $tecnicoAntes = [];

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

    protected function beforeSave(): void
    {
        $this->tecnicoAntes = AuditLogger::snapshot($this->record);
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        AuditLogger::registrar(
            'tecnico.editado',
            'Tecnico editado.',
            $this->record,
            antes: $this->tecnicoAntes,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'is_ativo' => $this->record->is_ativo,
            ],
        );
    }
}
