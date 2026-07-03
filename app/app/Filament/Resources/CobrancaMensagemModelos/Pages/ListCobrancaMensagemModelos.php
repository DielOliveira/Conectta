<?php

namespace App\Filament\Resources\CobrancaMensagemModelos\Pages;

use App\Filament\Resources\CobrancaMensagemModelos\CobrancaMensagemModeloResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCobrancaMensagemModelos extends ListRecords
{
    protected static string $resource = CobrancaMensagemModeloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Adicionar'),
        ];
    }
}
