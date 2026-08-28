<?php

namespace Tests\Feature;

use App\Filament\Pages\EstoqueRastreadores;
use App\Models\Chip;
use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\Operadora;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\Estoque\EquipamentoStatusWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class EstoqueRastreadoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_existing_chip_can_be_linked_from_tracker_row_action(): void
    {
        $this->actingAs($this->admin());

        $status = $this->statusDisponivel();
        $tecnico = Tecnico::query()->create(['nome' => 'Tecnico Teste']);
        $rastreador = $this->rastreador('123456789012345', $tecnico, $status);
        $chip = Chip::query()->create([
            'fornecedor' => 'HINOVA',
            'fornecedor_id' => Fornecedor::query()->where('nome', 'HINOVA')->value('id'),
            'operadora' => 'VIVO',
            'operadora_id' => Operadora::query()->where('nome', 'VIVO')->value('id'),
            'numero_chip' => '5562999990000',
            'iccid' => '89550000000000000001',
            'status_rastreador_id' => $status->id,
        ]);

        Livewire::test(EstoqueRastreadores::class)
            ->callAction('adicionarChip', [
                'chip_id' => $chip->id,
                'fornecedor_id' => $chip->fornecedor_id,
                'operadora_id' => $chip->operadora_id,
                'numero_chip' => $chip->numero_chip,
                'iccid' => $chip->iccid,
            ], [
                'id' => $rastreador->id,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame($chip->id, $rastreador->refresh()->chip_id);
        $this->assertSame('89550000000000000001', $rastreador->chip?->iccid);
        $this->assertSame($tecnico->id, $chip->refresh()->tecnico_id);
        $this->assertSame($status->id, $chip->status_rastreador_id);
        $this->assertSame(7, $chip->operadora_id);
        $this->assertSame('VIVO', $chip->operadora);
        $this->assertSame(1, $chip->refresh()->fornecedor_id);
        $this->assertSame('HINOVA', $chip->fornecedor);
    }

    public function test_available_existing_chip_can_be_linked_and_follows_tracker_technician(): void
    {
        $this->actingAs($this->admin());

        $status = $this->statusDisponivel();
        $tecnico = Tecnico::query()->create(['nome' => 'Tecnico Responsavel']);
        $rastreador = $this->rastreador('123456789012349', $tecnico, $status);
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990003',
            'iccid' => '89550000000000000013',
            'status_rastreador_id' => $status->id,
        ]);

        Livewire::test(EstoqueRastreadores::class)
            ->callAction('adicionarChip', [
                'chip_id' => $chip->id,
                'numero_chip' => $chip->numero_chip,
                'iccid' => $chip->iccid,
            ], [
                'id' => $rastreador->id,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame($chip->id, $rastreador->refresh()->chip_id);
        $this->assertSame($tecnico->id, $chip->refresh()->tecnico_id);
    }

    public function test_unavailable_existing_chip_cannot_be_linked_from_tracker_row_action(): void
    {
        $this->actingAs($this->admin());

        $disponivel = $this->statusDisponivel();
        $ativo = StatusRastreador::query()->create([
            'label' => 'Ativo',
            'order' => 2,
            'is_active' => true,
        ]);
        $tecnico = Tecnico::query()->create(['nome' => 'Tecnico Teste']);
        $rastreador = $this->rastreador('123456789012346', $tecnico, $disponivel);
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990001',
            'iccid' => '89550000000000000011',
            'status_rastreador_id' => $ativo->id,
        ]);
        EquipamentoStatusWorkflow::executar(
            fn () => $chip->update(['status_rastreador_id' => $ativo->id]),
        );

        Livewire::test(EstoqueRastreadores::class)
            ->callAction('adicionarChip', [
                'chip_id' => $chip->id,
                'numero_chip' => $chip->numero_chip,
                'iccid' => $chip->iccid,
            ], [
                'id' => $rastreador->id,
            ])
            ->assertHasActionErrors(['numero_chip']);

        $this->assertNull($rastreador->refresh()->chip_id);
    }

    public function test_new_chip_can_still_be_created_with_all_original_fields(): void
    {
        $this->actingAs($this->admin());

        $status = $this->statusDisponivel();
        $tecnico = Tecnico::query()->create(['nome' => 'Tecnico Novo Chip']);
        $rastreador = $this->rastreador('123456789012347', $tecnico, $status);
        Livewire::test(EstoqueRastreadores::class)
            ->mountAction('adicionarChip', ['id' => $rastreador->id])
            ->setActionData([
                'chip_id' => null,
                'numero_chip' => '5562999990002',
            ])
            ->setActionData([
                'fornecedor_id' => Fornecedor::query()->where('nome', 'HINOVA')->value('id'),
                'operadora_id' => Operadora::query()->where('nome', 'VIVO')->value('id'),
                'iccid' => '89550000000000000012',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $chip = Chip::query()->where('numero_chip', '5562999990002')->firstOrFail();
        $this->assertSame($chip->id, $rastreador->refresh()->chip_id);
        $this->assertSame('HINOVA', $chip->refresh()->fornecedor);
        $this->assertSame('VIVO', $chip->operadora);
        $this->assertSame($tecnico->id, $chip->tecnico_id);
        $this->assertSame($status->id, $chip->status_rastreador_id);
    }

    public function test_changing_tracker_technician_preserves_status_and_changes_linked_chip_technician(): void
    {
        $this->actingAs($this->admin());

        $status = $this->statusDisponivel();
        $tecnicoAtual = Tecnico::query()->create(['nome' => 'Tecnico Atual']);
        $novoTecnico = Tecnico::query()->create(['nome' => 'Tecnico Novo']);
        $rastreador = $this->rastreador('333333333333333', $tecnicoAtual, $status);
        $chip = Chip::query()->create([
            'numero_chip' => '62977776666',
            'iccid' => '89550000000000000003',
            'status_rastreador_id' => $status->id,
            'tecnico_id' => $tecnicoAtual->id,
        ]);
        $rastreador->update(['chip_id' => $chip->id]);

        Livewire::test(EstoqueRastreadores::class)
            ->call('editar', $rastreador->id)
            ->set('tecnico_id', $novoTecnico->id)
            ->call('salvar')
            ->assertHasNoErrors();

        $this->assertSame($novoTecnico->id, $rastreador->refresh()->tecnico_id);
        $this->assertSame($novoTecnico->id, $chip->refresh()->tecnico_id);
        $this->assertSame($status->id, $rastreador->status_rastreador_id);
        $this->assertSame($status->id, $chip->status_rastreador_id);
    }

    public function test_tracker_status_can_be_changed_temporarily_from_stock_form(): void
    {
        $this->actingAs($this->admin());

        $disponivel = $this->statusDisponivel();
        $manutencao = StatusRastreador::query()->create([
            'label' => 'Manutencao',
            'order' => 2,
            'is_active' => true,
        ]);
        $tecnico = Tecnico::query()->create(['nome' => 'Tecnico Status']);
        $rastreador = $this->rastreador('333333333333334', $tecnico, $disponivel);

        Livewire::test(EstoqueRastreadores::class)
            ->call('editar', $rastreador->id)
            ->assertSet('status_rastreador_id', $disponivel->id)
            ->set('status_rastreador_id', $manutencao->id)
            ->call('salvar')
            ->assertHasNoErrors();

        $this->assertSame($manutencao->id, $rastreador->refresh()->status_rastreador_id);
    }

    public function test_changing_active_tracker_technician_requires_confirmation_and_syncs_vehicle(): void
    {
        $this->actingAs($this->admin());

        $this->statusDisponivel();
        $statusAtivo = StatusRastreador::query()->create([
            'label' => 'Ativo',
            'order' => 1,
            'is_active' => true,
        ]);
        $tecnicoAtual = Tecnico::query()->create(['nome' => 'Tecnico Atual']);
        $novoTecnico = Tecnico::query()->create(['nome' => 'Tecnico Novo']);
        $chip = Chip::query()->create([
            'numero_chip' => '62977775555',
            'iccid' => '89550000000000000007',
            'status_rastreador_id' => $statusAtivo->id,
            'tecnico_id' => $tecnicoAtual->id,
        ]);
        $rastreador = Rastreador::query()->create([
            'modelo' => 'Modelo Ativo',
            'ativacao' => 2026,
            'imei' => '777777777777777',
            'chip_id' => $chip->id,
            'tecnico_id' => $tecnicoAtual->id,
            'status_rastreador_id' => $statusAtivo->id,
        ]);
        EquipamentoStatusWorkflow::executar(function () use ($chip, $rastreador, $statusAtivo): void {
            $chip->update(['status_rastreador_id' => $statusAtivo->id]);
            $rastreador->update(['status_rastreador_id' => $statusAtivo->id]);
        });
        $cliente = Cliente::query()->create([
            'nome' => 'Cliente Rastreador Ativo',
            'cpf_cnpj' => '52998224725',
            'telefone1' => '62999999999',
            'data_adesao' => '2026-07-23',
            'dia_pagamento' => 10,
        ]);
        $veiculo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'status_rastreador_id' => $statusAtivo->id,
            'rastreador_id' => $rastreador->id,
            'veiculo' => 'Toyota / Corolla',
            'placa' => 'ABC-1D23',
        ]);

        $component = Livewire::test(EstoqueRastreadores::class)
            ->call('editar', $rastreador->id)
            ->set('tecnico_id', $novoTecnico->id)
            ->call('salvar')
            ->assertSet('sincronizacaoTecnicoDescricao', fn (?string $descricao): bool => str_contains(
                (string) $descricao,
                'no rastreador, no chip vinculado e no tecnico de instalacao do veiculo',
            ));

        $this->assertSame($tecnicoAtual->id, $rastreador->refresh()->tecnico_id);
        $this->assertSame($tecnicoAtual->id, $chip->refresh()->tecnico_id);
        $this->assertSame($tecnicoAtual->id, $veiculo->refresh()->tecnico_instala_id);

        $component
            ->callMountedAction()
            ->assertHasNoErrors();

        $this->assertSame($novoTecnico->id, $rastreador->refresh()->tecnico_id);
        $this->assertSame($novoTecnico->id, $chip->refresh()->tecnico_id);
        $this->assertSame($statusAtivo->id, $rastreador->status_rastreador_id);
        $this->assertSame($novoTecnico->id, $veiculo->refresh()->tecnico_instala_id);
        $this->assertSame('Tecnico Novo', $veiculo->instalador);
    }

    public function test_chip_can_be_unlinked_without_being_deleted(): void
    {
        $this->actingAs($this->admin());

        $status = $this->statusDisponivel();
        $tecnico = Tecnico::query()->create(['nome' => 'Tecnico Remocao']);
        $rastreador = $this->rastreador('444444444444444', $tecnico, $status);
        $chip = Chip::query()->create([
            'numero_chip' => '62966665555',
            'iccid' => '89550000000000000004',
            'status_rastreador_id' => $status->id,
            'tecnico_id' => $tecnico->id,
        ]);
        $rastreador->update(['chip_id' => $chip->id]);

        Livewire::test(EstoqueRastreadores::class)
            ->callAction('removerChip', [], ['id' => $rastreador->id]);

        $this->assertNull($rastreador->refresh()->chip_id);
        $this->assertTrue($chip->fresh()->exists);
    }

    public function test_tracker_search_matches_chip_number_and_iccid(): void
    {
        $this->actingAs($this->admin());

        $status = $this->statusDisponivel();
        $tecnico = Tecnico::query()->create(['nome' => 'Tecnico Busca']);
        $encontrado = $this->rastreador('111111111111111', $tecnico, $status);
        $naoEncontrado = $this->rastreador('222222222222222', $tecnico, $status);

        $chip = Chip::query()->create([
            'fornecedor' => 'HINOVA',
            'fornecedor_id' => Fornecedor::query()->where('nome', 'HINOVA')->value('id'),
            'operadora_id' => Operadora::query()->where('nome', 'TIM')->value('id'),
            'numero_chip' => '62988887777',
            'iccid' => '89550000000000000002',
            'status_rastreador_id' => $status->id,
            'tecnico_id' => $tecnico->id,
        ]);
        $encontrado->update(['chip_id' => $chip->id]);

        Livewire::test(EstoqueRastreadores::class)
            ->set('search', '6298888')
            ->assertSee($encontrado->imei)
            ->assertDontSee($naoEncontrado->imei)
            ->set('search', '89550000000000000002')
            ->assertSee($encontrado->imei)
            ->assertDontSee($naoEncontrado->imei);
    }

    private function admin(): User
    {
        return User::query()->create([
            'name' => 'Admin Estoque',
            'email' => 'admin-estoque@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);
    }

    private function statusDisponivel(): StatusRastreador
    {
        return StatusRastreador::query()->create([
            'label' => 'Disponivel',
            'order' => 1,
            'is_active' => true,
        ]);
    }

    private function rastreador(string $imei, Tecnico $tecnico, StatusRastreador $status): Rastreador
    {
        return Rastreador::query()->create([
            'modelo' => 'Modelo Teste',
            'imei' => $imei,
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $status->id,
        ]);
    }
}
