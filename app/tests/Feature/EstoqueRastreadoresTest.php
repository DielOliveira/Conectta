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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class EstoqueRastreadoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_chip_can_be_created_and_linked_from_tracker_row_action(): void
    {
        $this->actingAs($this->admin());

        $status = $this->statusDisponivel();
        $tecnico = Tecnico::query()->create(['nome' => 'Tecnico Teste']);
        $rastreador = $this->rastreador('123456789012345', $tecnico, $status);

        Livewire::test(EstoqueRastreadores::class)
            ->callAction('adicionarChip', [
                'fornecedor_id' => Fornecedor::query()->where('nome', 'HINOVA')->value('id'),
                'operadora_id' => Operadora::query()->where('nome', 'VIVO')->value('id'),
                'numero_chip' => '62999990000',
                'iccid' => '89550000000000000001',
            ], [
                'id' => $rastreador->id,
            ])
            ->assertHasNoActionErrors();

        $chip = Chip::query()->where('numero_chip', '5562999990000')->firstOrFail();

        $this->assertSame($chip->id, $rastreador->refresh()->chip_id);
        $this->assertSame('89550000000000000001', $rastreador->chip?->iccid);
        $this->assertSame($tecnico->id, $chip->tecnico_id);
        $this->assertSame($status->id, $chip->status_rastreador_id);
        $this->assertSame(7, $chip->operadora_id);
        $this->assertSame('VIVO', $chip->operadora);
        $this->assertSame(1, $chip->fornecedor_id);
        $this->assertSame('HINOVA', $chip->fornecedor);
    }

    public function test_changing_tracker_technician_and_status_also_changes_linked_chip(): void
    {
        $this->actingAs($this->admin());

        $status = $this->statusDisponivel();
        $novoStatus = StatusRastreador::query()->create([
            'label' => 'Ativo',
            'order' => 2,
            'is_active' => true,
        ]);
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
            ->set('status_rastreador_id', $novoStatus->id)
            ->call('salvar')
            ->assertHasNoErrors();

        $this->assertSame($novoTecnico->id, $rastreador->refresh()->tecnico_id);
        $this->assertSame($novoTecnico->id, $chip->refresh()->tecnico_id);
        $this->assertSame($novoStatus->id, $rastreador->status_rastreador_id);
        $this->assertSame($novoStatus->id, $chip->status_rastreador_id);
    }

    public function test_changing_active_tracker_technician_requires_confirmation_and_syncs_vehicle(): void
    {
        $this->actingAs($this->admin());

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
            'is_estoque' => true,
        ]);
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
            'is_estoque' => true,
        ]);
    }
}
