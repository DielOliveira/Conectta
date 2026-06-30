<?php

namespace Database\Seeders;

use App\Models\ClienteOrigem;
use App\Models\Estado;
use App\Models\StatusCliente;
use App\Models\StatusContrato;
use App\Models\StatusRastreador;
use App\Models\Vendedor;
use Illuminate\Database\Seeder;

class ClienteSupportSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedOrdered(StatusCliente::class, [
            ['label' => 'Ativo', 'order' => 1],
            ['label' => 'Inativo', 'order' => 2],
        ]);

        $this->seedOrdered(StatusContrato::class, [
            ['label' => 'Assinado', 'order' => 1],
            ['label' => 'Rejeitado', 'order' => 2],
            ['label' => 'Enviado', 'order' => 3],
            ['label' => 'Nao Enviado', 'order' => 4],
        ]);

        $this->seedOrdered(ClienteOrigem::class, [
            ['label' => 'Meta', 'order' => 1],
            ['label' => 'Indicacao', 'order' => 2],
            ['label' => 'ADS', 'order' => 3],
            ['label' => 'Retencao', 'order' => 4],
            ['label' => 'Conectta', 'order' => 5],
            ['label' => 'Venda Direta', 'order' => 6],
            ['label' => 'Outros', 'order' => 7],
        ]);

        $this->seedOrdered(StatusRastreador::class, [
            ['label' => 'Ativo', 'order' => 1],
            ['label' => 'Cancelado', 'order' => 2],
            ['label' => 'Lixo', 'order' => 3],
            ['label' => 'Disponivel', 'order' => 4],
        ]);

        $this->seedEstados();

        Vendedor::query()->firstOrCreate(
            ['nome' => 'Ribeiro'],
            ['numr' => 1],
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

    private function seedEstados(): void
    {
        $estados = [
            ['label' => 'Goias', 'order' => 1],
            ['label' => 'Distrito Federal', 'order' => 2],
            ['label' => 'Mato Grosso', 'order' => 3],
            ['label' => 'Mato Grosso do Sul', 'order' => 4],
            ['label' => 'Alagoas', 'order' => 5],
            ['label' => 'Bahia', 'order' => 6],
            ['label' => 'Ceara', 'order' => 7],
            ['label' => 'Maranhao', 'order' => 8],
            ['label' => 'Paraiba', 'order' => 9],
            ['label' => 'Pernambuco', 'order' => 10],
            ['label' => 'Piaui', 'order' => 11],
            ['label' => 'Rio Grande do Norte', 'order' => 12],
            ['label' => 'Sergipe', 'order' => 13],
            ['label' => 'Acre', 'order' => 14],
            ['label' => 'Amapa', 'order' => 15],
            ['label' => 'Amazonas', 'order' => 16],
            ['label' => 'Para', 'order' => 17],
            ['label' => 'Rondonia', 'order' => 18],
            ['label' => 'Roraima', 'order' => 19],
            ['label' => 'Tocantis', 'order' => 20],
            ['label' => 'Espirito Santo', 'order' => 21],
            ['label' => 'Minas Gerais', 'order' => 22],
            ['label' => 'RiodeJaneiro', 'order' => 23],
            ['label' => 'SaoPaulo', 'order' => 24],
            ['label' => 'Parana', 'order' => 25],
            ['label' => 'Rio Grande do Sul', 'order' => 26],
            ['label' => 'Santa Catarina', 'order' => 27],
        ];

        foreach ($estados as $estado) {
            Estado::query()->updateOrCreate(
                ['label' => $estado['label']],
                ['order' => $estado['order'], 'is_active' => true],
            );
        }
    }
}
