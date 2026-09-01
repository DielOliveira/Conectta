<?php

namespace App\Filament\Resources\Rastreadores\Pages;

use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Filament\Resources\Rastreadores\Schemas\RastreadorForm;
use App\Models\Permission;
use App\Services\Audit\AuditLogger;
use App\Services\Veiculo\VeiculoCancelamentoService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
                ->label('Cancelar Rastreador')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (): bool => $this->record->isAtivo()
                    && (auth()->user()?->hasPermission(Permission::CADASTRO_EXCLUSAO) ?? false))
                ->modalHeading('Cancelar veículo sem retirada')
                ->modalDescription("O veículo será cancelado e o rastreador e o chip serão enviados ao técnico 'Lixo'.")
                ->modalSubmitActionLabel('Cancelar veículo')
                ->schema([
                    DatePicker::make('data_retirada')
                        ->label('Data de retirada')
                        ->default(today())
                        ->maxDate(today())
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->required(),
                    Textarea::make('motivo')
                        ->label('Motivo do cancelamento')
                        ->required()
                        ->maxLength(5000)
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    app(VeiculoCancelamentoService::class)->cancelarSemRetirada(
                        $this->record,
                        $data['motivo'],
                        $data['data_retirada'],
                        auth()->user(),
                    );
                    Notification::make()->title('Veículo cancelado sem retirada.')->success()->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
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
