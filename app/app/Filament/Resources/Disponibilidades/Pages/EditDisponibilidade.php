<?php

namespace App\Filament\Resources\Disponibilidades\Pages;

use App\Filament\Resources\Disponibilidades\DisponibilidadeResource;
use App\Models\OrdemServicoDisponibilidade;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDisponibilidade extends EditRecord
{
    protected static string $resource = DisponibilidadeResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var OrdemServicoDisponibilidade $record */
        return app(OrdemServicoAgendaService::class)->atualizarDisponibilidade(
            $record,
            (int) $data['tecnico_id'],
            $data['data'],
            $data['hora_inicio'],
            $data['hora_fim'],
        );
    }
}
