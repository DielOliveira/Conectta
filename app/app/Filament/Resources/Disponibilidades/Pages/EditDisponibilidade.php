<?php

namespace App\Filament\Resources\Disponibilidades\Pages;

use App\Filament\Resources\Disponibilidades\DisponibilidadeResource;
use App\Models\OrdemServicoDisponibilidade;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditDisponibilidade extends EditRecord
{
    protected static string $resource = DisponibilidadeResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var OrdemServicoDisponibilidade $record */
        try {
            return app(OrdemServicoAgendaService::class)->atualizarDisponibilidade(
                $record,
                (int) $data['tecnico_id'],
                $data['data'],
                $data['hora_inicio'],
                $data['hora_fim'],
            );
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())
                ->mapWithKeys(fn (array $mensagens, string $campo): array => ["data.{$campo}" => $mensagens])
                ->all());
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
