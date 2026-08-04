<?php

namespace App\Filament\Resources\OrdensServico\Pages;

use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use App\Models\OrdemServico;
use App\Services\OrdemServico\OrdemServicoService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateOrdemServico extends CreateRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            /** @var OrdemServico $ordem */
            $ordem = app(OrdemServicoService::class)->criar($data, auth()->user())['ordem'];
        } catch (ValidationException $exception) {
            $this->notificarErroValidacao($exception);

            throw ValidationException::withMessages(collect($exception->errors())
                ->mapWithKeys(fn (array $mensagens, string $campo): array => ["data.{$campo}" => $mensagens])
                ->all());
        }

        return $ordem;
    }

    protected function onValidationError(ValidationException $exception): void
    {
        $this->notificarErroValidacao($exception);
    }

    private function notificarErroValidacao(ValidationException $exception): void
    {
        Notification::make()
            ->title('Não foi possível salvar a ordem de serviço.')
            ->body((string) collect($exception->errors())->flatten()->first())
            ->danger()
            ->send();
    }
}
