<?php

namespace App\Filament\Resources\Rastreadores\Pages;

use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Models\Chip;
use App\Models\Permission;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Veiculo;
use App\Services\Audit\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class EditRastreador extends EditRecord
{
    protected static string $resource = RastreadorResource::class;

    protected array $rastreadorAntes = [];

    protected ?int $chipIdSelecionado = null;

    public bool $transferenciaChipConfirmada = false;

    public ?string $transferenciaChipDescricao = null;

    public ?string $rastreadorIndisponivelDescricao = null;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Excluir')
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
        $this->chipIdSelecionado = filled($data['chip_id_form'] ?? null) ? (int) $data['chip_id_form'] : null;
        unset($data['chip_id_form']);

        if ($this->chipIdSelecionado !== null && blank($data['rastreador_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.rastreador_id' => 'Selecione um IMEI para vincular o chip.',
            ]);
        }

        $rastreadorId = filled($data['rastreador_id'] ?? null) ? (int) $data['rastreador_id'] : null;

        if ($this->rastreadorSelecionadoEstaEmOutroVeiculoAtivo($rastreadorId)) {
            $this->rastreadorIndisponivelDescricao = $this->descricaoRastreadorIndisponivel($rastreadorId);
            $this->mountAction('rastreadorIndisponivel');
            $this->halt();
        }

        if (! $this->transferenciaChipConfirmada && $this->chipSelecionadoEstaEmOutroRastreador($this->chipIdSelecionado, filled($data['rastreador_id'] ?? null) ? (int) $data['rastreador_id'] : null)) {
            $this->transferenciaChipDescricao = $this->descricaoConfirmacaoChip($this->chipIdSelecionado, (int) $data['rastreador_id']);
            $this->mountAction('confirmarTransferenciaChip');
            $this->halt();
        }

        return $data;
    }

    public function rastreadorIndisponivelAction(): Action
    {
        return Action::make('rastreadorIndisponivel')
            ->modalHeading('IMEI indisponivel')
            ->modalDescription(fn (): string => $this->rastreadorIndisponivelDescricao ?? 'Este IMEI esta ativo em outro veiculo e nao pode ser utilizado neste cadastro.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Entendi');
    }

    public function confirmarTransferenciaChipAction(): Action
    {
        return Action::make('confirmarTransferenciaChip')
            ->requiresConfirmation()
            ->modalHeading('Chip ja vinculado')
            ->modalDescription(fn (): string => $this->transferenciaChipDescricao ?? 'Este chip ja esta vinculado a outro IMEI. Deseja transferir o chip para o IMEI deste cadastro?')
            ->modalSubmitActionLabel('Sim, transferir chip')
            ->action(function (): void {
                $this->transferenciaChipConfirmada = true;
                $this->save();
                $this->transferenciaChipConfirmada = false;
            });
    }

    protected function afterSave(): void
    {
        $this->sincronizarChipRastreador();
        $this->record->refresh();

        AuditLogger::registrar(
            'rastreador.editado',
            'Rastreador editado.',
            $this->record,
            antes: $this->rastreadorAntes,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'tecnico_id' => $this->record->tecnico_id,
                'status_rastreador_id' => $this->record->status_rastreador_id,
                'is_estoque' => $this->record->is_estoque,
            ],
        );
    }

    private function podeEditar(): bool
    {
        return auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false;
    }

    private function sincronizarChipRastreador(): void
    {
        if ($this->record->rastreador_id === null) {
            return;
        }

        if ($this->chipIdSelecionado !== null) {
            Rastreador::query()
                ->where('chip_id', $this->chipIdSelecionado)
                ->where('id', '!=', $this->record->rastreador_id)
                ->update(['chip_id' => null]);
        }

        Rastreador::query()
            ->whereKey($this->record->rastreador_id)
            ->update(['chip_id' => $this->chipIdSelecionado]);

        $this->ativarChipSelecionado();
    }

    private function ativarChipSelecionado(): void
    {
        if ($this->chipIdSelecionado === null) {
            return;
        }

        $ativoId = StatusRastreador::query()
            ->where('label', 'Ativo')
            ->value('id');

        if ($ativoId === null) {
            return;
        }

        Chip::query()
            ->whereKey($this->chipIdSelecionado)
            ->update(['status_rastreador_id' => $ativoId]);
    }

    private function chipSelecionadoEstaEmOutroRastreador(?int $chipId = null, ?int $rastreadorId = null): bool
    {
        $chipId ??= $this->chipIdAtualDoFormulario();
        $rastreadorId ??= $this->rastreadorIdAtualDoFormulario();

        if ($chipId === null || $rastreadorId === null) {
            return false;
        }

        return Rastreador::query()
            ->where('chip_id', $chipId)
            ->where('id', '!=', $rastreadorId)
            ->exists();
    }

    private function rastreadorSelecionadoEstaEmOutroVeiculoAtivo(?int $rastreadorId): bool
    {
        if ($rastreadorId === null) {
            return false;
        }

        return $this->outrosVeiculosAtivosComRastreador($rastreadorId)->exists();
    }

    private function descricaoRastreadorIndisponivel(?int $rastreadorId): string
    {
        $veiculo = $rastreadorId === null
            ? null
            : $this->outrosVeiculosAtivosComRastreador($rastreadorId)
                ->with('cliente:id,nome')
                ->first();

        if ($veiculo === null) {
            return 'Este IMEI esta ativo em outro veiculo e nao pode ser utilizado neste cadastro. Cancele primeiro o vinculo anterior para tornar o rastreador disponivel.';
        }

        $identificacao = trim($veiculo->veiculo.' / '.$veiculo->placa, ' /');
        $cliente = $veiculo->cliente?->nome ?? 'cliente nao informado';

        return "O IMEI esta ativo no veiculo {$identificacao} (cadastro #{$veiculo->id}), do cliente {$cliente}, e nao pode ser utilizado neste cadastro. Cancele primeiro o vinculo anterior para tornar o rastreador disponivel.";
    }

    private function outrosVeiculosAtivosComRastreador(int $rastreadorId)
    {
        return Veiculo::query()
            ->whereKeyNot($this->record->getKey())
            ->where('rastreador_id', $rastreadorId)
            ->whereNull('data_exclusao')
            ->where('status_rastreador_id', Veiculo::statusId('Ativo'));
    }

    private function descricaoConfirmacaoChip(?int $chipId = null, ?int $rastreadorId = null): string
    {
        $imei = Rastreador::query()
            ->where('chip_id', $chipId ?? $this->chipIdAtualDoFormulario())
            ->where('id', '!=', $rastreadorId ?? $this->rastreadorIdAtualDoFormulario())
            ->value('imei');

        return $imei
            ? 'Este chip ja esta vinculado ao IMEI '.$imei.'. Deseja transferir o chip para o IMEI deste cadastro?'
            : 'Este chip ja esta vinculado a outro IMEI. Deseja transferir o chip para o IMEI deste cadastro?';
    }

    private function chipIdAtualDoFormulario(): ?int
    {
        $chipId = data_get($this->form->getRawState(), 'chip_id_form');

        return filled($chipId) ? (int) $chipId : null;
    }

    private function rastreadorIdAtualDoFormulario(): ?int
    {
        $rastreadorId = data_get($this->form->getRawState(), 'rastreador_id');

        return filled($rastreadorId) ? (int) $rastreadorId : null;
    }
}
