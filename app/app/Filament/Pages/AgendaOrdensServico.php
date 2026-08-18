<?php

namespace App\Filament\Pages;

use App\Enums\OrdemServicoStatus;
use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use App\Models\OrdemServico;
use App\Models\OrdemServicoDisponibilidade;
use App\Models\Permission;
use App\Models\Tecnico;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use App\Services\OrdemServico\OrdemServicoService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
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

    public function podeAtribuir(): bool
    {
        return auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false;
    }

    public function tecnicoPodeReceberOs(Tecnico $tecnico): bool
    {
        return strlen(preg_replace('/\D+/', '', (string) $tecnico->telefone) ?? '') >= 10;
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
        $inicio = $this->modo === 'semana' ? $data->startOfWeek(CarbonInterface::MONDAY) : $data;

        return collect(range(0, $this->modo === 'semana' ? 6 : 0))->map(fn (int $i) => $inicio->addDays($i));
    }

    public function agenda(): Collection
    {
        $dias = $this->dias();

        return OrdemServicoDisponibilidade::query()->with(['tecnico', 'ordens.cliente', 'ordens.veiculo'])
            ->whereDate('data', '>=', $dias->first()->toDateString())
            ->whereDate('data', '<=', $dias->last()->toDateString())
            ->when($this->tecnicoId, fn ($q) => $q->where('tecnico_id', $this->tecnicoId))->orderBy('data')->orderBy('hora_inicio')->get()
            ->flatMap(function (OrdemServicoDisponibilidade $disponibilidade): Collection {
                if ($disponibilidade->isBloqueio()) {
                    $inicioBloqueio = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_inicio);
                    $fimBloqueio = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_fim);
                    $blocosBloqueados = collect();
                    while ($inicioBloqueio->addMinutes(OrdemServicoAgendaService::DURACAO_MINUTOS)->lessThanOrEqualTo($fimBloqueio)) {
                        $blocosBloqueados->push(['horario' => $inicioBloqueio, 'disponibilidade' => $disponibilidade, 'ordem' => null, 'bloqueio' => true]);
                        $inicioBloqueio = $inicioBloqueio->addMinutes(OrdemServicoAgendaService::DURACAO_MINUTOS);
                    }

                    return $blocosBloqueados;
                }

                $ocupados = $disponibilidade->ordens->reject(fn ($os) => in_array($os->status, [OrdemServicoStatus::ABERTA, OrdemServicoStatus::CANCELADA, OrdemServicoStatus::FINALIZADA], true))->keyBy(fn ($os) => $os->agendado_em?->format('Y-m-d H:i:s'));
                $livres = app(OrdemServicoAgendaService::class)->blocos($disponibilidade)
                    ->keyBy(fn (CarbonImmutable $horario): string => $horario->format('Y-m-d H:i:s'));
                $inicio = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_inicio);
                $fim = CarbonImmutable::parse($disponibilidade->data->format('Y-m-d').' '.$disponibilidade->hora_fim);
                $blocos = collect();
                while ($inicio->addMinutes(OrdemServicoAgendaService::DURACAO_MINUTOS)->lessThanOrEqualTo($fim)) {
                    $chave = $inicio->format('Y-m-d H:i:s');
                    if ($ocupados->has($chave) || $livres->has($chave)) {
                        $blocos->push(['horario' => $inicio, 'disponibilidade' => $disponibilidade, 'ordem' => $ocupados->get($chave), 'bloqueio' => false]);
                    }
                    $inicio = $inicio->addMinutes(OrdemServicoAgendaService::DURACAO_MINUTOS);
                }

                return $blocos;
            });
    }

    public function horariosDia(Collection $agenda): Collection
    {
        if ($agenda->isEmpty()) {
            return collect();
        }

        $horarios = $agenda->pluck('horario')->sortBy(fn (CarbonImmutable $horario) => $horario->timestamp)->values();
        $atual = $horarios->first();
        $ultimo = $horarios->last();
        $grade = collect();

        while ($atual->lessThanOrEqualTo($ultimo)) {
            $grade->push($atual);
            $atual = $atual->addMinutes(OrdemServicoAgendaService::DURACAO_MINUTOS);
        }

        return $grade;
    }

    public function atribuirAction(): Action
    {
        return Action::make('atribuir')
            ->modalHeading('Agendar ordem de serviço')
            ->modalDescription(fn (array $arguments): string => 'Horário: '.CarbonImmutable::parse($arguments['horario'])->format('d/m/Y H:i'))
            ->modalSubmitActionLabel('Agendar e enviar')
            ->fillForm(fn (array $arguments): array => [
                'horario' => $arguments['horario'],
                'ordem_servico_id' => null,
                'tecnico_id' => null,
            ])
            ->schema([
                Hidden::make('horario'),
                Select::make('ordem_servico_id')
                    ->label('Ordem de serviço')
                    ->options(fn (): array => OrdemServico::query()
                        ->with(['cliente', 'veiculo'])
                        ->where('status', OrdemServicoStatus::ABERTA->value)
                        ->whereNull('tecnico_id')
                        ->orderBy('numero')
                        ->get()
                        ->mapWithKeys(fn (OrdemServico $ordem): array => [
                            $ordem->id => $ordem->numero_formatado.' — '.$ordem->nome_atendimento.' — '.($ordem->veiculo->placa ?: 'Sem placa'),
                        ])->all())
                    ->searchable()
                    ->preload()
                    ->noOptionsMessage('Não existem ordens abertas aguardando atribuição.')
                    ->required(),
                Select::make('tecnico_id')
                    ->label('Técnico livre')
                    ->options(fn (Get $get): array => filled($get('horario')) ? $this->disponibilidadesLivres(CarbonImmutable::parse($get('horario')))
                        ->mapWithKeys(fn (OrdemServicoDisponibilidade $disponibilidade): array => [
                            $disponibilidade->tecnico_id => $disponibilidade->tecnico->nome,
                        ])->all() : [])
                    ->noOptionsMessage('O horário não possui mais técnicos livres.')
                    ->required(),
            ])
            ->action(function (array $data, array $arguments, Action $action): void {
                abort_unless(auth()->user()?->hasPermission(Permission::OS_ESCRITA), 403);

                try {
                    $horario = CarbonImmutable::parse($arguments['horario']);
                    $ordem = OrdemServico::query()
                        ->where('status', OrdemServicoStatus::ABERTA->value)
                        ->whereNull('tecnico_id')
                        ->findOrFail((int) $data['ordem_servico_id']);
                    $disponibilidade = $this->disponibilidadesLivres($horario)
                        ->firstWhere('tecnico_id', (int) $data['tecnico_id']);

                    if (! $disponibilidade) {
                        throw ValidationException::withMessages(['tecnico_id' => 'Este técnico não está mais livre no horário selecionado.']);
                    }

                    app(OrdemServicoService::class)->agendar($ordem, $disponibilidade, $horario, auth()->user());
                } catch (ValidationException $exception) {
                    Notification::make()->title('Não foi possível agendar a OS.')->body(collect($exception->errors())->flatten()->first())->danger()->send();
                    $action->halt();

                    return;
                }
                Notification::make()->title('OS agendada e enviada ao técnico.')->success()->send();
            });
    }

    public function agendamentoAction(): Action
    {
        return Action::make('agendamento')
            ->modalHeading(fn (array $arguments): string => OrdemServico::query()->findOrFail((int) $arguments['ordem'])->numero_formatado)
            ->modalDescription('Escolha o que deseja fazer com este agendamento.')
            ->color('danger')
            ->modalSubmitAction(fn (Action $action, array $arguments): Action|false => $this->podeCancelarAgendamento((int) $arguments['ordem'])
                ? $action->label('Cancelar agendamento')
                : false)
            ->extraModalFooterActions(fn (array $arguments): array => [
                Action::make('verOrdem')
                    ->label('Ver ordem de serviço')
                    ->color('gray')
                    ->url($this->urlOrdem((int) $arguments['ordem'])),
            ])
            ->action(function (array $arguments): void {
                abort_unless($this->podeCancelarAgendamento((int) $arguments['ordem']), 403);
                $ordem = OrdemServico::query()->findOrFail((int) $arguments['ordem']);
                app(OrdemServicoService::class)->cancelarAgendamento($ordem, auth()->user());
                Notification::make()->title('Agendamento cancelado; o horário foi liberado.')->success()->send();
            });
    }

    private function podeCancelarAgendamento(int $ordemId): bool
    {
        return $this->podeAtribuir() && OrdemServico::query()
            ->whereKey($ordemId)
            ->whereIn('status', [OrdemServicoStatus::ENVIADA->value, OrdemServicoStatus::ACEITA->value])
            ->exists();
    }

    private function disponibilidadesLivres(CarbonImmutable $horario): Collection
    {
        if ($horario->isPast()) {
            return collect();
        }

        return OrdemServicoDisponibilidade::query()
            ->with('tecnico')
            ->where('tipo', OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE)
            ->whereDate('data', $horario->toDateString())
            ->where('hora_inicio', '<=', $horario->format('H:i:s'))
            ->where('hora_fim', '>=', $horario->addMinutes(OrdemServicoAgendaService::DURACAO_MINUTOS)->format('H:i:s'))
            ->when($this->tecnicoId, fn ($query) => $query->where('tecnico_id', $this->tecnicoId))
            ->get()
            ->filter(fn (OrdemServicoDisponibilidade $disponibilidade): bool => $this->tecnicoPodeReceberOs($disponibilidade->tecnico))
            ->filter(fn (OrdemServicoDisponibilidade $disponibilidade): bool => app(OrdemServicoAgendaService::class)
                ->blocos($disponibilidade)
                ->contains(fn (CarbonImmutable $bloco): bool => $bloco->equalTo($horario)))
            ->unique('tecnico_id')
            ->values();
    }

    public function urlOrdem(int $id): string
    {
        return OrdemServicoResource::getUrl('edit', ['record' => $id]);
    }
}
