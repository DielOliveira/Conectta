<?php

namespace Tests\Feature;

use App\Enums\OrdemServicoStatus;
use App\Filament\Resources\Disponibilidades\DisponibilidadeResource;
use App\Filament\Resources\OrdensServico\Pages\CreateOrdemServico;
use App\Filament\Resources\OrdensServico\Pages\EditOrdemServico;
use App\Models\Chip;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use App\Services\OrdemServico\OrdemServicoService;
use App\Services\OrdemServico\TecnicoAgendaPublicaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
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

    public function test_extrai_coordenadas_de_link_encurtado_do_google_maps(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        Http::fake([
            'https://maps.app.goo.gl/local-teste' => Http::response('', 302, [
                'Location' => 'https://www.google.com/maps/place/Local/@-16.6499653,-49.2962112,878m/data=!3m1!4b1!4m2!3d-16.6499653!4d-49.2936363',
            ]),
        ]);

        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['localizacao_url'] = 'https://maps.app.goo.gl/local-teste';
        $ordem = app(OrdemServicoService::class)->criar($dados, $operador)['ordem'];

        $this->assertSame('-16.6499653', $ordem->localizacao_latitude);
        $this->assertSame('-49.2936363', $ordem->localizacao_longitude);
        Http::assertSentCount(1);
    }

    public function test_cria_agenda_e_executa_fluxo_inicial_com_token_imprevisivel(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $service = app(OrdemServicoService::class);
        $dadosOrdem = $this->dadosOrdem($cliente, $veiculo);
        $dadosOrdem['notificar_cliente'] = true;
        $ordem = $service->criar($dadosOrdem, $operador)['ordem'];

        $this->assertSame(1, $ordem->numero);
        $this->assertSame(OrdemServicoStatus::ABERTA, $ordem->status);
        $this->assertDatabaseHas('ordem_servico_historicos', ['ordem_servico_id' => $ordem->id, 'evento' => 'abertura']);

        $disponibilidade = app(OrdemServicoAgendaService::class)->criarDisponibilidade($tecnico->id, '2026-08-04', '08:00', '10:00');
        $this->assertSame('08:00:00', $disponibilidade->hora_inicio);
        $this->assertSame('10:00:00', $disponibilidade->hora_fim);
        $blocos = app(OrdemServicoAgendaService::class)->blocos($disponibilidade);
        $this->assertSame(['08:00', '09:00'], $blocos->map->format('H:i')->all());

        $resultado = $service->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 09:00'), $operador);
        $this->assertSame(64, strlen($resultado['token']));
        $this->assertNotSame($resultado['token'], $resultado['ordem']->token_hash);
        $this->assertSame($resultado['ordem']->id, $service->porToken($resultado['token'])->id);
        $this->assertDatabaseHas('ordem_servico_notificacoes', ['ordem_servico_id' => $ordem->id, 'evento' => 'atribuicao']);
        $mensagemTecnico = $ordem->notificacoes()->where('evento', 'atribuicao')->value('mensagem');
        $this->assertStringContainsString('Olá, Técnico OS! Tudo bem?', $mensagemTecnico);
        $this->assertStringContainsString('*OS 000001 — Instalação*', $mensagemTecnico);
        $this->assertStringContainsString('Cliente: Cliente OS', $mensagemTecnico);
        $this->assertStringContainsString('Veículo: Automóvel — OSX-0001', $mensagemTecnico);
        $this->assertStringContainsString('Data: 04/08/2026', $mensagemTecnico);
        $this->assertStringContainsString('Endereço: Rua de Teste, 1', $mensagemTecnico);
        $this->assertStringContainsString($resultado['token'], $mensagemTecnico);

        $this->get(route('ordens-servico.tecnico', $resultado['token']))
            ->assertOk()
            ->assertSee('modal-rejeicao', false)
            ->assertSee('Confirmar rejeição')
            ->assertSee('contact-button whatsapp', false)
            ->assertSee('maps/dir/?api=1&destination=Rua+de+Teste%2C+1', false)
            ->assertDontSee('maps/search', false);

        $this->post(route('ordens-servico.tecnico.action', $resultado['token']), ['acao' => 'rejeitar'])
            ->assertSessionHasErrors('motivo');
        $this->assertSame(OrdemServicoStatus::ENVIADA, $resultado['ordem']->fresh()->status);

        $service->aceitar($resultado['ordem']);
        $mensagemCliente = $ordem->notificacoes()->where('evento', 'aceite')->value('mensagem');
        $this->assertStringContainsString('Olá, Cliente OS!', $mensagemCliente);
        $this->assertStringContainsString('Seu atendimento foi confirmado.', $mensagemCliente);
        $this->assertStringContainsString('👤 Técnico responsável: Técnico OS', $mensagemCliente);
        $this->assertStringContainsString('*Conectta Rastreamento*', $mensagemCliente);
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

    public function test_exige_dados_do_associado_no_veiculo_antes_de_criar_a_os(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['associado'] = true;

        try {
            app(OrdemServicoService::class)->criar($dados, $operador);
            $this->fail('A OS deveria exigir os dados do associado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('associado', $exception->errors());
        }

        $this->assertDatabaseCount('ordens_servico', 0);
    }

    public function test_toggle_de_associado_limpa_e_restaura_endereco_do_cliente(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        $cliente->update([
            'rua' => 'Rua Original',
            'numero' => '123',
            'setor' => 'Centro',
            'cidade' => 'Goiânia',
        ]);
        $this->actingAs($operador);

        Livewire::test(CreateOrdemServico::class)
            ->fillForm(['cliente_id' => $cliente->id, 'veiculo_id' => $veiculo->id])
            ->assertSchemaStateSet(['endereco' => 'Rua Original, 123, Centro, Goiânia'])
            ->fillForm(['associado' => true])
            ->assertSchemaStateSet(['endereco' => null])
            ->fillForm(['associado' => false])
            ->assertSchemaStateSet(['endereco' => 'Rua Original, 123, Centro, Goiânia']);
    }

    public function test_os_de_associado_usa_nome_contato_e_pais_do_veiculo(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $veiculo->update([
            'associado' => 'Associado da Silva',
            'contato' => '(62) 9.7777-6666',
            'contato_pais' => 'BR',
        ]);
        $this->assertSame('62977776666', $veiculo->fresh()->contato);

        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['associado'] = true;
        $dados['notificar_cliente'] = true;
        $ordem = app(OrdemServicoService::class)->criar($dados, $operador)['ordem'];
        $disponibilidade = app(OrdemServicoAgendaService::class)->criarDisponibilidade($tecnico->id, '2026-08-04', '08:00', '09:00');
        app(OrdemServicoService::class)->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 08:00'), $operador);

        $mensagemTecnico = $ordem->notificacoes()->where('evento', 'atribuicao')->value('mensagem');
        $this->assertStringContainsString('Cliente: Associado da Silva', $mensagemTecnico);

        app(OrdemServicoService::class)->aceitar($ordem->fresh());
        $notificacaoAssociado = $ordem->notificacoes()->where('evento', 'aceite')->firstOrFail();
        $this->assertSame('5562977776666', $notificacaoAssociado->telefone);
        $this->assertStringContainsString('Olá, Associado da Silva!', $notificacaoAssociado->mensagem);
    }

    public function test_exibe_no_formulario_o_motivo_que_impede_criar_a_ordem(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000009',
            'status_rastreador_id' => StatusRastreador::query()->where('label', 'Ativo')->value('id'),
        ]);
        $veiculo->update(['rastreador_id' => $rastreador->id]);
        $this->actingAs($operador);

        Livewire::test(CreateOrdemServico::class)
            ->fillForm($this->dadosOrdem($cliente, $veiculo))
            ->call('create')
            ->assertHasErrors(['data.tipo'])
            ->assertNotified('Não foi possível salvar a ordem de serviço.');

        $this->assertDatabaseCount('ordens_servico', 0);
    }

    public function test_nao_permite_instalacao_em_veiculo_com_rastreador(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000010',
            'status_rastreador_id' => StatusRastreador::query()->where('label', 'Ativo')->value('id'),
        ]);
        $veiculo->update(['rastreador_id' => $rastreador->id]);

        try {
            app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador);
            $this->fail('A instalação deveria ser bloqueada para veículo com rastreador.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'A instalação exige um veículo sem rastreador vinculado.',
                $exception->errors()['tipo'][0],
            );
        }

        $this->assertDatabaseCount('ordens_servico', 0);
    }

    public function test_nao_permite_retirada_em_veiculo_sem_rastreador_e_chip(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['tipo'] = 'retirada';

        try {
            app(OrdemServicoService::class)->criar($dados, $operador);
            $this->fail('A retirada deveria ser bloqueada para veículo sem rastreador e chip.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Retirada e manutenção exigem rastreador e chip vinculados ao veículo.',
                $exception->errors()['tipo'][0],
            );
        }

        $this->assertDatabaseCount('ordens_servico', 0);
    }

    public function test_rejeicao_libera_a_agenda_e_invalida_o_token(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $service = app(OrdemServicoService::class);
        $ordem = $service->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $disponibilidade = app(OrdemServicoAgendaService::class)->criarDisponibilidade($tecnico->id, '2026-08-04', '08:00', '09:00');
        $resultado = $service->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 08:00'), $operador);

        $this->post(route('ordens-servico.tecnico.action', $resultado['token']), [
            'acao' => 'rejeitar',
            'motivo' => 'Não conseguirei atender nessa data.',
        ])->assertRedirect(route('ordens-servico.tecnico.rejeicao-confirmada'));
        $ordem = $ordem->fresh();
        $this->assertSame(OrdemServicoStatus::ABERTA, $ordem->status);
        $this->assertNull($ordem->tecnico_id);
        $this->assertNull($ordem->token_hash);
        $this->assertCount(1, app(OrdemServicoAgendaService::class)->blocos($disponibilidade));
        $this->get(route('ordens-servico.tecnico.rejeicao-confirmada'))
            ->assertOk()
            ->assertSee('Atendimento rejeitado');
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
        app(OrdemServicoService::class)->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 09:00'), $operador);

        $this->expectException(ValidationException::class);
        $agenda->atualizarDisponibilidade($disponibilidade, $tecnico->id, '2026-08-04', '08:00', '09:00');
    }

    public function test_tecnico_mantem_a_propria_agenda_pelo_link_publico(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [, , , $tecnico] = $this->cenarioBase();
        $token = app(TecnicoAgendaPublicaService::class)->gerarToken($tecnico);

        $this->assertSame(64, strlen($token));
        $this->assertNotSame($token, $tecnico->fresh()->agenda_token_hash);
        $this->get(route('tecnicos.agenda', $token))
            ->assertOk()
            ->assertSee('Olá, Técnico OS')
            ->assertSee('Adicionar disponibilidade');

        $this->post(route('tecnicos.agenda.store', $token), [
            'data' => '2026-08-04', 'hora_inicio' => '08:00', 'hora_fim' => '10:00',
        ])->assertRedirect(route('tecnicos.agenda', $token));
        $disponibilidade = $tecnico->disponibilidadesOrdemServico()->firstOrFail();

        $this->get(route('tecnicos.agenda', $token))->assertSee('08:00 às 10:00');
        $this->delete(route('tecnicos.agenda.destroy', [$token, $disponibilidade]))
            ->assertRedirect(route('tecnicos.agenda', $token));
        $this->assertModelMissing($disponibilidade);
        $this->get(route('tecnicos.agenda', str_repeat('x', 64)))->assertNotFound();
    }

    public function test_tecnico_nao_exclui_periodo_que_possui_os_vinculada(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $token = app(TecnicoAgendaPublicaService::class)->gerarToken($tecnico);
        $disponibilidade = app(OrdemServicoAgendaService::class)->criarDisponibilidade($tecnico->id, '2026-08-04', '08:00', '09:00');
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        app(OrdemServicoService::class)->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 08:00'), $operador);

        $this->from(route('tecnicos.agenda', $token))
            ->delete(route('tecnicos.agenda.destroy', [$token, $disponibilidade]))
            ->assertRedirect(route('tecnicos.agenda', $token))
            ->assertSessionHasErrors('disponibilidade');
        $this->assertModelExists($disponibilidade);
        $this->get(route('tecnicos.agenda', $token))->assertSee('Possui OS cadastrada');
    }

    public function test_agenda_publica_lista_todos_os_status_do_tecnico_na_semana(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 10:00:00');
        [, $cliente, , $tecnico] = $this->cenarioBase();
        $outroTecnico = Tecnico::query()->create(['nome' => 'Outro Técnico', 'telefone' => '62977777777', 'is_ativo' => true]);
        $status = [
            'aberta', 'enviada', 'aceita', 'em_atendimento', 'aguardando_correcao_cadastral',
            'em_conferencia', 'pendente', 'finalizada', 'cancelada',
        ];

        foreach ($status as $indice => $situacao) {
            $veiculo = Veiculo::query()->create([
                'cliente_id' => $cliente->id,
                'veiculo' => 'Veículo '.$indice,
                'placa' => 'OS'.str_pad((string) $indice, 5, '0', STR_PAD_LEFT),
            ]);
            OrdemServico::query()->create([
                'numero' => 100 + $indice,
                'tipo' => 'instalacao',
                'status' => $situacao,
                'cliente_id' => $cliente->id,
                'veiculo_id' => $veiculo->id,
                'tecnico_id' => $tecnico->id,
                'agendado_em' => CarbonImmutable::parse('2026-08-10 08:00')->addDays($indice % 7),
                'endereco' => 'Rua da OS '.$indice,
                'descricao' => 'Atendimento semanal '.$indice,
            ]);
        }

        $veiculoFora = Veiculo::query()->create(['cliente_id' => $cliente->id, 'veiculo' => 'Fora da semana', 'placa' => 'FOR-0001']);
        OrdemServico::query()->create(['numero' => 198, 'tipo' => 'instalacao', 'status' => 'enviada', 'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculoFora->id, 'tecnico_id' => $tecnico->id, 'agendado_em' => '2026-08-17 08:00', 'endereco' => 'Outra semana', 'descricao' => 'Fora da semana']);
        $veiculoOutro = Veiculo::query()->create(['cliente_id' => $cliente->id, 'veiculo' => 'Outro técnico', 'placa' => 'OUT-0001']);
        OrdemServico::query()->create(['numero' => 199, 'tipo' => 'instalacao', 'status' => 'enviada', 'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculoOutro->id, 'tecnico_id' => $outroTecnico->id, 'agendado_em' => '2026-08-12 08:00', 'endereco' => 'Outro técnico', 'descricao' => 'Outro técnico']);
        $token = app(TecnicoAgendaPublicaService::class)->gerarToken($tecnico);

        $resposta = $this->get(route('tecnicos.agenda', [
            'token' => $token,
            'aba' => 'ordens',
            'semana' => '2026-08-10',
        ]))->assertOk()->assertSee('Minhas O.S.');

        foreach (range(100, 108) as $numero) {
            $resposta->assertSee('OS '.str_pad((string) $numero, 6, '0', STR_PAD_LEFT));
        }
        $resposta->assertDontSee('OS 000198')->assertDontSee('OS 000199');
    }

    public function test_chip_substituido_volta_disponivel_para_o_estoque_do_tecnico(): void
    {
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $statusDisponivel = StatusRastreador::query()->where('label', 'Disponivel')->firstOrFail();
        $statusAtivo = StatusRastreador::query()->where('label', 'Ativo')->firstOrFail();
        $chipAnterior = Chip::query()->create([
            'numero_chip' => '5562999990001',
            'iccid' => '89550000000000000001',
            'status_rastreador_id' => $statusAtivo->id,
        ]);
        $chipNovo = Chip::query()->create([
            'numero_chip' => '5562999990002',
            'iccid' => '89550000000000000002',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $statusDisponivel->id,
        ]);
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000001',
            'chip_id' => $chipAnterior->id,
            'status_rastreador_id' => $statusAtivo->id,
        ]);
        $veiculo->update(['rastreador_id' => $rastreador->id]);

        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['tipo'] = 'manutencao';
        $ordem = app(OrdemServicoService::class)->criar($dados, $operador)['ordem'];
        $ordem->update([
            'tecnico_id' => $tecnico->id,
            'chip_novo_id' => $chipNovo->id,
            'status' => OrdemServicoStatus::EM_CONFERENCIA,
        ]);

        app(OrdemServicoService::class)->finalizar($ordem->fresh(), $operador, [
            'check_funcionamento' => true,
            'check_pos_chave' => true,
            'check_bloqueio' => 'conferido',
        ]);

        $this->assertSame($tecnico->id, $chipAnterior->fresh()->tecnico_id);
        $this->assertSame($statusDisponivel->id, $chipAnterior->fresh()->status_rastreador_id);
        $this->assertSame($chipNovo->id, $rastreador->fresh()->chip_id);
    }

    public function test_operador_aprova_e_finaliza_instalacao_pelo_popup_de_conferencia(): void
    {
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $statusDisponivel = StatusRastreador::query()->where('label', 'Disponivel')->firstOrFail();
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990010',
            'iccid' => '89550000000000000010',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $statusDisponivel->id,
        ]);
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000010',
            'chip_id' => $chip->id,
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $statusDisponivel->id,
        ]);
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $ordem->update([
            'tecnico_id' => $tecnico->id,
            'rastreador_novo_id' => $rastreador->id,
            'chip_novo_id' => $chip->id,
            'status' => OrdemServicoStatus::EM_CONFERENCIA,
        ]);
        $this->actingAs($operador);

        Livewire::test(EditOrdemServico::class, ['record' => $ordem->getRouteKey()])
            ->mountAction('finalizar')
            ->setActionData(['check_funcionamento' => '1'])
            ->setActionData(['check_pos_chave' => '1'])
            ->setActionData(['check_bloqueio' => 'conferido'])
            ->assertActionDataSet([
                'check_funcionamento' => '1',
                'check_pos_chave' => '1',
                'check_bloqueio' => 'conferido',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertRedirect();

        $this->assertSame(OrdemServicoStatus::FINALIZADA, $ordem->fresh()->status);
        $this->assertSame($rastreador->id, $veiculo->fresh()->rastreador_id);
    }

    public function test_popup_informa_o_item_nao_conferido_ao_tentar_finalizar(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $ordem->update(['status' => OrdemServicoStatus::EM_CONFERENCIA]);
        $this->actingAs($operador);

        Livewire::test(EditOrdemServico::class, ['record' => $ordem->getRouteKey()])
            ->callAction('finalizar', data: [
                'check_funcionamento' => '0',
                'check_pos_chave' => '1',
                'check_bloqueio' => 'conferido',
            ])
            ->assertHasActionErrors(['check_funcionamento' => 'accepted']);

        $this->assertSame(OrdemServicoStatus::EM_CONFERENCIA, $ordem->fresh()->status);
    }

    public function test_popup_exibe_o_motivo_quando_o_imei_ja_esta_em_outro_veiculo(): void
    {
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $statusDisponivel = StatusRastreador::query()->where('label', 'Disponivel')->firstOrFail();
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990011',
            'iccid' => '89550000000000000011',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $statusDisponivel->id,
        ]);
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000011',
            'chip_id' => $chip->id,
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $statusDisponivel->id,
        ]);
        Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'veiculo' => 'Veículo já instalado',
            'placa' => 'OSX-0002',
            'rastreador_id' => $rastreador->id,
            'status_rastreador_id' => StatusRastreador::query()->where('label', 'Ativo')->value('id'),
        ]);
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $ordem->update([
            'tecnico_id' => $tecnico->id,
            'rastreador_novo_id' => $rastreador->id,
            'chip_novo_id' => $chip->id,
            'status' => OrdemServicoStatus::EM_CONFERENCIA,
        ]);
        $this->actingAs($operador);

        Livewire::test(EditOrdemServico::class, ['record' => $ordem->getRouteKey()])
            ->callAction('finalizar', data: [
                'check_funcionamento' => '1',
                'check_pos_chave' => '1',
                'check_bloqueio' => 'conferido',
            ])
            ->assertNotified('Não foi possível finalizar a ordem de serviço.');

        $this->assertSame(OrdemServicoStatus::EM_CONFERENCIA, $ordem->fresh()->status);
        $this->assertNull($veiculo->fresh()->rastreador_id);
    }

    public function test_atendimento_lista_somente_rastreadores_e_chips_disponiveis_do_tecnico(): void
    {
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $statusDisponivel = StatusRastreador::query()->where('label', 'Disponivel')->firstOrFail();
        $statusAtivo = StatusRastreador::query()->where('label', 'Ativo')->firstOrFail();
        $chipDisponivelVinculado = Chip::query()->create([
            'numero_chip' => '5562999990020',
            'iccid' => '89550000000000000020',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $statusDisponivel->id,
        ]);
        $chipDisponivelLivre = Chip::query()->create([
            'numero_chip' => '5562999990021',
            'iccid' => '89550000000000000021',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $statusDisponivel->id,
        ]);
        $chipAtivo = Chip::query()->create([
            'numero_chip' => '5562999990022',
            'iccid' => '89550000000000000022',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $statusAtivo->id,
        ]);
        Rastreador::query()->create([
            'imei' => '860000000000020',
            'chip_id' => $chipDisponivelVinculado->id,
            'tecnico_id' => $tecnico->id,
            'is_estoque' => true,
            'status_rastreador_id' => $statusDisponivel->id,
        ]);
        Rastreador::query()->create([
            'imei' => '860000000000022',
            'tecnico_id' => $tecnico->id,
            'is_estoque' => true,
            'status_rastreador_id' => $statusAtivo->id,
        ]);
        $token = str_repeat('a', 64);
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $ordem->update([
            'tecnico_id' => $tecnico->id,
            'status' => OrdemServicoStatus::EM_ATENDIMENTO,
            'token_hash' => hash('sha256', $token),
            'token_credencial' => $token,
        ]);

        $this->get(route('ordens-servico.tecnico', $token))
            ->assertOk()
            ->assertSee('860000000000020')
            ->assertSee($chipDisponivelVinculado->numero_chip)
            ->assertSee($chipDisponivelLivre->numero_chip)
            ->assertDontSee('860000000000022')
            ->assertDontSee($chipAtivo->numero_chip);
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
            'endereco' => 'Rua de Teste, 1', 'descricao' => 'Instalar equipamento', 'notificar_cliente' => false];
    }
}
