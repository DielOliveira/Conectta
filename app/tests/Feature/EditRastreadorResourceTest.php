<?php

namespace Tests\Feature;

use App\Filament\Resources\Rastreadores\Pages\CreateRastreador;
use App\Filament\Resources\Rastreadores\Pages\EditRastreador;
use App\Models\Chip;
use App\Models\Cliente;
use App\Models\Rastreador;
use App\Models\StatusCliente;
use App\Models\StatusRastreador;
use App\Models\TipoVeiculo;
use App\Models\User;
use App\Models\Veiculo;
use Database\Seeders\ClienteSupportSeeder;
use Database\Seeders\PaisSeeder;
use Database\Seeders\RastreadorSupportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class EditRastreadorResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracker_status_is_required_only_when_an_imei_is_selected(): void
    {
        $this->seed(ClienteSupportSeeder::class);
        $this->seed(PaisSeeder::class);
        $this->seed(RastreadorSupportSeeder::class);
        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-status-rastreador@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        $cliente = $this->cliente('Cliente sem rastreador', '52998224725');
        $tipoVeiculo = TipoVeiculo::query()->where('label', 'Carro')->firstOrFail();
        $dados = [
            'cliente_id' => $cliente->id,
            'veiculo' => 'Veiculo sem rastreador',
            'placa' => 'SEM-1A23',
            'cor' => 'Prata',
            'ano' => '2026',
            'tipo_veiculo_id' => $tipoVeiculo->id,
            'contato_pais' => 'BR',
        ];

        Livewire::test(CreateRastreador::class)
            ->fillForm($dados)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('veiculos', [
            'placa' => 'SEM-1A23',
            'rastreador_id' => null,
            'status_rastreador_id' => null,
        ]);

        Livewire::test(CreateRastreador::class)
            ->fillForm([
                ...$dados,
                'veiculo' => 'Veiculo com rastreador',
                'placa' => 'COM-4B56',
                'rastreador_id' => Rastreador::query()->firstOrFail()->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['status_rastreador_id' => 'required']);
    }

    public function test_imei_linked_to_another_active_vehicle_is_blocked_with_an_informative_modal(): void
    {
        $this->seed(ClienteSupportSeeder::class);
        $this->seed(RastreadorSupportSeeder::class);
        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-rastreadores@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        $ativoId = StatusRastreador::query()->where('label', 'Ativo')->value('id');
        $tipoVeiculoId = TipoVeiculo::query()->where('label', 'Carro')->value('id');
        $clienteAnterior = $this->cliente('Cliente Anterior', '52998224725');
        $clienteNovo = $this->cliente('Cliente Novo', '04252011000110');
        $chip = Chip::query()->firstOrFail();
        $rastreador = Rastreador::query()->firstOrFail();
        $rastreador->update([
            'chip_id' => $chip->id,
            'status_rastreador_id' => $ativoId,
        ]);
        $veiculoAnterior = $this->veiculo($clienteAnterior, $ativoId, $tipoVeiculoId, [
            'rastreador_id' => $rastreador->id,
            'veiculo' => 'Honda / Civic',
            'placa' => 'ANT-1G00',
        ]);
        $veiculoNovo = $this->veiculo($clienteNovo, $ativoId, $tipoVeiculoId, [
            'veiculo' => 'Toyota / Corolla',
            'placa' => 'NOV-2H00',
        ]);

        Livewire::test(EditRastreador::class, ['record' => $veiculoNovo->getRouteKey()])
            ->set('data.rastreador_id', $rastreador->id)
            ->assertSet('data.chip_id_form', $chip->id)
            ->call('save')
            ->assertSet(
                'rastreadorIndisponivelDescricao',
                fn (?string $descricao): bool => str_contains((string) $descricao, 'Honda / Civic')
                    && str_contains((string) $descricao, 'Cliente Anterior')
                    && str_contains((string) $descricao, 'Cancele primeiro'),
            )
            ->assertActionMounted('rastreadorIndisponivel');

        $this->assertSame($rastreador->id, $veiculoAnterior->refresh()->rastreador_id);
        $this->assertNull($veiculoNovo->refresh()->rastreador_id);
        $this->assertSame($chip->id, $rastreador->refresh()->chip_id);
    }

    private function cliente(string $nome, string $cpfCnpj): Cliente
    {
        return Cliente::query()->create([
            'status_cliente_id' => StatusCliente::query()->where('label', 'Inativo')->value('id'),
            'nome' => $nome,
            'cpf_cnpj' => $cpfCnpj,
            'telefone1' => '62999999999',
            'data_adesao' => '2026-07-23',
            'dia_pagamento' => 10,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function veiculo(
        Cliente $cliente,
        int $statusId,
        int $tipoVeiculoId,
        array $extra,
    ): Veiculo {
        return Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'status_rastreador_id' => $statusId,
            'tipo_veiculo_id' => $tipoVeiculoId,
            'cor' => 'Prata',
            'ano' => '2025',
            ...$extra,
        ]);
    }
}
