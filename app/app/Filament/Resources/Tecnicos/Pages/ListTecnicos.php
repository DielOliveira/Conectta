<?php

namespace App\Filament\Resources\Tecnicos\Pages;

use App\Filament\Resources\Tecnicos\TecnicoResource;
use App\Models\Tecnico;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListTecnicos extends ListRecords
{
    protected static string $resource = TecnicoResource::class;

    public ?string $tecnicoStatusFiltro = null;

    public string $tecnicoPesquisa = '';

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'tecnico') && method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function limparFiltrosTecnicos(): void
    {
        $this->tecnicoStatusFiltro = null;
        $this->tecnicoPesquisa = '';

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function aplicarFiltrosTecnicos(Builder $query): Builder
    {
        $search = trim($this->tecnicoPesquisa);
        $digits = preg_replace('/\D+/', '', $search);

        return $query
            ->when($this->tecnicoStatusFiltro !== null && $this->tecnicoStatusFiltro !== '', function (Builder $query): Builder {
                return $query->where('is_ativo', $this->tecnicoStatusFiltro === '1');
            })
            ->when($search !== '', function (Builder $query) use ($search, $digits): Builder {
                return $query->where(function (Builder $query) use ($search, $digits): void {
                    $query
                        ->where('nome', 'like', '%' . $search . '%')
                        ->orWhere('telefone', 'like', '%' . $search . '%');

                    if ($digits !== '') {
                        $query
                            ->orWhere('cpf', 'like', '%' . $digits . '%')
                            ->orWhere('telefone', 'like', '%' . $digits . '%');
                    }
                });
            });
    }

    public function exportarCsv(): StreamedResponse
    {
        $query = $this->aplicarFiltrosTecnicos(Tecnico::query());

        $this->applySortingToTableQuery($query);

        $tecnicos = $query
            ->limit(10000)
            ->get();

        $fileName = 'tecnicos-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($tecnicos): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Nome', 'CPF', 'Telefone', 'Ativo'], ';');

            foreach ($tecnicos as $tecnico) {
                fputcsv($handle, [
                    $tecnico->nome,
                    $tecnico->cpf,
                    $tecnico->telefone,
                    $tecnico->is_ativo ? 'Sim' : 'Nao',
                ], ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar')
                ->label('Exportar')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->action('exportarCsv'),
            CreateAction::make()
                ->label('Adicionar')
                ->visible(fn (): bool => static::getResource()::podeManter()),
        ];
    }
}
