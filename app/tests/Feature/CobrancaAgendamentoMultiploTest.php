<?php

namespace Tests\Feature;

use App\Models\CobrancaAgendamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobrancaAgendamentoMultiploTest extends TestCase
{
    use RefreshDatabase;

    public function test_permite_varios_agendamentos_do_mesmo_tipo(): void
    {
        CobrancaAgendamento::query()->create([
            'tipo' => 'atraso_2',
            'ativo' => false,
            'horario' => '18:00:00',
            'dias_semana' => [1],
            'dry_run' => true,
            'enviar_whatsapp' => false,
        ]);

        $this->assertDatabaseCount('cobranca_agendamentos', 17);
        $this->assertSame(3, CobrancaAgendamento::query()->where('tipo', 'atraso_2')->count());
    }

    public function test_migration_separa_dias_uteis_e_fim_de_semana_preservando_a_progressao(): void
    {
        $diasUteis = CobrancaAgendamento::query()
            ->whereJsonContains('dias_semana', 1)
            ->whereJsonDoesntContain('dias_semana', 0)
            ->get()
            ->keyBy('tipo');

        $fimDeSemana = CobrancaAgendamento::query()
            ->whereJsonContains('dias_semana', 0)
            ->whereJsonContains('dias_semana', 6)
            ->get()
            ->keyBy('tipo');

        $this->assertCount(8, $diasUteis);
        $this->assertCount(8, $fimDeSemana);
        $this->assertSame('09:30:00', $diasUteis['atraso_2']->horario);
        $this->assertSame('08:00:00', $diasUteis['boleto_7_dias']->horario);
        $this->assertSame('09:00:00', $fimDeSemana['atraso_2']->horario);
        $this->assertSame('09:25:00', $fimDeSemana['atraso_15']->horario);
        $this->assertSame('09:50:00', $fimDeSemana['lembrete_vencimento']->horario);
        $this->assertSame('09:55:00', $fimDeSemana['boleto_7_dias']->horario);
    }
}
