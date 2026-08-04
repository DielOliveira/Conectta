<?php

namespace Tests\Feature;

use App\Enums\OrdemServicoStatus;
use App\Filament\Resources\Disponibilidades\DisponibilidadeResource;
use App\Models\Cliente;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use App\Services\OrdemServico\OrdemServicoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrdemServicoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_consegue_abrir_o_cadastro_de_disponibilidade(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(DisponibilidadeResource::getUrl('create'))
            ->assertOk();
    }

    public function test_cria_agenda_e_executa_fluxo_inicial_com_token_imprevisivel(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $service = app(OrdemServicoService::class);
        $ordem = $service->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];

        $this->assertSame(1, $ordem->numero);
        $this->assertSame(OrdemServicoStatus::ABERTA, $ordem->status);
        $this->assertDatabaseHas('ordem_servico_historicos', ['ordem_servico_id' => $ordem->id, 'evento' => 'abertura']);

        $disponibilidade = app(OrdemServicoAgendaService::class)->criarDisponibilidade($tecnico->id, '2026-08-04', '08:00', '10:00');
        $this->assertSame('08:00:00', $disponibilidade->hora_inicio);
        $this->assertSame('10:00:00', $disponibilidade->hora_fim);
        $blocos = app(OrdemServicoAgendaService::class)->blocos($disponibilidade);
        $this->assertSame(['08:00', '08:40', '09:20'], $blocos->map->format('H:i')->all());

        $resultado = $service->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 08:40'), $operador);
        $this->assertSame(64, strlen($resultado['token']));
        $this->assertNotSame($resultado['token'], $resultado['ordem']->token_hash);
        $this->assertSame($resultado['ordem']->id, $service->porToken($resultado['token'])->id);
        $this->assertDatabaseHas('ordem_servico_notificacoes', ['ordem_servico_id' => $ordem->id, 'evento' => 'atribuicao']);

        $service->aceitar($resultado['ordem']);
        $service->iniciar($resultado['ordem']->fresh(), -16.6869, -49.2648);
        $this->assertSame(OrdemServicoStatus::EM_ATENDIMENTO, $resultado['ordem']->fresh()->status);
    }

    public function test_impede_duas_ordens_ativas_para_o_mesmo_veiculo(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        $service = app(OrdemServicoService::class);
        $service->criar($this->dadosOrdem($cliente, $veiculo), $operador);

        $this->expectException(ValidationException::class);
        $service->criar($this->dadosOrdem($cliente, $veiculo), $operador);
    }

    public function test_rejeicao_libera_a_agenda_e_invalida_o_token(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $service = app(OrdemServicoService::class);
        $ordem = $service->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $disponibilidade = app(OrdemServicoAgendaService::class)->criarDisponibilidade($tecnico->id, '2026-08-04', '08:00', '09:00');
        $resultado = $service->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 08:00'), $operador);

        $service->rejeitar($resultado['ordem'], 'Não conseguirei atender nessa data.');
        $ordem = $ordem->fresh();
        $this->assertSame(OrdemServicoStatus::ABERTA, $ordem->status);
        $this->assertNull($ordem->tecnico_id);
        $this->assertNull($ordem->token_hash);
        $this->assertCount(1, app(OrdemServicoAgendaService::class)->blocos($disponibilidade));
    }

    public function test_permite_editar_intervalo_livre_e_bloqueia_remocao_de_bloco_ocupado(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $agenda = app(OrdemServicoAgendaService::class);
        $disponibilidade = $agenda->criarDisponibilidade($tecnico->id, '2026-08-04', '08:00', '10:00');
        $disponibilidade = $agenda->atualizarDisponibilidade($disponibilidade, $tecnico->id, '2026-08-04', '08:00', '11:00');
        $this->assertSame('11:00:00', $disponibilidade->hora_fim);

        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        app(OrdemServicoService::class)->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 09:20'), $operador);

        $this->expectException(ValidationException::class);
        $agenda->atualizarDisponibilidade($disponibilidade, $tecnico->id, '2026-08-04', '08:00', '09:00');
    }

    private function cenarioBase(): array
    {
        $operador = User::factory()->create(['is_admin' => true]);
        StatusRastreador::query()->create(['label' => 'Disponivel', 'order' => 1, 'is_active' => true]);
        StatusRastreador::query()->create(['label' => 'Ativo', 'order' => 2, 'is_active' => true]);
        StatusRastreador::query()->create(['label' => 'Cancelado', 'order' => 3, 'is_active' => true]);
        $cliente = Cliente::query()->create(['nome' => 'Cliente OS', 'cpf_cnpj' => fake()->unique()->numerify('###########'), 'telefone1' => '62999999999', 'data_adesao' => '2026-01-01', 'dia_pagamento' => 10]);
        $veiculo = Veiculo::query()->create(['cliente_id' => $cliente->id, 'veiculo' => 'Automóvel', 'placa' => 'OSX-0001']);
        $tecnico = Tecnico::query()->create(['nome' => 'Técnico OS', 'telefone' => '62988888888', 'is_ativo' => true]);

        return [$operador, $cliente, $veiculo, $tecnico];
    }

    private function dadosOrdem(Cliente $cliente, Veiculo $veiculo): array
    {
        return ['tipo' => 'instalacao', 'cliente_id' => $cliente->id, 'veiculo_id' => $veiculo->id,
            'atendimento_desejado_em' => '2026-08-04 08:00:00', 'endereco' => 'Rua de Teste, 1',
            'descricao' => 'Instalar equipamento', 'notificar_cliente' => false];
    }
}
