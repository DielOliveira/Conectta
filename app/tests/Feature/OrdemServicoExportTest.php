<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdensServico\Pages\ListOrdensServico;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\StatusRastreador;
use App\Models\TipoVeiculo;
use App\Models\User;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrdemServicoExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_includes_vehicle_type_and_service_description(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        StatusRastreador::query()->create(['label' => 'Ativo', 'order' => 1, 'is_active' => true]);
        StatusRastreador::query()->create(['label' => 'Cancelado', 'order' => 2, 'is_active' => true]);
        $tipoVeiculo = TipoVeiculo::query()->create([
            'label' => 'Caminhonete',
            'order' => 1,
            'is_active' => true,
        ]);
        $cliente = Cliente::query()->create([
            'nome' => 'Cliente da exportação',
            'cpf_cnpj' => '52998224725',
            'telefone1' => '62999999999',
            'data_adesao' => '2026-01-01',
            'dia_pagamento' => 10,
        ]);
        $veiculo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'tipo_veiculo_id' => $tipoVeiculo->id,
            'veiculo' => 'Toyota / Hilux',
            'placa' => 'EXP-1A23',
        ]);
        OrdemServico::query()->create([
            'numero' => 41,
            'tipo' => 'instalacao',
            'status' => 'aberta',
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'endereco' => 'Rua de Teste, 1',
            'descricao' => 'Instalar rastreador no veículo',
            'notificar_cliente' => false,
        ]);

        $component = Livewire::test(ListOrdensServico::class)
            ->call('exportarCsv')
            ->assertFileDownloaded();

        $content = base64_decode(data_get($component->effects, 'download.content'));
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, substr($content, 3));
        rewind($handle);
        $headers = fgetcsv($handle, separator: ';');
        $row = fgetcsv($handle, separator: ';');
        fclose($handle);

        $this->assertSame([
            'OS',
            'Cliente',
            'Placa',
            'Tipo do veículo',
            'Tipo',
            'Motivo ou descrição do serviço',
            'Status',
            'Técnico',
            'Atendimento',
        ], $headers);
        $this->assertSame('Caminhonete', $row[3]);
        $this->assertSame('Instalação', $row[4]);
        $this->assertSame('Instalar rastreador no veículo', $row[5]);
    }
}
