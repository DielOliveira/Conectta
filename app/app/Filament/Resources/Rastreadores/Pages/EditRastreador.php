<?php

namespace App\Filament\Resources\Rastreadores\Pages;

use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Filament\Resources\Rastreadores\Schemas\RastreadorForm;
use App\Models\Permission;
use App\Models\Veiculo;
use App\Services\Audit\AuditLogger;
use App\Services\Veiculo\VeiculoCancelamentoService;
use App\Services\Veiculo\VeiculoExclusaoService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EditRastreador extends EditRecord
{
    protected static string $resource = RastreadorResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected array $rastreadorAntes = [];

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelar')
                ->label('Cancelar')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (): bool => $this->record->isAtivo()
                    && (auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false))
                ->modalHeading('Cancelar veículo sem retirada')
                ->modalDescription('O veículo será cancelado e o rastreador e o chip serão enviados ao técnico Lixo. Nenhuma retirada será registrada.')
                ->modalSubmitActionLabel('Cancelar veículo')
                ->schema([
                    Textarea::make('motivo')
                        ->label('Motivo do cancelamento')
                        ->required()
                        ->maxLength(5000)
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    app(VeiculoCancelamentoService::class)->cancelarSemRetirada($this->record, $data['motivo'], auth()->user());
                    Notification::make()->title('Veículo cancelado sem retirada.')->success()->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
            DeleteAction::make()
                ->label('Excluir')
                ->using(function (Veiculo $record): bool {
                    app(VeiculoExclusaoService::class)->excluir([$record], auth()->user());

                    return true;
                })
                ->modalDescription('Deseja excluir este veículo? As ordens de serviço em andamento serão canceladas, o histórico será preservado e os avisos de cancelamento seguirão as regras da OS.')
                ->visible(fn (): bool => auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->disabled(! $this->podeEditar());
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->submit(null)
            ->action('save')
            ->visible(fn (): bool => $this->podeEditar());
    }

    public function getTitle(): string
    {
        return $this->podeEditar() ? 'Editar Rastreador' : 'Ver Rastreador';
    }

    protected function beforeSave(): void
    {
        if (! $this->podeEditar()) {
            Notification::make()
                ->title('Voce nao tem permissao para alterar rastreadores.')
                ->danger()
                ->send();

            $this->halt();
        }

        $this->rastreadorAntes = AuditLogger::snapshot($this->record);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return RastreadorForm::removerCamposGerenciadosPelaOs($data);
    }

    protected function afterSave(): void
    {
        AuditLogger::registrar(
            'rastreador.editado',
            'Rastreador editado.',
            $this->record,
            antes: $this->rastreadorAntes,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'tecnico_instala_id' => $this->record->tecnico_instala_id,
                'tecnico_remocao_id' => $this->record->tecnico_remocao_id,
                'status_rastreador_id' => $this->record->status_rastreador_id,
            ],
        );
    }

    private function podeEditar(): bool
    {
        return auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false;
    }
}
