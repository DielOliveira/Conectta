<?php

namespace Tests\Feature;

use App\Enums\OrdemServicoStatus;
use App\Filament\Resources\Rastreadores\Pages\EditRastreador;
use App\Filament\Resources\Rastreadores\Pages\ListRastreadores;
use App\Models\AuditLog;
use App\Models\Chip;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Rastreador;
use App\Models\StatusCliente;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\Estoque\EquipamentoStatusWorkflow;
use App\Services\Veiculo\VeiculoCancelamentoService;
use Database\Seeders\ClienteSupportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class VeiculoCancelamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelamento_sem_retirada_preserva_historico_e_envia_equipamentos_ao_tecnico_lixo(): void
    {
        [$operador, $cliente, $veiculo, $rastreador, $chip, $lixo] = $this->cenarioAtivo();

        $ordemAtiva = OrdemServico::query()->create([
            'numero' => 1,
            'tipo' => 'manutencao',
            'status' => OrdemServicoStatus::ABERTA,
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'endereco' => 'Rua de teste, 1',
            'descricao' => 'OS ainda ativa',
        ]);
        $ordemFinalizada = OrdemServico::query()->create([
            'numero' => 2,
            'tipo' => 'instalacao',
            'status' => OrdemServicoStatus::FINALIZADA,
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'endereco' => 'Rua de teste, 1',
            'descricao' => 'OS histórica',
            'finalizada_em' => now(),
        ]);

        $this->actingAs($operador);
        app(VeiculoCancelamentoService::class)->cancelarSemRetirada(
            $veiculo,
            'Rompimento comercial solicitado pelo cliente.',
            $operador,
        );

        $veiculo->refresh();
        $rastreador->refresh();
        $chip->refresh();

        $this->assertSame('Cancelado', $veiculo->statusRastreador->label);
        $this->assertSame('Rompimento comercial solicitado pelo cliente.', $veiculo->motivo_cancelamento);
        $this->assertNotNull($veiculo->cancelado_em);
        $this->assertSame($operador->id, $veiculo->cancelado_por);
        $this->assertNull($veiculo->data_retirada);
        $this->assertNull($veiculo->tecnico_remocao_id);
        $this->assertSame($rastreador->id, $veiculo->rastreador_id);
        $this->assertSame($chip->id, $rastreador->chip_id);

        $this->assertSame('Cancelado', $rastreador->statusRastreador->label);
        $this->assertSame($lixo->id, $rastreador->tecnico_id);
        $this->assertSame('Cancelado', $chip->statusRastreador->label);
        $this->assertSame($lixo->id, $chip->tecnico_id);

        $this->assertSame(OrdemServicoStatus::CANCELADA, $ordemAtiva->fresh()->status);
        $this->assertSame(
            'Veículo cancelado sem retirada: Rompimento comercial solicitado pelo cliente.',
            $ordemAtiva->fresh()->motivo_cancelamento,
        );
        $this->assertSame(OrdemServicoStatus::FINALIZADA, $ordemFinalizada->fresh()->status);
        $this->assertSame('Inativo', $cliente->refresh()->statusCliente->label);

        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'veiculo.cancelado_sem_retirada',
            'entidade_tipo' => 'Veiculo',
            'entidade_id' => $veiculo->id,
            'user_id' => $operador->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'rastreador.cancelado_sem_retirada',
            'entidade_id' => $rastreador->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'chip.cancelado_sem_retirada',
            'entidade_id' => $chip->id,
        ]);
    }

    public function test_botao_da_lista_exige_motivo_e_executa_cancelamento_sem_retirada(): void
    {
        [$operador, , $veiculo] = $this->cenarioAtivo();
        $this->actingAs($operador);

        Livewire::test(ListRastreadores::class)
            ->callTableAction('cancelar', $veiculo, ['motivo' => 'Cliente encerrou o vínculo.'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Cancelado', $veiculo->refresh()->statusRastreador->label);
        $this->assertSame('Cliente encerrou o vínculo.', $veiculo->motivo_cancelamento);

        Livewire::test(EditRastreador::class, ['record' => $veiculo->getRouteKey()])
            ->assertSee('Cancelamento sem retirada')
            ->assertSet('data.motivo_cancelamento', 'Cliente encerrou o vínculo.');
    }

    public function test_cancelamento_nao_movimenta_equipamento_compartilhado_com_outro_veiculo_ativo(): void
    {
        [$operador, $cliente, $veiculo, $rastreador, $chip] = $this->cenarioAtivo();
        $ativoId = StatusRastreador::query()->where('label', 'Ativo')->value('id');

        DB::table('veiculos')->insert([
            'cliente_id' => $cliente->id,
            'status_rastreador_id' => $ativoId,
            'rastreador_id' => $rastreador->id,
            'veiculo' => 'Duplicidade legada ativa',
            'placa' => 'LEG-1A24',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operador);
        app(VeiculoCancelamentoService::class)->cancelarSemRetirada($veiculo, 'Encerramento do vínculo.', $operador);

        $this->assertSame('Ativo', $rastreador->refresh()->statusRastreador->label);
        $this->assertNull($rastreador->tecnico_id);
        $this->assertSame('Ativo', $chip->refresh()->statusRastreador->label);
        $this->assertNull($chip->tecnico_id);
        $this->assertSame('Ativo', $cliente->refresh()->statusCliente->label);

        $auditoria = AuditLog::query()
            ->where('acao', 'veiculo.cancelado_sem_retirada')
            ->where('entidade_id', $veiculo->id)
            ->firstOrFail();
        $this->assertSame('preservado_em_veiculo_ativo', $auditoria->contexto['equipamento_destino']);
    }

    private function cenarioAtivo(): array
    {
        $this->seed(ClienteSupportSeeder::class);

        $operador = User::factory()->create(['is_admin' => true]);
        $lixo = Tecnico::query()->create(['nome' => 'Lixo', 'is_ativo' => true]);
        $cliente = Cliente::query()->create([
            'status_cliente_id' => StatusCliente::query()->where('label', 'Ativo')->value('id'),
            'nome' => 'Cliente cancelamento sem retirada',
            'cpf_cnpj' => '52998224725',
            'telefone1' => '62999999999',
            'data_adesao' => '2026-09-01',
            'dia_pagamento' => 10,
        ]);
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990201',
            'iccid' => '89550000000000000201',
        ]);
        $rastreador = Rastreador::query()->create([
            'modelo' => 'Modelo cancelamento',
            'imei' => '860000000000201',
        ]);
        $ativoId = StatusRastreador::query()->where('label', 'Ativo')->value('id');

        EquipamentoStatusWorkflow::executar(function () use ($chip, $rastreador, $ativoId): void {
            $chip->update(['status_rastreador_id' => $ativoId, 'tecnico_id' => null]);
            $rastreador->update([
                'chip_id' => $chip->id,
                'status_rastreador_id' => $ativoId,
                'tecnico_id' => null,
            ]);
        });

        $veiculo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'status_rastreador_id' => $ativoId,
            'rastreador_id' => $rastreador->id,
            'veiculo' => 'Veículo ativo',
            'placa' => 'CAN-1A23',
        ]);

        return [$operador, $cliente, $veiculo, $rastreador, $chip, $lixo];
    }
}
