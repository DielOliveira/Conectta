<?php

namespace App\Filament\Resources\Rastreadores\Pages;

use App\Filament\Resources\Rastreadores\RastreadorResource;
use App\Models\Chip;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Veiculo;
use App\Services\Audit\AuditLogger;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateRastreador extends CreateRecord
{
    protected static string $resource = RastreadorResource::class;

    protected ?int $chipIdSelecionado = null;

    public bool $transferenciaChipConfirmada = false;

    public bool $criarOutroAposConfirmacao = false;

    public ?string $transferenciaChipDescricao = null;

    public bool $transferenciaRastreadorConfirmada = false;

    public ?string $transferenciaRastreadorDescricao = null;

    public bool $manterHistoricoRastreador = false;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function fillForm(): void
    {
        $this->form->fill([
            'cliente_id' => request()->integer('cliente_id') ?: null,
        ]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->submit(null)
            ->action(function (): void {
                $this->criarOutroAposConfirmacao = false;
                $this->create();
            });
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->action(function (): void {
                $this->criarOutroAposConfirmacao = true;
                $this->create(another: true);
            });
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (request()->filled('cliente_id')) {
            $data['cliente_id'] = request()->integer('cliente_id');
        }

        $this->chipIdSelecionado = filled($data['chip_id_form'] ?? null) ? (int) $data['chip_id_form'] : null;
        unset($data['chip_id_form']);

        if ($this->chipIdSelecionado !== null && blank($data['rastreador_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.rastreador_id' => 'Selecione um IMEI para vincular o chip.',
            ]);
        }

        $rastreadorId = filled($data['rastreador_id'] ?? null) ? (int) $data['rastreador_id'] : null;

        if (! $this->transferenciaRastreadorConfirmada && $this->rastreadorSelecionadoEstaEmOutroVeiculoAtivo($rastreadorId)) {
            $this->transferenciaRastreadorDescricao = $this->descricaoConfirmacaoRastreador($rastreadorId);
            $this->mountAction('confirmarTransferenciaRastreador');
            $this->halt();
        }

        if ($this->transferenciaRastreadorConfirmada) {
            if ($this->manterHistoricoRastreador) {
                $this->desativarVinculoAnteriorDoRastreador($rastreadorId);
            } else {
                $this->desvincularRastreadorDeOutrosVeiculosAtivos($rastreadorId);
            }
        }

        if (! $this->transferenciaChipConfirmada && $this->chipSelecionadoEstaEmOutroRastreador($this->chipIdSelecionado, filled($data['rastreador_id'] ?? null) ? (int) $data['rastreador_id'] : null)) {
            $this->transferenciaChipDescricao = $this->descricaoConfirmacaoChip($this->chipIdSelecionado, (int) $data['rastreador_id']);
            $this->mountAction('confirmarTransferenciaChip');
            $this->halt();
        }

        return $data;
    }

    public function confirmarTransferenciaRastreadorAction(): Action
    {
        return Action::make('confirmarTransferenciaRastreador')
            ->requiresConfirmation()
            ->modalHeading('IMEI ja vinculado')
            ->modalDescription(fn (): string => $this->transferenciaRastreadorDescricao ?? 'Este IMEI ja esta vinculado a outro veiculo ativo. Deseja transferi-lo para este veiculo?')
            ->modalSubmitActionLabel('Sim, transferir IMEI e chip')
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('manterHistorico', arguments: ['manterHistorico' => true])
                    ->label('Desativar vinculo antigo e manter historico')
                    ->color('warning'),
            ])
            ->action(function (array $arguments): void {
                $this->transferenciaRastreadorConfirmada = true;
                $this->manterHistoricoRastreador = (bool) ($arguments['manterHistorico'] ?? false);
                $this->create(another: $this->criarOutroAposConfirmacao);
                $this->transferenciaRastreadorConfirmada = false;
                $this->manterHistoricoRastreador = false;
                $this->criarOutroAposConfirmacao = false;
            });
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
                $this->create(another: $this->criarOutroAposConfirmacao);
                $this->transferenciaChipConfirmada = false;
                $this->criarOutroAposConfirmacao = false;
            });
    }

    protected function afterCreate(): void
    {
        $this->sincronizarChipRastreador();

        AuditLogger::registrar(
            'rastreador.criado',
            'Rastreador criado.',
            $this->record,
            depois: AuditLogger::snapshot($this->record),
            contexto: [
                'tecnico_id' => $this->record->tecnico_id,
                'status_rastreador_id' => $this->record->status_rastreador_id,
                'is_estoque' => $this->record->is_estoque,
            ],
        );
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

    private function descricaoConfirmacaoRastreador(?int $rastreadorId): string
    {
        $veiculo = $rastreadorId === null
            ? null
            : $this->outrosVeiculosAtivosComRastreador($rastreadorId)
                ->with('cliente:id,nome')
                ->first();

        if ($veiculo === null) {
            return 'Este IMEI ja esta vinculado a outro veiculo ativo. Voce pode transferi-lo removendo o vinculo anterior ou desativar o vinculo anterior para manter o historico.';
        }

        $identificacao = trim($veiculo->veiculo.' / '.$veiculo->placa, ' /');
        $cliente = $veiculo->cliente?->nome ?? 'cliente nao informado';

        return "Este IMEI esta vinculado ao veiculo {$identificacao} (cadastro #{$veiculo->id}), do cliente {$cliente}. Voce pode transferi-lo removendo o vinculo anterior ou desativar o vinculo anterior para manter o historico.";
    }

    private function desvincularRastreadorDeOutrosVeiculosAtivos(?int $rastreadorId): void
    {
        if ($rastreadorId === null) {
            return;
        }

        $this->outrosVeiculosAtivosComRastreador($rastreadorId)
            ->get()
            ->each(fn (Veiculo $veiculo): bool => $veiculo->update(['rastreador_id' => null]));
    }

    private function desativarVinculoAnteriorDoRastreador(?int $rastreadorId): void
    {
        if ($rastreadorId === null) {
            return;
        }

        $tecnicoId = Rastreador::query()->whereKey($rastreadorId)->value('tecnico_id');

        $this->outrosVeiculosAtivosComRastreador($rastreadorId)
            ->get()
            ->each(fn (Veiculo $veiculo): bool => $veiculo->update([
                'status_rastreador_id' => Veiculo::statusId('Cancelado'),
                'data_retirada' => now()->toDateString(),
                'tecnico_remocao_id' => $veiculo->tecnico_instala_id ?? $tecnicoId,
            ]));
    }

    private function outrosVeiculosAtivosComRastreador(int $rastreadorId)
    {
        return Veiculo::query()
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
