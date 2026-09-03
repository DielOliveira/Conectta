<?php

namespace Tests\Feature;

use App\Enums\OrdemServicoStatus;
use App\Filament\Resources\Rastreadores\Pages\EditRastreador;
use App\Models\AuditLog;
use App\Models\Chip;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Rastreador;
use App\Models\StatusCliente;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\Estoque\EquipamentoStatusWorkflow;
use App\Services\Veiculo\VeiculoRetencaoService;
use Database\Seeders\ClienteSupportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class VeiculoRetencaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-03 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_retencao_preserva_vinculo_antigo_e_copia_todos_os_dados_para_o_novo_cliente(): void
    {
        [$operador, $clienteAnterior, $novoCliente, $veiculo, $rastreador, $chip, $instalador, $retencao] = $this->cenarioAtivo();
        $rastreadorAntes = $rastreador->only(['status_rastreador_id', 'tecnico_id', 'chip_id']);
        $chipAntes = $chip->only(['status_rastreador_id', 'tecnico_id']);

        $this->actingAs($operador);
        $novoVeiculo = app(VeiculoRetencaoService::class)->reter(
            $veiculo,
            $novoCliente->id,
            '2026-09-02',
            $operador,
        );

        $veiculo->refresh();
        $novoVeiculo->refresh();

        $this->assertSame('Cancelado', $veiculo->statusRastreador->label);
        $this->assertSame($clienteAnterior->id, $veiculo->cliente_id);
        $this->assertSame($retencao->id, $veiculo->tecnico_remocao_id);
        $this->assertSame('Retenção', $veiculo->tecnico_remocao);
        $this->assertSame('2026-09-02', $veiculo->data_retirada?->format('Y-m-d'));
        $this->assertSame($operador->id, $veiculo->cancelado_por);

        $this->assertSame('Ativo', $novoVeiculo->statusRastreador->label);
        $this->assertSame($novoCliente->id, $novoVeiculo->cliente_id);
        $this->assertSame('2026-09-02', $novoVeiculo->data_instalacao?->format('Y-m-d'));
        $this->assertNull($novoVeiculo->data_retirada);
        $this->assertNull($novoVeiculo->tecnico_remocao_id);
        $this->assertSame($instalador->id, $novoVeiculo->tecnico_instala_id);

        foreach (['veiculo', 'placa', 'cor', 'ano', 'tipo_veiculo_id', 'rastreador_id', 'login', 'senha', 'valor_instalacao', 'associado', 'contato', 'contato_pais', 'observacao'] as $campo) {
            $this->assertSame($veiculo->getRawOriginal($campo), $novoVeiculo->getRawOriginal($campo), $campo);
        }

        $this->assertSame($rastreadorAntes, $rastreador->fresh()->only(array_keys($rastreadorAntes)));
        $this->assertSame($chipAntes, $chip->fresh()->only(array_keys($chipAntes)));
        $this->assertSame('Inativo', $clienteAnterior->refresh()->statusCliente->label);
        $this->assertSame('Ativo', $novoCliente->refresh()->statusCliente->label);

        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'veiculo.retencao_origem',
            'entidade_id' => $veiculo->id,
            'user_id' => $operador->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'veiculo.retencao_destino',
            'entidade_id' => $novoVeiculo->id,
            'user_id' => $operador->id,
        ]);
        $this->assertSame(
            '[redigido]',
            AuditLog::query()->where('acao', 'veiculo.retencao_destino')->firstOrFail()->depois['senha'],
        );
    }

    public function test_retencao_e_bloqueada_quando_existe_os_ativa(): void
    {
        [$operador, $clienteAnterior, $novoCliente, $veiculo] = $this->cenarioAtivo();
        OrdemServico::query()->create([
            'numero' => 1,
            'tipo' => 'manutencao',
            'status' => OrdemServicoStatus::ABERTA,
            'cliente_id' => $clienteAnterior->id,
            'veiculo_id' => $veiculo->id,
            'endereco' => 'Rua de teste, 1',
            'descricao' => 'O.S. ativa',
        ]);

        $this->actingAs($operador);

        try {
            app(VeiculoRetencaoService::class)->reter($veiculo, $novoCliente->id, '2026-09-03', $operador);
            $this->fail('Era esperada uma falha de validação.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Este veículo possui uma O.S. ativa. Cancele a O.S. antes de realizar a retenção.',
                $exception->errors()['veiculo'][0],
            );
        }

        $this->assertSame('Ativo', $veiculo->fresh()->statusRastreador->label);
        $this->assertSame(1, Veiculo::query()->count());
    }

    public function test_retencao_exige_data_ate_hoje_e_chip_e_rastreador_ativos(): void
    {
        [$operador, , $novoCliente, $veiculo, , $chip] = $this->cenarioAtivo();
        $this->actingAs($operador);

        try {
            app(VeiculoRetencaoService::class)->reter($veiculo, $novoCliente->id, '2026-09-04', $operador);
            $this->fail('Era esperada uma falha para a data futura.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'A data de retenção não pode estar no futuro.',
                $exception->errors()['data_retencao'][0],
            );
        }

        EquipamentoStatusWorkflow::executar(fn () => $chip->update([
            'status_rastreador_id' => StatusRastreador::query()->where('label', 'Disponivel')->value('id'),
        ]));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('A retenção exige um chip ativo vinculado ao rastreador.');
        app(VeiculoRetencaoService::class)->reter($veiculo, $novoCliente->id, '2026-09-03', $operador);
    }

    public function test_botao_retencao_transfere_o_veiculo(): void
    {
        [$operador, , $novoCliente, $veiculo] = $this->cenarioAtivo();
        $this->actingAs($operador);

        Livewire::test(EditRastreador::class, ['record' => $veiculo->getRouteKey()])
            ->callAction('retencao', [
                'novo_cliente_id' => $novoCliente->id,
                'data_retencao' => '2026-09-03',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Cancelado', $veiculo->fresh()->statusRastreador->label);
        $this->assertDatabaseHas('veiculos', [
            'cliente_id' => $novoCliente->id,
            'placa' => $veiculo->placa,
            'status_rastreador_id' => StatusRastreador::query()->where('label', 'Ativo')->value('id'),
        ]);
    }

    public function test_botao_retencao_exibe_o_motivo_quando_a_operacao_e_bloqueada(): void
    {
        [$operador, $clienteAnterior, $novoCliente, $veiculo] = $this->cenarioAtivo();
        OrdemServico::query()->create([
            'numero' => 1,
            'tipo' => 'manutencao',
            'status' => OrdemServicoStatus::ABERTA,
            'cliente_id' => $clienteAnterior->id,
            'veiculo_id' => $veiculo->id,
            'endereco' => 'Rua de teste, 1',
            'descricao' => 'O.S. ativa',
        ]);
        $this->actingAs($operador);

        Livewire::test(EditRastreador::class, ['record' => $veiculo->getRouteKey()])
            ->callAction('retencao', [
                'novo_cliente_id' => $novoCliente->id,
                'data_retencao' => '2026-09-03',
            ])
            ->assertNotified('Não foi possível realizar a retenção.');

        $this->assertSame('Ativo', $veiculo->fresh()->statusRastreador->label);
        $this->assertSame(1, Veiculo::query()->count());
    }

    private function cenarioAtivo(): array
    {
        $this->seed(ClienteSupportSeeder::class);

        $operador = User::factory()->create(['is_admin' => true]);
        $retencao = Tecnico::query()->create(['nome' => 'Retenção', 'is_ativo' => true]);
        $instalador = Tecnico::query()->create(['nome' => 'Instalador original', 'is_ativo' => true]);
        $clienteAnterior = $this->cliente('Cliente anterior', '52998224725');
        $novoCliente = $this->cliente('Cliente novo', '11144477735');
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990301',
            'iccid' => '89550000000000000301',
        ]);
        $rastreador = Rastreador::query()->create([
            'modelo' => 'Modelo retenção',
            'imei' => '860000000000301',
        ]);
        $ativoId = StatusRastreador::query()->where('label', 'Ativo')->value('id');

        EquipamentoStatusWorkflow::executar(function () use ($chip, $rastreador, $ativoId): void {
            $chip->update(['status_rastreador_id' => $ativoId, 'tecnico_id' => null]);
            $rastreador->update([
                'chip_id' => $chip->id,
                'status_rastreador_id' => $ativoId,
                'tecnico_id' => null,
            ]);
        });

        $veiculo = Veiculo::query()->create([
            'cliente_id' => $clienteAnterior->id,
            'status_rastreador_id' => $ativoId,
            'rastreador_id' => $rastreador->id,
            'tecnico_instala_id' => $instalador->id,
            'instalador' => $instalador->nome,
            'veiculo' => 'Veículo retido',
            'placa' => 'RET-1A23',
            'cor' => 'Prata',
            'ano' => '2025',
            'data_instalacao' => '2025-05-10',
            'login' => 'login-teste',
            'senha' => 'senha-teste',
            'valor_instalacao' => 175.50,
            'associado' => 'Associado original',
            'contato' => '62999999999',
            'contato_pais' => 'BR',
            'observacao' => 'Observação original',
        ]);

        return [$operador, $clienteAnterior, $novoCliente, $veiculo, $rastreador, $chip, $instalador, $retencao];
    }

    private function cliente(string $nome, string $cpfCnpj): Cliente
    {
        return Cliente::query()->create([
            'status_cliente_id' => StatusCliente::query()->where('label', 'Ativo')->value('id'),
            'nome' => $nome,
            'cpf_cnpj' => $cpfCnpj,
            'telefone1' => '62999999999',
            'data_adesao' => '2026-09-01',
            'dia_pagamento' => 10,
        ]);
    }
}
