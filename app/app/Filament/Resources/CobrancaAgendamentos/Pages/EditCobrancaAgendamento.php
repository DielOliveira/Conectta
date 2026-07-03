<?php

namespace App\Filament\Resources\CobrancaAgendamentos\Pages;

use App\Filament\Resources\CobrancaAgendamentos\CobrancaAgendamentoResource;
use App\Services\Cobranca\CobrancaAgendamentoService;
use Filament\Resources\Pages\EditRecord;

class EditCobrancaAgendamento extends EditRecord
{
    protected static string $resource = CobrancaAgendamentoResource::class;

    protected function afterSave(): void
    {
        app(CobrancaAgendamentoService::class)->recalcularProxima($this->record);
    }
}
