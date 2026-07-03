<?php

namespace App\Filament\Resources\CobrancaMensagemModelos\Pages;

use App\Filament\Resources\CobrancaMensagemModelos\CobrancaMensagemModeloResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCobrancaMensagemModelo extends EditRecord
{
    protected static string $resource = CobrancaMensagemModeloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Excluir')
                ->visible(false),
        ];
    }
}
