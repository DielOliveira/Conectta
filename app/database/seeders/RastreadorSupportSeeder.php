<?php

namespace Database\Seeders;

use App\Models\Chip;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\TipoVeiculo;
use Illuminate\Database\Seeder;

class RastreadorSupportSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedOrdered(TipoVeiculo::class, [
            ['label' => 'Carro', 'order' => 1],
            ['label' => 'Moto', 'order' => 2],
            ['label' => 'Caminhonete', 'order' => 3],
            ['label' => 'Camioneta', 'order' => 4],
            ['label' => 'Caminhao', 'order' => 5],
            ['label' => 'Onibus', 'order' => 6],
            ['label' => 'Maquinas Agricolas', 'order' => 7],
            ['label' => 'Nauticos', 'order' => 8],
            ['label' => 'Maquina Industrial', 'order' => 9],
            ['label' => 'Computador', 'order' => 10],
            ['label' => 'Nao definido', 'order' => 11],
        ]);

        $outros = Tecnico::query()->firstOrCreate(
            ['nome' => 'Outros'],
            ['is_ativo' => true],
        );

        $romeu = Tecnico::query()->firstOrCreate(
            ['nome' => 'Romeu'],
            ['is_ativo' => true],
        );

        $disponivelId = StatusRastreador::query()
            ->where('label', 'Disponivel')
            ->value('id');

        Chip::query()->firstOrCreate(
            ['iccid' => '9.9874-8737'],
            ['fornecedor' => 'Seed', 'operadora' => 'Operadora', 'tecnico_id' => $outros->id, 'status_rastreador_id' => $disponivelId],
        );

        Chip::query()->firstOrCreate(
            ['iccid' => '8.7654-3210'],
            ['fornecedor' => 'Seed', 'operadora' => 'Operadora', 'tecnico_id' => $romeu->id, 'status_rastreador_id' => $disponivelId],
        );

        Rastreador::query()->firstOrCreate(
            ['imei' => '862292050969070'],
            ['modelo' => 'Outros', 'tecnico_id' => $outros->id, 'status_rastreador_id' => $disponivelId, 'is_estoque' => true],
        );

        Rastreador::query()->firstOrCreate(
            ['imei' => '352503092763322'],
            ['modelo' => 'Outros', 'tecnico_id' => $romeu->id, 'status_rastreador_id' => $disponivelId, 'is_estoque' => true],
        );
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @param array<int, array{label: string, order: int}> $records
     */
    private function seedOrdered(string $model, array $records): void
    {
        foreach ($records as $record) {
            $model::query()->updateOrCreate(
                ['label' => $record['label']],
                ['order' => $record['order'], 'is_active' => true],
            );
        }
    }
}
