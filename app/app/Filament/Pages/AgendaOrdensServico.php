<?php

namespace App\Filament\Pages;

use App\Enums\OrdemServicoStatus;
use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use App\Models\OrdemServicoDisponibilidade;
use App\Models\Permission;
use App\Models\Tecnico;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class AgendaOrdensServico extends Page
{
    protected static ?string $slug = 'agenda-ordens-servico';

    protected static string|UnitEnum|null $navigationGroup = 'Ordens de Serviço';

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendário';

    protected static ?string $title = 'Agenda de Ordens de Serviço';

    protected string $view = 'filament.pages.agenda-ordens-servico';

    protected Width|string|null $maxWidth = Width::Full;

    public string $data = '';

    public string $modo = 'dia';

    public ?int $tecnicoId = null;

    public function mount(): void
    {
        $this->data = today()->toDateString();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::OS_LEITURA) ?? false;
    }

    public function anterior(): void
    {
        $this->data = CarbonImmutable::parse($this->data)->subDays($this->modo === 'semana' ? 7 : 1)->toDateString();
    }

    public function proximo(): void
    {
        $this->data = CarbonImmutable::parse($this->data)->addDays($this->modo === 'semana' ? 7 : 1)->toDateString();
    }

    public function hoje(): void
    {
        $this->data = today()->toDateString();
    }

    public function tecnicos(): Collection
    {
        return Tecnico::query()->where('is_ativo', true)->orderBy('nome')->get();
    }

    public function dias(): Collection
    {
        $data = CarbonImmutable::parse($this->data);
        $inicio = $this->modo === 'semana' ? $data->startOfWeek() : $data;

        return collect(range(0, $this->modo === 'semana' ? 6 : 0))->map(fn (int $i) => $inicio->addDays($i));
    }

    public function agenda(): Collection
    {
        $dias = $this->dias();

        return OrdemServicoDisponibilidade::query()->with(['tecnico', 'ordens.cliente', 'ordens.veiculo'])
            ->whereBetween('data', [$dias->first()->toDateString(), $dias->last()->toDateString()])
            ->when($this->tecnicoId, fn ($q) => $q->where('tecnico_id', $this->tecnicoId))->orderBy('data')->orderBy('hora_inicio')->get()
            ->flatMap(function (OrdemServicoDisponibilidade $disponibilidade): Collection {
                $ocupados = $disponibilidade->ordens->reject(fn ($os) => in_array($os->status, [OrdemServicoStatus::ABERTA, OrdemServicoStatus::CANCELADA, OrdemServicoStatus::FINALIZADA], true))->keyBy(fn ($os) => $os->agendado_em?->format('Y-m-d H:i:s'));
                $inicio = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_inicio);
                $fim = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_fim);
                $blocos = collect();
                while ($inicio->addMinutes(40)->lessThanOrEqualTo($fim)) {
                    $blocos->push(['horario' => $inicio, 'disponibilidade' => $disponibilidade, 'ordem' => $ocupados->get($inicio->format('Y-m-d H:i:s'))]);
                    $inicio = $inicio->addMinutes(40);
                }

                return $blocos;
            });
    }

    public function urlOrdem(int $id): string
    {
        return OrdemServicoResource::getUrl('edit', ['record' => $id]);
    }
}
