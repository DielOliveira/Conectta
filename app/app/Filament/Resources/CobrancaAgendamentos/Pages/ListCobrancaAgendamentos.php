<?php

namespace App\Filament\Resources\CobrancaAgendamentos\Pages;

use App\Filament\Resources\CobrancaAgendamentos\CobrancaAgendamentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCobrancaAgendamentos extends ListRecords
{
    protected static string $resource = CobrancaAgendamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo agendamento'),
        ];
    }
}
