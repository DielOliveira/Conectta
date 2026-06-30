<?php

namespace Database\Seeders;

use App\Models\Chip;
use App\Models\Cliente;
use App\Models\Rastreador;
use App\Models\StatusCliente;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\TipoVeiculo;
use App\Models\Veiculo;
use Illuminate\Database\Seeder;

class DemoBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $ativoId = StatusRastreador::query()->where('label', 'Ativo')->value('id');
        $canceladoId = StatusRastreador::query()->where('label', 'Cancelado')->value('id');
        $disponivelId = StatusRastreador::query()->where('label', 'Disponivel')->value('id');
        $clienteInativoId = StatusCliente::query()->where('label', 'Inativo')->value('id');
        $carroId = TipoVeiculo::query()->where('label', 'Carro')->value('id');
        $motoId = TipoVeiculo::query()->where('label', 'Moto')->value('id') ?? $carroId;

        $tecnicoOutros = Tecnico::query()->firstOrCreate(
            ['nome' => 'Outros'],
            ['is_ativo' => true],
        );

        $tecnicoRomeu = Tecnico::query()->firstOrCreate(
            ['nome' => 'Romeu'],
            ['is_ativo' => true],
        );

        $clientes = [
            [
                'nome' => 'Alfa Monitoramento',
                'cpf_cnpj' => '11222333000181',
                'telefone1' => '62990010001',
                'data_adesao' => '2024-01-15',
            ],
            [
                'nome' => 'Beta Transportes',
                'cpf_cnpj' => '22333444000102',
                'telefone1' => '62990020002',
                'data_adesao' => '2024-03-20',
            ],
            [
                'nome' => 'Carvalho Logistica',
                'cpf_cnpj' => '33444555000115',
                'telefone1' => '62990030003',
                'data_adesao' => '2024-05-10',
            ],
        ];

        $veiculos = [
            ['veiculo' => 'Toyota / Corolla', 'tipo_id' => $carroId, 'cor' => 'Prata', 'ano' => '20/21'],
            ['veiculo' => 'Honda / CG 160 Fan', 'tipo_id' => $motoId, 'cor' => 'Vermelha', 'ano' => '22/23'],
            ['veiculo' => 'Ford / Ecosport', 'tipo_id' => $carroId, 'cor' => 'Branca', 'ano' => '19/20'],
        ];

        foreach ($clientes as $clienteIndex => $clienteData) {
            $cliente = Cliente::query()->firstOrCreate(
                ['cpf_cnpj' => $clienteData['cpf_cnpj']],
                [
                    'status_cliente_id' => $clienteInativoId,
                    'nome' => $clienteData['nome'],
                    'telefone1' => $clienteData['telefone1'],
                    'data_adesao' => $clienteData['data_adesao'],
                    'dia_pagamento' => 10 + $clienteIndex,
                ],
            );

            foreach ($veiculos as $veiculoIndex => $veiculoData) {
                $sequence = ($clienteIndex * 3) + $veiculoIndex + 1;
                $imei = '990000000000' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
                $iccid = '895500000000000000' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
                $isCancelado = $veiculoIndex === 2;

                $rastreador = Rastreador::query()->firstOrCreate(
                    ['imei' => $imei],
                    [
                        'modelo' => 'Demo',
                        'tecnico_id' => $tecnicoOutros->id,
                        'status_rastreador_id' => $disponivelId,
                        'is_estoque' => true,
                    ],
                );

                $chip = Chip::query()->firstOrCreate(
                    ['iccid' => $iccid],
                    [
                        'fornecedor' => 'Demo',
                        'operadora' => 'Operadora Demo',
                        'tecnico_id' => $tecnicoOutros->id,
                    ],
                );

                Veiculo::query()->updateOrCreate(
                    ['placa' => 'DEM-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT)],
                    [
                        'cliente_id' => $cliente->id,
                        'status_rastreador_id' => $isCancelado ? $canceladoId : $ativoId,
                        'tipo_veiculo_id' => $veiculoData['tipo_id'],
                        'rastreador_id' => $rastreador->id,
                        'chip_id' => $chip->id,
                        'tecnico_remocao_id' => $isCancelado ? $tecnicoRomeu->id : null,
                        'veiculo' => $veiculoData['veiculo'],
                        'cor' => $veiculoData['cor'],
                        'ano' => $veiculoData['ano'],
                        'data_instalacao' => now()->subMonths(12 - $sequence)->toDateString(),
                        'data_retirada' => $isCancelado ? now()->subDays($sequence)->toDateString() : null,
                        'login' => 'demo' . $sequence,
                        'senha' => 'demo@' . $sequence,
                        'associado' => $cliente->nome,
                        'contato' => $cliente->telefone1,
                    ],
                );
            }

            $cliente->syncStatusFromVeiculos();
        }
    }
}
