<?php

namespace Tests\Feature;

use App\Filament\Pages\Financeiro;
use App\Models\Cliente;
use App\Models\CobrancaEnvio;
use App\Models\Lancamento;
use App\Models\User;
use App\Services\Cobranca\CobrancaAutomaticaService;
use App\Services\Lytex\LytexInvoiceService;
use Carbon\CarbonImmutable;
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

    public function test_does_not_require_lancamento_date_when_valor_efetivado_is_zero(): void
    {
        $cliente = $this->cliente('Cliente Zero', '39053344705');

        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-zero@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        Livewire::test(Financeiro::class)
            ->set('modalClienteId', $cliente->id)
            ->set('modalMes', 7)
            ->set('modalAno', 2026)
            ->set('modalDataLancamento', '')
            ->set('modalValorPlanejado', '150,00')
            ->set('modalValorEfetivado', '0,00')
            ->call('salvarLancamentoModal')
            ->assertHasNoErrors();

        $lancamento = Lancamento::query()->sole();

        $this->assertNull($lancamento->data_lancamento);
        $this->assertSame('0.00', $lancamento->valor_efetivado);
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

    public function test_uses_current_date_when_valor_efetivado_is_filled_without_lancamento_date(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        $cliente = $this->cliente('Cliente Data Atual', '58345114000110');

        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-data-atual@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        Livewire::test(Financeiro::class)
            ->set('modalClienteId', $cliente->id)
            ->set('modalMes', 7)
            ->set('modalAno', 2026)
            ->set('modalDataLancamento', '')
            ->set('modalValorPlanejado', '150,00')
            ->set('modalValorEfetivado', '150,00')
            ->call('salvarLancamentoModal')
            ->assertHasNoErrors();

        $lancamento = Lancamento::query()->sole();

        $this->assertSame('2026-07-20', $lancamento->data_lancamento?->toDateString());
        $this->assertSame('150.00', $lancamento->valor_efetivado);
    }

    public function test_repeated_stale_create_request_updates_existing_planned_lancamento(): void
    {
        $cliente = $this->cliente('Cliente Idempotente', '84762979000191');

        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-idempotente@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        foreach (['150,00', '175,00'] as $valor) {
            Livewire::test(Financeiro::class)
                ->set('modalClienteId', $cliente->id)
                ->set('modalMes', 7)
                ->set('modalAno', 2026)
                ->set('modalValorPlanejado', $valor)
                ->call('salvarLancamentoModal')
                ->assertHasNoErrors();
        }

        $lancamento = Lancamento::query()->sole();

        $this->assertSame('175.00', $lancamento->valor_planejado);
    }

    public function test_generating_boleto_from_popup_marks_lancamento_as_lytex(): void
    {
        $this->travelTo('2026-07-01 10:00:00');

        $cliente = $this->cliente('Cliente Boleto Popup', '39053344705');
        $cliente->update(['email' => 'boleto-popup@example.com']);

        $lancamento = Lancamento::query()->create([
            'cliente_id' => $cliente->id,
            'mes_referencia' => 7,
            'ano_referencia' => 2026,
            'valor_planejado' => 150,
        ]);

        $this->actingAs(User::query()->create([
            'name' => 'Admin Boleto Popup',
            'email' => 'admin-boleto-popup@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        $lytex = $this->mock(LytexInvoiceService::class);
        $lytex->shouldReceive('criarFatura')
            ->once()
            ->andReturn([
                '_id' => 'invoice-popup-1',
                '_clientId' => 'cliente-lytex-1',
                '_hashId' => 'hash-popup-1',
                'totalValue' => 15000,
                'status' => 'waiting_payment',
                'dueDate' => '2026-07-10T23:59:59.000Z',
                'createdAt' => '2026-07-01T13:00:00.000Z',
                'updatedAt' => '2026-07-01T13:00:00.000Z',
            ]);

        Livewire::test(Financeiro::class)
            ->call('abrirLancamento', $cliente->id, 7, 2026)
            ->assertSet('modalLancamentoId', $lancamento->id)
            ->call('gerarBoleto')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('lancamentos', [
            'id' => $lancamento->id,
            'numero_boleto' => 'Lytex',
        ]);
        $this->assertDatabaseHas('invoices', [
            'fatura_id' => 'invoice-popup-1',
            'lancamento_id' => $lancamento->id,
        ]);
    }

    public function test_automatic_billing_blocks_duplicated_planned_lancamentos(): void
    {
        $cliente = $this->cliente('Cliente Duplicado', '11222333000181');
        $cliente->update([
            'email' => 'duplicado@example.com',
            'dia_pagamento' => 30,
        ]);

        foreach ([150, 150] as $valor) {
            Lancamento::query()->create([
                'cliente_id' => $cliente->id,
                'mes_referencia' => 7,
                'ano_referencia' => 2026,
                'valor_planejado' => $valor,
            ]);
        }

        $resultado = app(CobrancaAutomaticaService::class)->processar(
            CarbonImmutable::create(2026, 7, 23),
            dryRun: false,
            tipo: CobrancaAutomaticaService::BOLETO_7_DIAS,
        );

        $this->assertSame(2, $resultado['total_erros']);
        $this->assertSame(0, $resultado['total_enviados']);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame(2, CobrancaEnvio::query()->where('status', 'erro')->count());
        $this->assertTrue(CobrancaEnvio::query()->get()->every(
            fn (CobrancaEnvio $envio): bool => str_contains((string) $envio->erro, 'multiplos lancamentos planejados'),
        ));
    }

    public function test_popup_prioritizes_active_lancamento_over_neutralized_history(): void
    {
        $cliente = $this->cliente('Cliente Saneado', '72993876000103');
        $neutralizado = Lancamento::query()->create([
            'cliente_id' => $cliente->id,
            'mes_referencia' => 7,
            'ano_referencia' => 2026,
            'valor_planejado' => null,
            'observacao' => 'Neutralizado em saneamento de duplicidade.',
            'invalidado_em' => now(),
            'motivo_invalidacao' => 'Duplicidade de boleto.',
        ]);
        $ativo = Lancamento::query()->create([
            'cliente_id' => $cliente->id,
            'mes_referencia' => 7,
            'ano_referencia' => 2026,
            'valor_planejado' => 180,
        ]);

        $this->actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-popup@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        Livewire::test(Financeiro::class)
            ->call('abrirLancamento', $cliente->id, 7, 2026)
            ->assertSet('modalLancamentoId', $ativo->id)
            ->assertSet('modalValorPlanejado', '180,00')
            ->assertSet('modalObservacao', '');

        $this->assertNotSame($neutralizado->id, $ativo->id);
    }

    public function test_financeiro_ignores_invalidated_lancamento_in_monthly_values_and_search(): void
    {
        $cliente = $this->cliente('Cliente Com Historico Invalidado', '99887766000155');

        Lancamento::query()->create([
            'cliente_id' => $cliente->id,
            'mes_referencia' => 7,
            'ano_referencia' => 2026,
            'valor_planejado' => 180,
            'observacao' => 'Observacao valida',
        ]);
        Lancamento::query()->create([
            'cliente_id' => $cliente->id,
            'mes_referencia' => 7,
            'ano_referencia' => 2026,
            'valor_planejado' => null,
            'observacao' => 'Neutralizado em saneamento de duplicidade',
            'invalidado_em' => now(),
        ]);

        $this->actingAs(User::query()->create([
            'name' => 'Admin Lista',
            'email' => 'admin-lista@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]));

        Livewire::test(Financeiro::class)
            ->set('mesBase', 7)
            ->set('anoBase', 2026)
            ->set('consultaMes1', 'Neutralizado em saneamento')
            ->assertDontSee($cliente->nome);
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
