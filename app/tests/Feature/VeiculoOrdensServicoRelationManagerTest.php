<?php

namespace Tests\Feature;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Filament\Resources\OrdensServico\OrdemServicoResource;
use App\Filament\Resources\Rastreadores\Pages\EditRastreador;
use App\Filament\Resources\Rastreadores\RelationManagers\OrdensServicoRelationManager;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VeiculoOrdensServicoRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_exibe_historico_da_placa_em_ordem_com_link_para_a_os(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $statusAtivo = StatusRastreador::query()->create([
            'label' => 'Ativo',
            'order' => 1,
            'is_active' => true,
        ]);
        StatusRastreador::query()->create([
            'label' => 'Cancelado',
            'order' => 2,
            'is_active' => true,
        ]);
        $cliente = Cliente::query()->create([
            'nome' => 'Cliente do histórico',
            'cpf_cnpj' => '52998224725',
            'data_adesao' => '2026-01-01',
            'dia_pagamento' => 10,
        ]);
        $veiculo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'status_rastreador_id' => $statusAtivo->id,
            'veiculo' => 'Automóvel',
            'placa' => 'HIS-1A23',
        ]);
        $tecnico = Tecnico::query()->create([
            'nome' => 'Técnico do atendimento',
            'telefone' => '62988888888',
            'is_ativo' => true,
        ]);
        $antiga = $this->ordem($cliente, $veiculo, $tecnico, 10, OrdemServicoTipo::INSTALACAO, OrdemServicoStatus::FINALIZADA, '2026-07-10 09:00:00');
        $recente = $this->ordem($cliente, $veiculo, $tecnico, 11, OrdemServicoTipo::MANUTENCAO, OrdemServicoStatus::EM_CONFERENCIA, '2026-08-20 14:00:00');

        $this->actingAs($admin);

        Livewire::test(OrdensServicoRelationManager::class, [
            'ownerRecord' => $veiculo,
            'pageClass' => EditRastreador::class,
        ])
            ->assertCanSeeTableRecords([$recente, $antiga], inOrder: true)
            ->assertTableColumnFormattedStateSet('numero', 'OS 000011', $recente)
            ->assertTableColumnFormattedStateSet('tipo', 'Manutenção', $recente)
            ->assertTableColumnFormattedStateSet('status', 'Em conferência', $recente)
            ->assertTableColumnFormattedStateSet('tecnico.nome', 'Técnico do atendimento', $recente)
            ->assertTableActionHasUrl(
                'verOrdemServico',
                OrdemServicoResource::getUrl('edit', ['record' => $recente]),
                $recente,
            );
    }

    private function ordem(
        Cliente $cliente,
        Veiculo $veiculo,
        Tecnico $tecnico,
        int $numero,
        OrdemServicoTipo $tipo,
        OrdemServicoStatus $status,
        string $agendadoEm,
    ): OrdemServico {
        return OrdemServico::query()->create([
            'numero' => $numero,
            'tipo' => $tipo,
            'status' => $status,
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'tecnico_id' => $tecnico->id,
            'agendado_em' => $agendadoEm,
            'endereco' => 'Rua de Teste, 1',
            'descricao' => 'Atendimento de teste',
            'notificar_cliente' => false,
        ]);
    }
}
