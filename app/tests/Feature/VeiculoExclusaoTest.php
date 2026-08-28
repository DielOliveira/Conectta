<?php

namespace Tests\Feature;

use App\Enums\OrdemServicoStatus;
use App\Filament\Resources\Rastreadores\Pages\ListRastreadores;
use App\Models\Chip;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\Estoque\EquipamentoStatusWorkflow;
use App\Services\OrdemServico\OrdemServicoService;
use App\Services\Veiculo\VeiculoExclusaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class VeiculoExclusaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_exclusao_oculta_veiculo_cancela_os_ativa_e_preserva_historico(): void
    {
        $this->criarStatusRastreadores();
        $operador = User::factory()->create(['is_admin' => true]);
        $cliente = Cliente::query()->create([
            'nome' => 'Cliente exclusão',
            'cpf_cnpj' => '52998224725',
            'telefone1' => '62999999999',
            'data_adesao' => '2026-01-01',
            'dia_pagamento' => 10,
        ]);
        $veiculo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'veiculo' => 'Automóvel excluído',
            'placa' => 'EXC-1A23',
        ]);
        $ordem = app(OrdemServicoService::class)->criar([
            'tipo' => 'instalacao',
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'endereco' => 'Rua de Teste, 1',
            'descricao' => 'Instalar equipamento',
            'notificar_cliente' => false,
        ], $operador)['ordem'];

        $this->actingAs($operador);
        app(VeiculoExclusaoService::class)->excluir([$veiculo], $operador);

        $this->assertNull(Veiculo::query()->find($veiculo->id));
        $this->assertNotNull(Veiculo::query()->withTrashed()->find($veiculo->id)?->data_exclusao);
        $this->assertFalse($cliente->veiculos()->whereKey($veiculo->id)->exists());
        $this->assertSame(OrdemServicoStatus::CANCELADA, $ordem->fresh()->status);
        $this->assertSame('Veículo excluído do cadastro.', $ordem->fresh()->motivo_cancelamento);
        $this->assertSame($veiculo->id, $ordem->fresh()->veiculo?->id);
        $this->assertDatabaseHas('ordem_servico_historicos', [
            'ordem_servico_id' => $ordem->id,
            'evento' => 'cancelamento',
            'status_novo' => OrdemServicoStatus::CANCELADA->value,
            'user_id' => $operador->id,
            'observacao' => 'Veículo excluído do cadastro.',
        ]);
    }

    public function test_exclusao_preserva_os_finalizada_e_libera_placa_para_novo_cadastro(): void
    {
        $this->criarStatusRastreadores();
        $operador = User::factory()->create(['is_admin' => true]);
        $cliente = Cliente::query()->create([
            'nome' => 'Cliente histórico',
            'cpf_cnpj' => '04252011000110',
            'telefone1' => '62999999999',
            'data_adesao' => '2026-01-01',
            'dia_pagamento' => 10,
        ]);
        $veiculo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'veiculo' => 'Veículo histórico',
            'placa' => 'HIS-1A23',
        ]);
        $ordem = OrdemServico::query()->create([
            'numero' => 1,
            'tipo' => 'instalacao',
            'status' => OrdemServicoStatus::FINALIZADA,
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'endereco' => 'Rua de Teste, 1',
            'descricao' => 'Ordem já concluída',
            'finalizada_em' => now(),
        ]);

        $this->actingAs($operador);
        app(VeiculoExclusaoService::class)->excluir([$veiculo], $operador);

        $this->assertSame(OrdemServicoStatus::FINALIZADA, $ordem->fresh()->status);
        $this->assertSame('Veículo histórico', $ordem->fresh()->veiculo?->veiculo);

        $novo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'veiculo' => 'Novo veículo',
            'placa' => 'his 1a23',
        ]);

        $this->assertSame('his 1a23', $novo->placa);
    }

    public function test_botao_da_lista_executa_exclusao_logica(): void
    {
        $this->criarStatusRastreadores();
        $operador = User::factory()->create(['is_admin' => true]);
        $cliente = $this->criarCliente('Cliente botão', '11144477735');
        $veiculo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'veiculo' => 'Veículo pelo botão',
            'placa' => 'BTN-1A23',
        ]);

        $this->actingAs($operador);

        Livewire::test(ListRastreadores::class)
            ->callTableAction('delete', $veiculo)
            ->assertHasNoTableActionErrors();

        $this->assertNull(Veiculo::query()->find($veiculo->id));
        $this->assertNotNull(Veiculo::query()->withTrashed()->find($veiculo->id));
    }

    public function test_exclusao_em_lote_tambem_arquiva_os_veiculos(): void
    {
        $this->criarStatusRastreadores();
        $operador = User::factory()->create(['is_admin' => true]);
        $cliente = $this->criarCliente('Cliente lote', '93541134780');
        $primeiro = Veiculo::query()->create(['cliente_id' => $cliente->id, 'veiculo' => 'Primeiro', 'placa' => 'LOT-1A23']);
        $segundo = Veiculo::query()->create(['cliente_id' => $cliente->id, 'veiculo' => 'Segundo', 'placa' => 'LOT-1A24']);

        $this->actingAs($operador);

        Livewire::test(ListRastreadores::class)
            ->callTableBulkAction('delete', [$primeiro, $segundo]);

        $this->assertSame(0, Veiculo::query()->whereKey([$primeiro->id, $segundo->id])->count());
        $this->assertSame(2, Veiculo::query()->withTrashed()->whereKey([$primeiro->id, $segundo->id])->count());
    }

    public function test_exclusao_nao_libera_equipamento_usado_por_outro_veiculo_ativo(): void
    {
        $this->criarStatusRastreadores();
        $operador = User::factory()->create(['is_admin' => true]);
        $cliente = $this->criarCliente('Cliente duplicidade legada', '39053344705');
        $ativoId = StatusRastreador::query()->where('label', 'Ativo')->value('id');
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990101',
            'iccid' => '89550000000000000101',
        ]);
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000101',
        ]);

        EquipamentoStatusWorkflow::executar(function () use ($chip, $rastreador, $ativoId): void {
            $chip->update([
                'status_rastreador_id' => $ativoId,
                'tecnico_id' => null,
            ]);
            $rastreador->update([
                'chip_id' => $chip->id,
                'status_rastreador_id' => $ativoId,
                'tecnico_id' => null,
            ]);
        });

        $veiculoExcluido = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'status_rastreador_id' => $ativoId,
            'rastreador_id' => $rastreador->id,
            'veiculo' => 'Cadastro antigo duplicado',
            'placa' => 'DUP-1A23',
        ]);

        $outroVeiculoId = DB::table('veiculos')->insertGetId([
            'cliente_id' => $cliente->id,
            'status_rastreador_id' => $ativoId,
            'rastreador_id' => $rastreador->id,
            'veiculo' => 'Cadastro ativo correto',
            'placa' => 'DUP-1A23',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operador);
        app(VeiculoExclusaoService::class)->excluir([$veiculoExcluido], $operador);

        $this->assertNull(Veiculo::query()->find($veiculoExcluido->id));
        $this->assertSame($rastreador->id, Veiculo::query()->findOrFail($outroVeiculoId)->rastreador_id);
        $this->assertSame((int) $ativoId, (int) $rastreador->refresh()->status_rastreador_id);
        $this->assertNull($rastreador->tecnico_id);
        $this->assertSame((int) $ativoId, (int) $chip->refresh()->status_rastreador_id);
        $this->assertNull($chip->tecnico_id);
    }

    private function criarStatusRastreadores(): void
    {
        StatusRastreador::query()->create(['label' => 'Disponivel', 'order' => 1, 'is_active' => true]);
        StatusRastreador::query()->create(['label' => 'Ativo', 'order' => 2, 'is_active' => true]);
        StatusRastreador::query()->create(['label' => 'Cancelado', 'order' => 3, 'is_active' => true]);
    }

    private function criarCliente(string $nome, string $documento): Cliente
    {
        return Cliente::query()->create([
            'nome' => $nome,
            'cpf_cnpj' => $documento,
            'telefone1' => '62999999999',
            'data_adesao' => '2026-01-01',
            'dia_pagamento' => 10,
        ]);
    }
}
