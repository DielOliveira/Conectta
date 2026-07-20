<?php

namespace Tests\Feature;

use App\Filament\Pages\Financeiro;
use App\Models\Cliente;
use App\Models\Lancamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class FinanceiroLancamentoModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_does_not_persist_suggested_lancamento_date_without_valor_efetivado(): void
    {
        $cliente = $this->cliente();

        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        Livewire::test(Financeiro::class)
            ->set('modalClienteId', $cliente->id)
            ->set('modalMes', 7)
            ->set('modalAno', 2026)
            ->set('modalDataLancamento', '2026-07-20')
            ->set('modalValorPlanejado', '150,00')
            ->set('modalValorEfetivado', '')
            ->call('salvarLancamentoModal')
            ->assertHasNoErrors();

        $lancamento = Lancamento::query()->sole();

        $this->assertNull($lancamento->data_lancamento);
        $this->assertNull($lancamento->valor_efetivado);
    }

    public function test_persists_lancamento_date_when_valor_efetivado_is_filled(): void
    {
        $cliente = $this->cliente('Cliente Pago', '04252011000110');

        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-pago@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        Livewire::test(Financeiro::class)
            ->set('modalClienteId', $cliente->id)
            ->set('modalMes', 7)
            ->set('modalAno', 2026)
            ->set('modalDataLancamento', '2026-07-20')
            ->set('modalValorPlanejado', '150,00')
            ->set('modalValorEfetivado', '150,00')
            ->call('salvarLancamentoModal')
            ->assertHasNoErrors();

        $lancamento = Lancamento::query()->sole();

        $this->assertSame('2026-07-20', $lancamento->data_lancamento?->toDateString());
        $this->assertSame('150.00', $lancamento->valor_efetivado);
    }

    private function cliente(string $nome = 'Cliente Teste', string $cpfCnpj = '52998224725'): Cliente
    {
        return Cliente::query()->create([
            'nome' => $nome,
            'cpf_cnpj' => $cpfCnpj,
            'telefone1' => '62999999999',
            'data_adesao' => '2026-06-22',
            'dia_pagamento' => 10,
        ]);
    }
}
