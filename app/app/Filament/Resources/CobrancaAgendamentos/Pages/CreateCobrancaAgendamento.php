<?php

namespace App\Filament\Resources\CobrancaAgendamentos\Pages;

use App\Filament\Resources\CobrancaAgendamentos\CobrancaAgendamentoResource;
use App\Services\Cobranca\CobrancaAgendamentoService;
use Filament\Resources\Pages\CreateRecord;

class CreateCobrancaAgendamento extends CreateRecord
{
    protected static string $resource = CobrancaAgendamentoResource::class;

    protected function afterCreate(): void
    {
        app(CobrancaAgendamentoService::class)->recalcularProxima($this->record);
    }
}
