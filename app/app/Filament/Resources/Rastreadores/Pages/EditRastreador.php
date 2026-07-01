<?php

namespace App\Filament\Resources\Rastreadores\Pages;

use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Models\Permission;
use App\Services\Audit\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRastreador extends EditRecord
{
    protected static string $resource = RastreadorResource::class;

    protected array $rastreadorAntes = [];

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

    protected function beforeSave(): void
    {
        $this->rastreadorAntes = AuditLogger::snapshot($this->record);
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        AuditLogger::registrar(
            'rastreador.editado',
            'Rastreador editado.',
            $this->record,
            antes: $this->rastreadorAntes,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'tecnico_id' => $this->record->tecnico_id,
                'status_rastreador_id' => $this->record->status_rastreador_id,
                'is_estoque' => $this->record->is_estoque,
            ],
        );
    }
}
