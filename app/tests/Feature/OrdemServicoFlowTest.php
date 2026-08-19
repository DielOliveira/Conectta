<?php

namespace Tests\Feature;

use App\Enums\OrdemServicoStatus;
use App\Filament\Pages\AgendaOrdensServico;
use App\Filament\Resources\Disponibilidades\DisponibilidadeResource;
use App\Filament\Resources\Disponibilidades\Pages\CreateDisponibilidade;
use App\Filament\Resources\Disponibilidades\Pages\ListDisponibilidades;
use App\Filament\Resources\OrdensServico\Pages\CreateOrdemServico;
use App\Filament\Resources\OrdensServico\Pages\EditOrdemServico;
use App\Models\Chip;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\OrdemServicoDisponibilidade;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\Estoque\EquipamentoStatusWorkflow;
use App\Services\OrdemServico\OrdemServicoAgendaService;
use App\Services\OrdemServico\OrdemServicoEquipamentoReserva;
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

    public function test_lista_de_agendas_esconde_vencidas_por_padrao_e_permite_filtra_las(): void
    {
        CarbonImmutable::setTestNow('2026-08-19 10:30:00');
        [$operador, , , $tecnico] = $this->cenarioBase();
        $vencidaOntem = OrdemServicoDisponibilidade::query()->create([
            'tecnico_id' => $tecnico->id,
            'tipo' => OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE,
            'data' => '2026-08-18',
            'hora_inicio' => '09:00:00',
            'hora_fim' => '10:00:00',
        ]);
        $vencidaHoje = OrdemServicoDisponibilidade::query()->create([
            'tecnico_id' => $tecnico->id,
            'tipo' => OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE,
            'data' => '2026-08-19',
            'hora_inicio' => '08:00:00',
            'hora_fim' => '09:00:00',
        ]);
        $presente = OrdemServicoDisponibilidade::query()->create([
            'tecnico_id' => $tecnico->id,
            'tipo' => OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE,
            'data' => '2026-08-19',
            'hora_inicio' => '10:00:00',
            'hora_fim' => '11:00:00',
        ]);
        $futura = OrdemServicoDisponibilidade::query()->create([
            'tecnico_id' => $tecnico->id,
            'tipo' => OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE,
            'data' => '2026-08-20',
            'hora_inicio' => '09:00:00',
            'hora_fim' => '10:00:00',
        ]);
        $this->actingAs($operador);

        Livewire::test(ListDisponibilidades::class)
            ->assertCanSeeTableRecords([$presente, $futura])
            ->assertCanNotSeeTableRecords([$vencidaOntem, $vencidaHoje])
            ->filterTable('periodo', 'vencidas')
            ->assertCanSeeTableRecords([$vencidaOntem, $vencidaHoje])
            ->assertCanNotSeeTableRecords([$presente, $futura])
            ->filterTable('periodo', 'todas')
            ->assertCanSeeTableRecords([$vencidaOntem, $vencidaHoje, $presente, $futura]);
    }

    public function test_central_cria_a_mesma_disponibilidade_de_segunda_a_sexta(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, , , $tecnico] = $this->cenarioBase();
        $this->actingAs($operador);

        Livewire::test(CreateDisponibilidade::class)
            ->fillForm([
                'tecnico_id' => $tecnico->id,
                'tipo' => OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE,
                'modo' => 'semana',
                'data' => '2026-08-17',
                'hora_inicio' => '08:00',
                'hora_fim' => '10:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['2026-08-17', '2026-08-18', '2026-08-19', '2026-08-20', '2026-08-21'],
            $tecnico->disponibilidadesOrdemServico()->orderBy('data')->get()->map(fn ($item) => $item->data->toDateString())->all(),
        );
    }

    public function test_bloqueio_prevalece_criado_antes_ou_depois_da_disponibilidade(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [, , , $tecnico] = $this->cenarioBase();
        $agenda = app(OrdemServicoAgendaService::class);

        $disponibilidadeAntes = $agenda->criarDisponibilidade($tecnico->id, '2026-08-18', '08:00', '10:00');
        $agenda->criarIntervalo($tecnico->id, '2026-08-18', '09:00', '10:00', OrdemServicoDisponibilidade::TIPO_BLOQUEIO);
        $this->assertSame(['08:00'], $agenda->blocos($disponibilidadeAntes)->map->format('H:i')->all());

        $agenda->criarIntervalo($tecnico->id, '2026-08-19', '08:00', '09:00', OrdemServicoDisponibilidade::TIPO_BLOQUEIO);
        $disponibilidadeDepois = $agenda->criarDisponibilidade($tecnico->id, '2026-08-19', '08:00', '10:00');
        $this->assertSame(['09:00'], $agenda->blocos($disponibilidadeDepois)->map->format('H:i')->all());
    }

    public function test_nao_permite_bloquear_horario_com_os_ativa(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $agenda = app(OrdemServicoAgendaService::class);
        $disponibilidade = $agenda->criarDisponibilidade($tecnico->id, '2026-08-18', '08:00', '10:00');
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        app(OrdemServicoService::class)->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-18 09:00'), $operador);

        $this->expectException(ValidationException::class);
        $agenda->criarIntervalo($tecnico->id, '2026-08-18', '09:00', '10:00', OrdemServicoDisponibilidade::TIPO_BLOQUEIO);
    }

    public function test_calendario_da_central_exibe_o_horario_bloqueado(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, , , $tecnico] = $this->cenarioBase();
        $bloqueio = app(OrdemServicoAgendaService::class)->criarIntervalo(
            $tecnico->id,
            '2026-08-18',
            '09:00',
            '10:00',
            OrdemServicoDisponibilidade::TIPO_BLOQUEIO,
        );
        $this->actingAs($operador);
        $pagina = app(AgendaOrdensServico::class);
        $pagina->data = '2026-08-18';

        $this->assertTrue($bloqueio->isBloqueio());
        $this->assertSame(['2026-08-18'], $pagina->dias()->map->toDateString()->all());
        $item = $pagina->agenda()->firstOrFail();
        $this->assertTrue($item['bloqueio']);
        $this->assertSame('Técnico OS', $item['disponibilidade']->tecnico->nome);
    }

    public function test_calendario_abre_horario_de_uma_hora_e_atribui_a_os_ao_tecnico_escolhido(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnicoComAgenda] = $this->cenarioBase();
        $tecnicoSemAgenda = Tecnico::query()->create([
            'nome' => 'Técnico sem agenda',
            'telefone' => '62977777777',
            'is_ativo' => true,
        ]);
        app(OrdemServicoAgendaService::class)->criarDisponibilidade(
            $tecnicoComAgenda->id,
            '2026-08-04',
            '08:00',
            '10:00',
        );
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $horario = '2026-08-04 09:00:00';
        $this->actingAs($operador);

        Livewire::test(AgendaOrdensServico::class)
            ->set('data', '2026-08-04')
            ->assertSee('Abrir horário')
            ->assertDontSee('Nenhum técnico disponível para atribuição.')
            ->callAction('atribuir', data: [
                'horario' => $horario,
                'abrir_horario' => true,
                'ordem_servico_id' => $ordem->id,
                'tecnico_id' => $tecnicoSemAgenda->id,
            ], arguments: [
                'horario' => $horario,
                'abrir_horario' => true,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('OS agendada e enviada ao técnico.');

        $disponibilidade = OrdemServicoDisponibilidade::query()
            ->where('tecnico_id', $tecnicoSemAgenda->id)
            ->firstOrFail();
        $this->assertSame('2026-08-04', $disponibilidade->data->format('Y-m-d'));
        $this->assertSame('09:00:00', $disponibilidade->hora_inicio);
        $this->assertSame('10:00:00', $disponibilidade->hora_fim);
        $this->assertSame($disponibilidade->id, $ordem->fresh()->disponibilidade_id);
        $this->assertSame($tecnicoSemAgenda->id, $ordem->fresh()->tecnico_id);
        $this->assertSame(OrdemServicoStatus::ENVIADA, $ordem->fresh()->status);
    }

    public function test_abrir_horario_reaproveita_bloco_livre_e_nao_lista_tecnico_bloqueado(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnicoLivre] = $this->cenarioBase();
        $tecnicoBloqueado = Tecnico::query()->create([
            'nome' => 'Técnico bloqueado',
            'telefone' => '62966666666',
            'is_ativo' => true,
        ]);
        $agenda = app(OrdemServicoAgendaService::class);
        $disponibilidade = $agenda->criarDisponibilidade($tecnicoLivre->id, '2026-08-04', '08:00', '10:00');
        $agenda->criarIntervalo(
            $tecnicoBloqueado->id,
            '2026-08-04',
            '09:00',
            '10:00',
            OrdemServicoDisponibilidade::TIPO_BLOQUEIO,
        );
        $horario = CarbonImmutable::parse('2026-08-04 09:00:00');

        $tecnicos = $agenda->tecnicosDisponiveisParaAbrirHorario($horario);
        $this->assertTrue($tecnicos->contains('id', $tecnicoLivre->id));
        $this->assertFalse($tecnicos->contains('id', $tecnicoBloqueado->id));

        try {
            $agenda->obterOuCriarHorario($tecnicoBloqueado->id, $horario);
            $this->fail('O bloqueio do técnico deveria impedir a abertura do horário.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('tecnico_id', $exception->errors());
        }
        $this->assertSame(0, OrdemServicoDisponibilidade::query()
            ->where('tecnico_id', $tecnicoBloqueado->id)
            ->where('tipo', OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE)
            ->count());

        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        app(OrdemServicoService::class)->agendarAbrindoHorario(
            $ordem,
            $tecnicoLivre->id,
            $horario,
            $operador,
        );

        $this->assertSame($disponibilidade->id, $ordem->fresh()->disponibilidade_id);
        $this->assertSame(1, OrdemServicoDisponibilidade::query()
            ->where('tecnico_id', $tecnicoLivre->id)
            ->count());
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

    public function test_permite_agendar_os_em_bloco_que_ja_comecou_mas_ainda_nao_terminou(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $disponibilidade = app(OrdemServicoAgendaService::class)->criarDisponibilidade($tecnico->id, '2026-08-04', '09:00', '11:00');
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];

        CarbonImmutable::setTestNow('2026-08-04 09:30:00');
        app(OrdemServicoService::class)->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 09:00'), $operador);

        $this->assertSame('2026-08-04 09:00:00', $ordem->fresh()->agendado_em?->format('Y-m-d H:i:s'));
    }

    public function test_calendario_mostra_status_da_os_com_cor_propria_e_mantem_o_bloco_ocupado(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $agenda = app(OrdemServicoAgendaService::class);
        $disponibilidade = $agenda->criarDisponibilidade($tecnico->id, '2026-08-04', '09:00', '11:00');
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        app(OrdemServicoService::class)->agendar($ordem, $disponibilidade, CarbonImmutable::parse('2026-08-04 09:00'), $operador);
        $ordem->update(['status' => OrdemServicoStatus::FINALIZADA]);

        $pagina = app(AgendaOrdensServico::class);
        $pagina->data = '2026-08-04';
        $item = $pagina->agenda()->first(fn (array $item): bool => $item['ordem']?->is($ordem) ?? false);

        $this->assertNotNull($item);
        $this->assertSame('Finalizada', $item['status_label']);
        $this->assertSame('status-finalizada', $item['status_classe']);
        $this->assertFalse($agenda->blocos($disponibilidade)->contains(fn (CarbonImmutable $bloco): bool => $bloco->format('H:i') === '09:00'));
    }

    public function test_calendario_define_uma_cor_para_todos_os_status_de_os(): void
    {
        $pagina = app(AgendaOrdensServico::class);

        foreach (OrdemServicoStatus::cases() as $status) {
            $this->assertNotSame('', $pagina->statusClasse($status), "Status {$status->value} ficou sem classe de cor.");
        }
    }

    public function test_editar_dados_depois_do_agendamento_nao_reabre_a_ordem(): void
    {
        CarbonImmutable::setTestNow('2026-08-14 08:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $disponibilidade = app(OrdemServicoAgendaService::class)->criarDisponibilidade($tecnico->id, '2026-08-15', '08:00', '12:00');
        $this->actingAs($operador);

        Livewire::test(EditOrdemServico::class, ['record' => $ordem->getRouteKey()])
            ->callAction('agendar', data: [
                'disponibilidade_id' => $disponibilidade->id,
                'agendado_em' => '2026-08-15 09:00:00',
            ])
            ->set('data.status', OrdemServicoStatus::ABERTA->value)
            ->set('data.observacoes', 'Observação atualizada depois do agendamento.')
            ->call('save')
            ->assertHasNoFormErrors();

        $ordem->refresh();
        $this->assertSame(OrdemServicoStatus::ENVIADA, $ordem->status);
        $this->assertSame($tecnico->id, $ordem->tecnico_id);
        $this->assertSame('2026-08-15 09:00:00', $ordem->agendado_em?->format('Y-m-d H:i:s'));
        $this->assertSame('Observação atualizada depois do agendamento.', $ordem->observacoes);

        app(OrdemServicoService::class)->aceitar($ordem);
        $this->assertSame(OrdemServicoStatus::ACEITA, $ordem->fresh()->status);
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

    public function test_tecnico_cria_semana_e_navega_entre_semanas_no_mobile(): void
    {
        CarbonImmutable::setTestNow('2026-08-03 08:00:00');
        [, , , $tecnico] = $this->cenarioBase();
        $token = app(TecnicoAgendaPublicaService::class)->gerarToken($tecnico);

        $this->post(route('tecnicos.agenda.store', $token), [
            'modo' => 'semana',
            'tipo' => OrdemServicoDisponibilidade::TIPO_DISPONIBILIDADE,
            'data' => '2026-08-24',
            'hora_inicio' => '08:00',
            'hora_fim' => '10:00',
        ])->assertRedirect(route('tecnicos.agenda', [
            'token' => $token,
            'modo_agenda' => 'semana',
            'semana' => '2026-08-24',
        ]));

        $this->assertCount(5, $tecnico->disponibilidadesOrdemServico);
        $this->get(route('tecnicos.agenda', ['token' => $token, 'modo_agenda' => 'semana', 'semana' => '2026-08-24']))
            ->assertOk()
            ->assertSee('24 ago — 28 ago')
            ->assertSee('Semana anterior')
            ->assertSee('Próxima semana');
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
        $this->assertNull($chipNovo->fresh()->tecnico_id);
        $this->assertSame($statusAtivo->id, $chipNovo->fresh()->status_rastreador_id);
    }

    public function test_manutencao_com_troca_de_rastreador_movimenta_equipamentos_novos_e_retirados(): void
    {
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $disponivel = StatusRastreador::query()->where('label', 'Disponivel')->firstOrFail();
        $ativo = StatusRastreador::query()->where('label', 'Ativo')->firstOrFail();
        $chipAnterior = Chip::query()->create([
            'numero_chip' => '5562999990031',
            'iccid' => '89550000000000000031',
            'status_rastreador_id' => $ativo->id,
        ]);
        $rastreadorAnterior = Rastreador::query()->create([
            'imei' => '860000000000031',
            'chip_id' => $chipAnterior->id,
            'status_rastreador_id' => $ativo->id,
            'is_estoque' => false,
        ]);
        $chipNovo = Chip::query()->create([
            'numero_chip' => '5562999990032',
            'iccid' => '89550000000000000032',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $disponivel->id,
        ]);
        $rastreadorNovo = Rastreador::query()->create([
            'imei' => '860000000000032',
            'chip_id' => $chipNovo->id,
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $disponivel->id,
            'is_estoque' => true,
        ]);
        $veiculo->update(['rastreador_id' => $rastreadorAnterior->id, 'status_rastreador_id' => $ativo->id]);

        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['tipo'] = 'manutencao';
        $ordem = app(OrdemServicoService::class)->criar($dados, $operador)['ordem'];
        $ordem->update([
            'tecnico_id' => $tecnico->id,
            'rastreador_novo_id' => $rastreadorNovo->id,
            'chip_novo_id' => $chipNovo->id,
            'status' => OrdemServicoStatus::EM_CONFERENCIA,
        ]);

        app(OrdemServicoService::class)->finalizar($ordem->fresh(), $operador, [
            'check_funcionamento' => true,
            'check_pos_chave' => true,
            'check_bloqueio' => 'conferido',
        ]);

        $this->assertSame($rastreadorNovo->id, $veiculo->fresh()->rastreador_id);
        $this->assertSame($tecnico->id, $veiculo->fresh()->tecnico_instala_id);
        $this->assertNull($rastreadorNovo->fresh()->tecnico_id);
        $this->assertSame($ativo->id, $rastreadorNovo->fresh()->status_rastreador_id);
        $this->assertFalse($rastreadorNovo->fresh()->is_estoque);
        $this->assertNull($chipNovo->fresh()->tecnico_id);
        $this->assertSame($ativo->id, $chipNovo->fresh()->status_rastreador_id);
        $this->assertSame($tecnico->id, $rastreadorAnterior->fresh()->tecnico_id);
        $this->assertSame($disponivel->id, $rastreadorAnterior->fresh()->status_rastreador_id);
        $this->assertTrue($rastreadorAnterior->fresh()->is_estoque);
        $this->assertSame($tecnico->id, $chipAnterior->fresh()->tecnico_id);
        $this->assertSame($disponivel->id, $chipAnterior->fresh()->status_rastreador_id);
    }

    public function test_manutencao_trocando_so_rastreador_reutiliza_o_chip_atual(): void
    {
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $disponivel = StatusRastreador::query()->where('label', 'Disponivel')->firstOrFail();
        $ativo = StatusRastreador::query()->where('label', 'Ativo')->firstOrFail();
        $chipAtual = Chip::query()->create([
            'numero_chip' => '5562999990033',
            'iccid' => '89550000000000000033',
            'status_rastreador_id' => $ativo->id,
        ]);
        $rastreadorAnterior = Rastreador::query()->create([
            'imei' => '860000000000033',
            'chip_id' => $chipAtual->id,
            'status_rastreador_id' => $ativo->id,
            'is_estoque' => false,
        ]);
        $rastreadorNovo = Rastreador::query()->create([
            'imei' => '860000000000034',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $disponivel->id,
            'is_estoque' => true,
        ]);
        $veiculo->update(['rastreador_id' => $rastreadorAnterior->id, 'status_rastreador_id' => $ativo->id]);

        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['tipo'] = 'manutencao';
        $ordem = app(OrdemServicoService::class)->criar($dados, $operador)['ordem'];
        $ordem->update([
            'tecnico_id' => $tecnico->id,
            'rastreador_novo_id' => $rastreadorNovo->id,
            'chip_novo_id' => null,
            'resultado_manutencao' => 'troca_rastreador',
            'descricao_atendimento' => 'Rastreador substituído com reaproveitamento do chip atual.',
            'equipamentos_confirmados' => true,
            'status' => OrdemServicoStatus::EM_CONFERENCIA,
        ]);

        app(OrdemServicoService::class)->finalizar($ordem->fresh(), $operador, [
            'check_funcionamento' => true,
            'check_pos_chave' => true,
            'check_bloqueio' => 'conferido',
        ]);

        $this->assertSame($rastreadorNovo->id, $veiculo->fresh()->rastreador_id);
        $this->assertSame($chipAtual->id, $rastreadorNovo->fresh()->chip_id);
        $this->assertNull($chipAtual->fresh()->tecnico_id);
        $this->assertSame($ativo->id, $chipAtual->fresh()->status_rastreador_id);
        $this->assertNull($rastreadorAnterior->fresh()->chip_id);
        $this->assertSame($tecnico->id, $rastreadorAnterior->fresh()->tecnico_id);
        $this->assertSame($disponivel->id, $rastreadorAnterior->fresh()->status_rastreador_id);
        $this->assertTrue($rastreadorAnterior->fresh()->is_estoque);
    }

    public function test_retirada_devolve_rastreador_e_chip_disponiveis_ao_estoque_do_tecnico(): void
    {
        CarbonImmutable::setTestNow('2026-08-14 10:00:00');
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $statusDisponivel = StatusRastreador::query()->where('label', 'Disponivel')->firstOrFail();
        $statusAtivo = StatusRastreador::query()->where('label', 'Ativo')->firstOrFail();
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990009',
            'iccid' => '89550000000000000009',
            'status_rastreador_id' => $statusAtivo->id,
        ]);
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000009',
            'chip_id' => $chip->id,
            'status_rastreador_id' => $statusAtivo->id,
            'is_estoque' => false,
        ]);
        $veiculo->update(['rastreador_id' => $rastreador->id, 'status_rastreador_id' => $statusAtivo->id]);

        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['tipo'] = 'retirada';
        $ordem = app(OrdemServicoService::class)->criar($dados, $operador)['ordem'];
        $ordem->update([
            'tecnico_id' => $tecnico->id,
            'status' => OrdemServicoStatus::EM_CONFERENCIA,
        ]);

        app(OrdemServicoService::class)->finalizar($ordem->fresh(), $operador);

        $this->assertSame(OrdemServicoStatus::FINALIZADA, $ordem->fresh()->status);
        $this->assertNull($veiculo->fresh()->rastreador_id);
        $this->assertSame($tecnico->id, $veiculo->fresh()->tecnico_remocao_id);
        $this->assertSame('2026-08-14', $veiculo->fresh()->data_retirada?->format('Y-m-d'));
        $this->assertSame($tecnico->id, $rastreador->fresh()->tecnico_id);
        $this->assertSame($statusDisponivel->id, $rastreador->fresh()->status_rastreador_id);
        $this->assertTrue($rastreador->fresh()->is_estoque);
        $this->assertSame($chip->id, $rastreador->fresh()->chip_id);
        $this->assertSame($tecnico->id, $chip->fresh()->tecnico_id);
        $this->assertSame($statusDisponivel->id, $chip->fresh()->status_rastreador_id);
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
        $this->assertNull($rastreador->fresh()->tecnico_id);
        $this->assertSame(
            StatusRastreador::query()->where('label', 'Ativo')->value('id'),
            $rastreador->fresh()->status_rastreador_id,
        );
        $this->assertFalse($rastreador->fresh()->is_estoque);
        $this->assertNull($chip->fresh()->tecnico_id);
        $this->assertSame(
            StatusRastreador::query()->where('label', 'Ativo')->value('id'),
            $chip->fresh()->status_rastreador_id,
        );
    }

    public function test_painel_exibe_equipamentos_que_ficarao_no_veiculo_em_conferencia_e_apos_finalizar(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        $chipAnterior = Chip::query()->create([
            'numero_chip' => '5562999990041',
            'iccid' => '89550000000000000041',
        ]);
        $rastreadorAnterior = Rastreador::query()->create([
            'imei' => '860000000000041',
            'chip_id' => $chipAnterior->id,
        ]);
        $rastreadorNovo = Rastreador::query()->create(['imei' => '860000000000042']);
        $veiculo->update(['rastreador_id' => $rastreadorAnterior->id]);

        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['tipo'] = 'manutencao';
        $ordem = app(OrdemServicoService::class)->criar($dados, $operador)['ordem'];
        $ordem->update([
            'rastreador_novo_id' => $rastreadorNovo->id,
            'resultado_manutencao' => 'troca_rastreador',
            'status' => OrdemServicoStatus::EM_CONFERENCIA,
        ]);
        $this->actingAs($operador);

        Livewire::test(EditOrdemServico::class, ['record' => $ordem->getRouteKey()])
            ->assertSee('Equipamentos vinculados ao veículo')
            ->assertSee('que ficarão vinculados ao veículo')
            ->assertSee($rastreadorNovo->imei)
            ->assertSee($chipAnterior->numero_chip)
            ->assertSee($chipAnterior->iccid);

        $ordem->update(['status' => OrdemServicoStatus::FINALIZADA]);

        Livewire::test(EditOrdemServico::class, ['record' => $ordem->getRouteKey()])
            ->assertSee('Equipamentos que ficaram vinculados ao veículo')
            ->assertSee($rastreadorNovo->imei)
            ->assertSee($chipAnterior->numero_chip)
            ->assertSee($chipAnterior->iccid);
    }

    public function test_painel_de_retirada_informa_que_nenhum_equipamento_ficou_vinculado(): void
    {
        [$operador, $cliente, $veiculo] = $this->cenarioBase();
        $chip = Chip::query()->create(['numero_chip' => '5562999990043']);
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000043',
            'chip_id' => $chip->id,
        ]);
        $veiculo->update(['rastreador_id' => $rastreador->id]);
        $dados = $this->dadosOrdem($cliente, $veiculo);
        $dados['tipo'] = 'retirada';
        $ordem = app(OrdemServicoService::class)->criar($dados, $operador)['ordem'];
        $ordem->update(['status' => OrdemServicoStatus::FINALIZADA]);
        $this->actingAs($operador);

        Livewire::test(EditOrdemServico::class, ['record' => $ordem->getRouteKey()])
            ->assertSee('Equipamentos vinculados ao veículo')
            ->assertSee('Nenhum rastreador vinculado')
            ->assertSee('Nenhum chip vinculado');
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
        EquipamentoStatusWorkflow::executar(function () use ($chipAtivo, $statusAtivo): void {
            $chipAtivo->update(['status_rastreador_id' => $statusAtivo->id]);
            Rastreador::query()->where('imei', '860000000000022')->firstOrFail()
                ->update(['status_rastreador_id' => $statusAtivo->id]);
        });
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

    public function test_equipamento_reservado_some_de_outra_os_mas_continua_visivel_na_propria(): void
    {
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $disponivel = StatusRastreador::query()->where('label', 'Disponivel')->firstOrFail();
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990051',
            'iccid' => '89550000000000000051',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $disponivel->id,
        ]);
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000051',
            'tecnico_id' => $tecnico->id,
            'is_estoque' => true,
            'status_rastreador_id' => $disponivel->id,
        ]);
        $rastreadorLivre = Rastreador::query()->create([
            'imei' => '860000000000053',
            'tecnico_id' => $tecnico->id,
            'is_estoque' => true,
            'status_rastreador_id' => $disponivel->id,
        ]);
        $tokenReservante = str_repeat('b', 64);
        $ordemReservante = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $ordemReservante->update([
            'tecnico_id' => $tecnico->id,
            'rastreador_novo_id' => $rastreador->id,
            'chip_novo_id' => $chip->id,
            'status' => OrdemServicoStatus::PENDENTE,
            'token_hash' => hash('sha256', $tokenReservante),
            'token_credencial' => $tokenReservante,
        ]);

        $outroVeiculo = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'veiculo' => 'Segundo automóvel',
            'placa' => 'OSX-0051',
        ]);
        $tokenConcorrente = str_repeat('c', 64);
        $ordemConcorrente = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $outroVeiculo), $operador)['ordem'];
        $ordemConcorrente->update([
            'tecnico_id' => $tecnico->id,
            'status' => OrdemServicoStatus::EM_ATENDIMENTO,
            'token_hash' => hash('sha256', $tokenConcorrente),
            'token_credencial' => $tokenConcorrente,
        ]);

        $this->get(route('ordens-servico.tecnico', $tokenReservante))
            ->assertOk()
            ->assertSee($rastreador->imei)
            ->assertSee($chip->numero_chip);
        $this->get(route('ordens-servico.tecnico', $tokenConcorrente))
            ->assertOk()
            ->assertDontSee($rastreador->imei)
            ->assertSee($rastreadorLivre->imei)
            ->assertDontSee($chip->numero_chip);

        $this->post(route('ordens-servico.tecnico.action', $tokenConcorrente), [
            'acao' => 'conferencia',
            'rastreador_novo_id' => $rastreador->id,
            'chip_novo_id' => $chip->id,
        ])->assertSessionHasErrors('rastreador_novo_id');

        $this->post(route('ordens-servico.tecnico.action', $tokenConcorrente), [
            'acao' => 'conferencia',
            'rastreador_novo_id' => $rastreadorLivre->id,
            'chip_novo_id' => $chip->id,
        ])->assertSessionHasErrors('chip_novo_id');

        $this->assertSame(OrdemServicoStatus::EM_ATENDIMENTO, $ordemConcorrente->fresh()->status);
        $this->assertNull($ordemConcorrente->fresh()->rastreador_novo_id);
        $this->assertStringContainsString(
            $ordemReservante->numero_formatado,
            OrdemServicoEquipamentoReserva::mensagemRastreador($rastreador->id),
        );
    }

    public function test_reserva_bloqueia_movimentacao_manual_e_e_liberada_ao_cancelar(): void
    {
        [$operador, $cliente, $veiculo, $tecnico] = $this->cenarioBase();
        $disponivel = StatusRastreador::query()->where('label', 'Disponivel')->firstOrFail();
        $chip = Chip::query()->create([
            'numero_chip' => '5562999990052',
            'iccid' => '89550000000000000052',
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $disponivel->id,
        ]);
        $rastreador = Rastreador::query()->create([
            'imei' => '860000000000052',
            'chip_id' => $chip->id,
            'tecnico_id' => $tecnico->id,
            'is_estoque' => true,
            'status_rastreador_id' => $disponivel->id,
        ]);
        $ordem = app(OrdemServicoService::class)->criar($this->dadosOrdem($cliente, $veiculo), $operador)['ordem'];
        $ordem->update([
            'tecnico_id' => $tecnico->id,
            'rastreador_novo_id' => $rastreador->id,
            'chip_novo_id' => $chip->id,
            'status' => OrdemServicoStatus::EM_CONFERENCIA,
        ]);

        try {
            $rastreador->update(['tecnico_id' => null]);
            $this->fail('O estoque não deveria movimentar um rastreador reservado.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($ordem->numero_formatado, $exception->errors()['rastreador_id'][0]);
        }

        try {
            $chip->update(['tecnico_id' => null]);
            $this->fail('O estoque não deveria movimentar um chip reservado.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($ordem->numero_formatado, $exception->errors()['chip_id'][0]);
        }

        try {
            Veiculo::query()->create([
                'cliente_id' => $cliente->id,
                'veiculo' => 'Cadastro manual indevido',
                'placa' => 'OSX-0052',
                'rastreador_id' => $rastreador->id,
            ]);
            $this->fail('O cadastro manual não deveria consumir um rastreador reservado.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($ordem->numero_formatado, $exception->errors()['rastreador_id'][0]);
        }

        app(OrdemServicoService::class)->cancelar($ordem->fresh(), 'Atendimento cancelado para teste.', $operador);
        $rastreador->refresh()->update(['tecnico_id' => null]);
        $veiculoManual = Veiculo::query()->create([
            'cliente_id' => $cliente->id,
            'veiculo' => 'Cadastro após liberação',
            'placa' => 'OSX-0052',
            'rastreador_id' => $rastreador->id,
        ]);

        $this->assertSame(OrdemServicoStatus::CANCELADA, $ordem->fresh()->status);
        $this->assertSame($rastreador->id, $veiculoManual->rastreador_id);
        $this->assertNull(OrdemServicoEquipamentoReserva::ordemDoRastreador($rastreador->id));
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
