<?php

namespace Tests\Feature;

use App\Filament\Resources\Clientes\Pages\CreateCliente;
use App\Models\Cliente;
use App\Models\StatusCliente;
use App\Models\User;
use Database\Seeders\ClienteSupportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ClienteCpfCnpjUnicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_accepts_legacy_clients_with_the_same_cpf_cnpj(): void
    {
        $this->seed(ClienteSupportSeeder::class);

        $this->createCliente('Cliente legado A');
        $this->createCliente('Cliente legado B');

        $this->assertSame(
            2,
            Cliente::query()->where('cpf_cnpj', '52998224725')->count(),
        );
    }

    public function test_client_creation_form_blocks_a_repeated_cpf_cnpj(): void
    {
        $this->seed(ClienteSupportSeeder::class);
        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-clientes@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));
        $this->createCliente('Cliente existente');

        Livewire::test(CreateCliente::class)
            ->fillForm([
                'nome' => 'Cliente novo',
                'cpf_cnpj' => '52998224725',
                'telefone1_pais' => 'BR',
                'telefone1' => '62999999999',
                'data_adesao' => '2026-07-24',
                'dia_pagamento' => 10,
            ])
            ->call('create')
            ->assertHasFormErrors(['cpf_cnpj' => 'unique']);

        $this->assertSame(
            1,
            Cliente::query()->where('cpf_cnpj', '52998224725')->count(),
        );
    }

    private function createCliente(string $nome): Cliente
    {
        return Cliente::query()->create([
            'status_cliente_id' => StatusCliente::query()->where('label', 'Inativo')->value('id'),
            'nome' => $nome,
            'cpf_cnpj' => '52998224725',
            'telefone1' => '62999999999',
            'data_adesao' => '2026-07-24',
            'dia_pagamento' => 10,
        ]);
    }
}
