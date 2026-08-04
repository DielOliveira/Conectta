<?php

namespace App\Filament\Resources\Disponibilidades\Pages;

use App\Filament\Resources\Disponibilidades\DisponibilidadeResource;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateDisponibilidade extends CreateRecord
{
    protected static string $resource = DisponibilidadeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(OrdemServicoAgendaService::class)->criarDisponibilidade($data['tecnico_id'], $data['data'], $data['hora_inicio'], $data['hora_fim']);
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
