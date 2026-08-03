<?php

namespace App\Filament\Resources\OrdensServico\Pages;

use App\Enums\OrdemServicoStatus;
use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use App\Models\OrdemServicoDisponibilidade;
use App\Models\Permission;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use App\Services\OrdemServico\OrdemServicoService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
            Action::make('agendar')->label($this->record->tecnico_id ? 'Reagendar' : 'Atribuir técnico')
                ->icon(Heroicon::OutlinedCalendarDays)->color('primary')
                ->visible($podeEscrever && in_array($this->record->status, [OrdemServicoStatus::ABERTA, OrdemServicoStatus::ENVIADA, OrdemServicoStatus::ACEITA], true))
                ->schema([
                    Select::make('disponibilidade_id')->label('Disponibilidade')->options(fn () => OrdemServicoDisponibilidade::query()->with('tecnico')->where('data', '>=', today())->orderBy('data')->get()->mapWithKeys(fn ($d) => [$d->id => $d->tecnico->nome.' — '.$d->data->format('d/m/Y').' '.substr($d->hora_inicio, 0, 5).' às '.substr($d->hora_fim, 0, 5)])->all())->searchable()->live()->required(),
                    Select::make('agendado_em')->label('Bloco livre')->options(function (Get $get): array {
                        $disponibilidade = OrdemServicoDisponibilidade::query()->find($get('disponibilidade_id'));
                        if (! $disponibilidade) {
                            return [];
                        }

                        return app(OrdemServicoAgendaService::class)->blocos($disponibilidade, $this->record->id)->mapWithKeys(fn (CarbonImmutable $b) => [$b->format('Y-m-d H:i:s') => $b->format('d/m/Y H:i')])->all();
                    })->required(),
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
                    Toggle::make('check_funcionamento')->label('Funcionamento do equipamento')->required(),
                    Toggle::make('check_pos_chave')->label('Pós-chave')->required(),
                    Select::make('check_bloqueio')->label('Bloqueio do veículo')->options(['conferido' => 'Conferido', 'nao_se_aplica' => 'Não se aplica'])->required(),
                ])->requiresConfirmation()
                ->action(function (array $data): void {
                    if ($data !== []) {
                        $this->record->update($data);
                    }
                    app(OrdemServicoService::class)->finalizar($this->record, auth()->user());
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
            return $this->record->getAttributes();
        }
        if (! in_array($this->record->status, [OrdemServicoStatus::ABERTA, OrdemServicoStatus::ENVIADA, OrdemServicoStatus::ACEITA], true)) {
            foreach (['tipo', 'cliente_id', 'veiculo_id'] as $campo) {
                unset($data[$campo]);
            }
        }

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

    public function getTitle(): string
    {
        return $this->podeEditar() ? 'Editar '.$this->record->numero_formatado : 'Consultar '.$this->record->numero_formatado;
    }

    private function podeEditar(): bool
    {
        return (auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false) && ! $this->record->status->isFinal();
    }
}
