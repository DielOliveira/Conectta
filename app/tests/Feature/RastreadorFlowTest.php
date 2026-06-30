<?php

namespace Tests\Feature;

use App\Models\Chip;
use App\Models\Cliente;
use App\Models\Rastreador;
use App\Models\StatusCliente;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\TipoVeiculo;
use App\Models\Veiculo;
use Database\Seeders\ClienteSupportSeeder;
use Database\Seeders\RastreadorSupportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class RastreadorFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_vehicle_turns_client_active(): void
    {
        $this->seedSupportData();

        $cliente = $this->cliente();

        Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'status_rastreador_id' => $this->statusRastreadorId('Ativo'),
            'tipo_veiculo_id' => TipoVeiculo::query()->where('label', 'Carro')->value('id'),
            'rastreador_id' => Rastreador::query()->first()->id,
            'chip_id' => Chip::query()->first()->id,
            'veiculo' => 'Toyota / Yaris',
            'placa' => 'PQY-0719',
            'cor' => 'Branca',
            'ano' => '19/20',
        ]);

        $this->assertSame('Ativo', $cliente->refresh()->statusCliente->label);
    }

    public function test_duplicate_active_imei_is_blocked(): void
    {
        $this->seedSupportData();

        $rastreador = Rastreador::query()->first();
        $chip = Chip::query()->first();

        Veiculo::query()->create([
            'cliente_id' => $this->cliente('Cliente 1', '52998224725')->id,
            'status_rastreador_id' => $this->statusRastreadorId('Ativo'),
            'tipo_veiculo_id' => TipoVeiculo::query()->where('label', 'Carro')->value('id'),
            'rastreador_id' => $rastreador->id,
            'chip_id' => $chip->id,
            'veiculo' => 'Toyota / Yaris',
            'placa' => 'PQY-0719',
            'cor' => 'Branca',
            'ano' => '19/20',
        ]);

        $this->expectException(ValidationException::class);

        Veiculo::query()->create([
            'cliente_id' => $this->cliente('Cliente 2', '04252011000110')->id,
            'status_rastreador_id' => $this->statusRastreadorId('Ativo'),
            'tipo_veiculo_id' => TipoVeiculo::query()->where('label', 'Carro')->value('id'),
            'rastreador_id' => $rastreador->id,
            'veiculo' => 'Ford / Ecosport',
            'placa' => 'QUS-7F74',
            'cor' => 'Preta',
            'ano' => '20/21',
        ]);
    }

    public function test_cancelled_vehicle_requires_removal_data_and_releases_tracker(): void
    {
        $this->seedSupportData();

        $cliente = $this->cliente();
        $rastreador = Rastreador::query()->first();
        $tecnicoRemocao = Tecnico::query()->where('nome', 'Romeu')->first();

        $veiculo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'status_rastreador_id' => $this->statusRastreadorId('Ativo'),
            'tipo_veiculo_id' => TipoVeiculo::query()->where('label', 'Carro')->value('id'),
            'rastreador_id' => $rastreador->id,
            'chip_id' => Chip::query()->first()->id,
            'veiculo' => 'Toyota / Yaris',
            'placa' => 'PQY-0719',
            'cor' => 'Branca',
            'ano' => '19/20',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $veiculo->update(['status_rastreador_id' => $this->statusRastreadorId('Cancelado')]);
        } finally {
            $veiculo->update([
                'status_rastreador_id' => $this->statusRastreadorId('Cancelado'),
                'data_retirada' => '2026-06-22',
                'tecnico_remocao_id' => $tecnicoRemocao->id,
            ]);

            $this->assertSame('Inativo', $cliente->refresh()->statusCliente->label);
            $this->assertSame('Disponivel', $rastreador->refresh()->statusRastreador->label);
            $this->assertSame($tecnicoRemocao->id, $rastreador->tecnico_id);
        }
    }

    private function seedSupportData(): void
    {
        $this->seed(ClienteSupportSeeder::class);
        $this->seed(RastreadorSupportSeeder::class);
    }

    private function cliente(string $nome = 'Cliente Teste', string $cpfCnpj = '52998224725'): Cliente
    {
        return Cliente::query()->create([
            'status_cliente_id' => StatusCliente::query()->where('label', 'Inativo')->value('id'),
            'nome' => $nome,
            'cpf_cnpj' => $cpfCnpj,
            'telefone1' => '62999999999',
            'data_adesao' => '2026-06-22',
            'dia_pagamento' => 10,
        ]);
    }

    private function statusRastreadorId(string $label): int
    {
        return StatusRastreador::query()
            ->where('label', $label)
            ->value('id');
    }
}
