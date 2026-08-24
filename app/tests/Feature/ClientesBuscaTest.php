<?php

namespace Tests\Feature;

use App\Filament\Resources\Clientes\Pages\ListClientes;
use App\Models\Cliente;
use App\Models\StatusCliente;
use App\Models\User;
use Database\Seeders\ClienteSupportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ClientesBuscaTest extends TestCase
{
    use RefreshDatabase;

    public function test_busca_clientes_por_telefone_principal_e_secundario_sem_alterar_o_texto_pesquisado(): void
    {
        $this->seed(ClienteSupportSeeder::class);
        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-busca-clientes@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        $statusAtivoId = StatusCliente::query()->where('label', 'Ativo')->value('id');
        $telefonePrincipal = $this->createCliente($statusAtivoId, 'Cliente principal', '62987654321', null);
        $telefoneSecundario = $this->createCliente($statusAtivoId, 'Cliente secundário', '62911111111', '62912345678');
        $nomeComCaracteres = $this->createCliente($statusAtivoId, 'João & Filhos', '62922222222', null);

        Livewire::test(ListClientes::class)
            ->set('clientePesquisa', '987654')
            ->assertCanSeeTableRecords([$telefonePrincipal])
            ->assertCanNotSeeTableRecords([$telefoneSecundario, $nomeComCaracteres])
            ->set('clientePesquisa', '123456')
            ->assertCanSeeTableRecords([$telefoneSecundario])
            ->assertCanNotSeeTableRecords([$telefonePrincipal, $nomeComCaracteres])
            ->set('clientePesquisa', 'João &')
            ->assertCanSeeTableRecords([$nomeComCaracteres])
            ->assertCanNotSeeTableRecords([$telefonePrincipal, $telefoneSecundario]);
    }

    private function createCliente(int $statusClienteId, string $nome, string $telefone1, ?string $telefone2): Cliente
    {
        return Cliente::query()->create([
            'status_cliente_id' => $statusClienteId,
            'nome' => $nome,
            'cpf_cnpj' => fake()->unique()->numerify('###########'),
            'telefone1' => $telefone1,
            'telefone2' => $telefone2,
            'data_adesao' => '2026-08-24',
            'dia_pagamento' => 10,
        ]);
    }
}
