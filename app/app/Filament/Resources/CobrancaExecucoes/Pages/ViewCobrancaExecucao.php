<?php

namespace App\Filament\Resources\CobrancaExecucoes\Pages;

use App\Filament\Resources\CobrancaExecucoes\CobrancaExecucaoResource;
use App\Models\CobrancaEnvio;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ViewCobrancaExecucao extends ViewRecord
{
    protected static string $resource = CobrancaExecucaoResource::class;

    protected string $view = 'filament.resources.cobranca-execucoes.pages.view-cobranca-execucao';

    public function getTitle(): string
    {
        return 'Detalhes da cobranca automatica';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voltar')
                ->label('Voltar')
                ->url(CobrancaExecucaoResource::getUrl()),
        ];
    }

    public function envios(): EloquentCollection
    {
        return CobrancaEnvio::query()
            ->with(['cliente', 'invoice'])
            ->where('cobranca_execucao_id', $this->record->id)
            ->orderBy('id')
            ->get();
    }

    public function moeda(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return number_format((float) $valor, 2, ',', '.');
    }
}
