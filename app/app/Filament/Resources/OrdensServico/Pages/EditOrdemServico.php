<?php

namespace App\Filament\Resources\OrdensServico\Pages;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use App\Models\OrdemServicoDisponibilidade;
use App\Models\Permission;
use App\Models\Veiculo;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use App\Services\OrdemServico\OrdemServicoService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class EditOrdemServico extends EditRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        $podeEscrever = auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false;

        return [
            Action::make('reenviarLink')->label('Reenviar link')->icon(Heroicon::OutlinedPaperAirplane)->color('gray')
                ->visible($podeEscrever && ! $this->record->status->isFinal() && filled($this->record->token_credencial))
                ->action(function (): void {
                    app(OrdemServicoService::class)->reenviarLink($this->record);
                    Notification::make()->title('Reenvio colocado na fila.')->success()->send();
                }),
            Action::make('cadastroCorrigido')->label('Cadastro corrigido')->icon(Heroicon::OutlinedArrowPath)->color('info')
                ->visible($podeEscrever && $this->record->status === OrdemServicoStatus::AGUARDANDO_CORRECAO_CADASTRAL)
                ->requiresConfirmation()->action(function (): void {
                    app(OrdemServicoService::class)->cadastroCorrigido($this->record, auth()->user());
                    Notification::make()->title('Cadastro validado; atendimento liberado.')->success()->send();
                    $this->refreshFormData(['status', 'rastreador_anterior_id', 'chip_anterior_id']);
                }),
            Action::make('agendar')->label('Atribuir técnico')
                ->icon(Heroicon::OutlinedCalendarDays)->color('primary')
                ->visible($podeEscrever && $this->record->status === OrdemServicoStatus::ABERTA && $this->record->tecnico_id === null)
                ->schema([
                    Select::make('disponibilidade_id')->label('Disponibilidade')->options(fn () => OrdemServicoDisponibilidade::query()->with('tecnico')->where('data', '>=', today())->orderBy('data')->get()->mapWithKeys(fn ($d) => [$d->id => $d->tecnico->nome.' — '.$d->data->format('d/m/Y').' '.substr($d->hora_inicio, 0, 5).' às '.substr($d->hora_fim, 0, 5)])->all())
                        ->searchable()
                        ->noOptionsMessage('Nenhuma disponibilidade futura cadastrada. Cadastre um intervalo em Agenda dos técnicos.')
                        ->noSearchResultsMessage('Nenhuma disponibilidade encontrada.')
                        ->live()
                        ->required(),
                    Select::make('agendado_em')->label('Bloco livre')->options(function (Get $get): array {
                        $disponibilidade = OrdemServicoDisponibilidade::query()->find($get('disponibilidade_id'));
                        if (! $disponibilidade) {
                            return [];
                        }

                        return app(OrdemServicoAgendaService::class)->blocos($disponibilidade, $this->record->id)->mapWithKeys(fn (CarbonImmutable $b) => [$b->format('Y-m-d H:i:s') => $b->format('d/m/Y H:i')])->all();
                    })
                        ->noOptionsMessage('Selecione uma disponibilidade que possua blocos livres.')
                        ->required(),
                ])->action(function (array $data): void {
                    $disponibilidade = OrdemServicoDisponibilidade::query()->findOrFail($data['disponibilidade_id']);
                    app(OrdemServicoService::class)->agendar($this->record, $disponibilidade, CarbonImmutable::parse($data['agendado_em']), auth()->user());
                    Notification::make()->title('OS atribuída e pronta para envio ao técnico.')->success()->send();
                    $this->refreshFormData(['status', 'tecnico_id', 'disponibilidade_id', 'agendado_em']);
                }),
            Action::make('pendente')->label('Marcar pendente')->icon(Heroicon::OutlinedArrowUturnLeft)->color('warning')
                ->visible($podeEscrever && $this->record->status === OrdemServicoStatus::EM_CONFERENCIA)
                ->schema([Textarea::make('motivo')->label('Motivo')->required()])
                ->action(function (array $data): void {
                    app(OrdemServicoService::class)->marcarPendente($this->record, $data['motivo'], auth()->user());
                    $this->refreshFormData(['status', 'motivo_pendencia']);
                }),
            Action::make('finalizar')->label('Aprovar e finalizar')->icon(Heroicon::OutlinedCheckCircle)->color('success')
                ->visible($podeEscrever && $this->record->status === OrdemServicoStatus::EM_CONFERENCIA)
                ->schema(fn (): array => $this->record->tipo->value === 'retirada' ? [] : [
                    ToggleButtons::make('check_funcionamento')->label('Funcionamento do equipamento')->options([1 => 'Conferido', 0 => 'Não conferido'])->inline()->grouped()->required(),
                    ToggleButtons::make('check_pos_chave')->label('Pós-chave')->options([1 => 'Conferido', 0 => 'Não conferido'])->inline()->grouped()->required(),
                    ToggleButtons::make('check_bloqueio')->label('Bloqueio do veículo')->options(['conferido' => 'Conferido', 'nao_se_aplica' => 'Não se aplica'])->inline()->grouped()->required(),
                ])->fillForm(fn (): array => $this->record->tipo->value === 'retirada' ? [] : [
                    'check_funcionamento' => null,
                    'check_pos_chave' => null,
                    'check_bloqueio' => null,
                ])->requiresConfirmation()
                ->action(function (array $data): void {
                    app(OrdemServicoService::class)->finalizar($this->record, auth()->user(), $data);
                    Notification::make()->title('Ordem finalizada.')->success()->send();
                    $this->redirect(OrdemServicoResource::getUrl());
                }),
            Action::make('cancelar')->label('Cancelar OS')->icon(Heroicon::OutlinedXCircle)->color('danger')
                ->visible($podeEscrever && ! $this->record->status->isFinal())->schema([Textarea::make('motivo')->label('Motivo')->required()])
                ->action(function (array $data): void {
                    app(OrdemServicoService::class)->cancelar($this->record, $data['motivo'], auth()->user());
                    $this->redirect(OrdemServicoResource::getUrl());
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! (auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false) || $this->record->status->isFinal()) {
            return [];
        }
        if (! in_array($this->record->status, [OrdemServicoStatus::ABERTA, OrdemServicoStatus::ENVIADA, OrdemServicoStatus::ACEITA], true)) {
            return ['observacoes' => $data['observacoes'] ?? $this->record->observacoes];
        }

        $veiculo = Veiculo::query()->with('rastreador.chip')->findOrFail($data['veiculo_id']);
        if ((int) $veiculo->cliente_id !== (int) $data['cliente_id']) {
            throw ValidationException::withMessages(['data.veiculo_id' => 'O veículo não pertence ao cliente selecionado.']);
        }
        if (($data['notificar_cliente'] ?? false) && strlen(preg_replace('/\D+/', '', (string) $veiculo->cliente?->telefone1) ?? '') < 10) {
            throw ValidationException::withMessages(['data.notificar_cliente' => 'Corrija o telefone do cliente antes de ativar as notificações.']);
        }
        if ($this->record->newQuery()->ativas()->where('veiculo_id', $veiculo->id)->whereKeyNot($this->record->id)->exists()) {
            throw ValidationException::withMessages(['data.veiculo_id' => 'Este veículo já possui outra ordem de serviço ativa.']);
        }
        $tipo = OrdemServicoTipo::from($data['tipo']);
        if ($tipo === OrdemServicoTipo::INSTALACAO && $veiculo->rastreador_id !== null) {
            throw ValidationException::withMessages(['data.tipo' => 'A instalação exige um veículo sem rastreador vinculado.']);
        }
        if ($tipo !== OrdemServicoTipo::INSTALACAO && ($veiculo->rastreador_id === null || $veiculo->rastreador?->chip_id === null)) {
            throw ValidationException::withMessages(['data.tipo' => 'Retirada e manutenção exigem rastreador e chip vinculados.']);
        }
        $data['rastreador_anterior_id'] = $veiculo->rastreador_id;
        $data['chip_anterior_id'] = $veiculo->rastreador?->chip_id;

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->disabled(! $this->podeEditar());
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->visible(fn (): bool => $this->podeEditar());
    }

    protected function afterSave(): void
    {
        $this->record->historicos()->create([
            'evento' => 'dados_atualizados',
            'status_anterior' => $this->record->status->value,
            'status_novo' => $this->record->status->value,
            'user_id' => auth()->id(),
        ]);
    }

    public function getTitle(): string
    {
        return $this->podeEditar() ? 'Editar '.$this->record->numero_formatado : 'Consultar '.$this->record->numero_formatado;
    }

    private function podeEditar(): bool
    {
        return (auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false) && ! $this->record->status->isFinal();
    }
}
