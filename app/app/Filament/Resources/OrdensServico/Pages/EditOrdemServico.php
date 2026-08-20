<?php

namespace App\Filament\Resources\OrdensServico\Pages;

use App\Enums\OrdemServicoStatus;
use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use App\Models\Permission;
use App\Services\OrdemServico\OrdemServicoService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
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
                    ToggleButtons::make('check_funcionamento')->label('Funcionamento do equipamento')->options([1 => 'Conferido', 0 => 'Não conferido'])->inline()->grouped()->required()->rules(['accepted'])
                        ->validationMessages(['accepted' => 'Confirme o funcionamento do equipamento para finalizar.']),
                    ToggleButtons::make('check_pos_chave')->label('Pós-chave')->options([1 => 'Conferido', 0 => 'Não conferido'])->inline()->grouped()->required()->rules(['accepted'])
                        ->validationMessages(['accepted' => 'Confirme o pós-chave para finalizar.']),
                    ToggleButtons::make('check_bloqueio')->label('Bloqueio do veículo')->options(['conferido' => 'Conferido', 'nao_se_aplica' => 'Não se aplica'])->inline()->grouped()->required(),
                ])->modalWidth(Width::Medium)
                ->modalSubmitActionLabel('Aprovar e finalizar')
                ->action(function (array $data): void {
                    try {
                        app(OrdemServicoService::class)->finalizar($this->record, auth()->user(), $data);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->title('Não foi possível finalizar a ordem de serviço.')
                            ->body(collect($exception->errors())->flatten()->implode(' '))
                            ->danger()
                            ->persistent()
                            ->send();

                        throw $exception;
                    }

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
        unset(
            $data['tipo'],
            $data['cliente_id'],
            $data['veiculo_id'],
            $data['associado'],
            $data['status'],
            $data['tecnico_id'],
            $data['disponibilidade_id'],
            $data['agendado_em'],
        );

        if (! (auth()->user()?->hasPermission(Permission::OS_ESCRITA) ?? false) || $this->record->status->isFinal()) {
            return [];
        }
        if (! in_array($this->record->status, [OrdemServicoStatus::ABERTA, OrdemServicoStatus::ENVIADA, OrdemServicoStatus::ACEITA], true)) {
            return ['observacoes' => $data['observacoes'] ?? $this->record->observacoes];
        }

        if (($data['notificar_cliente'] ?? false) && strlen(preg_replace('/\D+/', '', (string) $this->record->telefone_atendimento) ?? '') < 10) {
            throw ValidationException::withMessages(['data.notificar_cliente' => 'Corrija o telefone do cliente antes de ativar as notificações.']);
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
