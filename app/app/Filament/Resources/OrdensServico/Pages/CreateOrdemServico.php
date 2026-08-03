<?php

namespace App\Filament\Resources\OrdensServico\Pages;

use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use App\Models\OrdemServico;
use App\Services\OrdemServico\OrdemServicoService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrdemServico extends CreateRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var OrdemServico $ordem */
        $ordem = app(OrdemServicoService::class)->criar($data, auth()->user())['ordem'];

        return $ordem;
    }
}
