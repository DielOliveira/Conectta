<?php

namespace Tests\Feature;

use App\Filament\Resources\Clientes\Pages\EditCliente;
use App\Models\Cliente;
use App\Models\StatusCliente;
use App\Models\User;
use Database\Seeders\ClienteSupportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ClienteNascimentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_displays_the_birth_date_in_brazilian_format(): void
    {
        $this->seed(ClienteSupportSeeder::class);
        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-nascimento@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        $cliente = Cliente::query()->create([
            'status_cliente_id' => StatusCliente::query()->where('label', 'Inativo')->value('id'),
            'nome' => 'Valmir Siqueira Freitas',
            'cpf_cnpj' => '52998224725',
            'nascimento' => '1962-09-24',
            'telefone1' => '62999999999',
            'data_adesao' => '2026-07-30',
            'dia_pagamento' => 10,
        ]);

        Livewire::test(EditCliente::class, ['record' => $cliente->getRouteKey()])
            ->assertFormSet([
                'nascimento' => '24/09/1962',
            ]);
    }
}
