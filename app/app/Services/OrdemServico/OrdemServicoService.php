<?php

namespace App\Services\OrdemServico;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Models\Chip;
use App\Models\OrdemServico;
use App\Models\OrdemServicoDisponibilidade;
use App\Models\OrdemServicoHistorico;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\Estoque\EquipamentoStatusWorkflow;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrdemServicoService
{
    public function __construct(private readonly OrdemServicoAgendaService $agenda, private readonly OrdemServicoNotificacaoService $notificacoes) {}

    /** @return array{ordem: OrdemServico, token: null} */
    public function criar(array $dados, User $operador): array
    {
        return DB::transaction(function () use ($dados, $operador): array {
            $veiculo = Veiculo::query()->with('rastreador.chip')->lockForUpdate()->findOrFail($dados['veiculo_id']);
            if ((int) $veiculo->cliente_id !== (int) $dados['cliente_id']) {
                throw ValidationException::withMessages(['veiculo_id' => 'O veículo não pertence ao cliente selecionado.']);
            }
            if (OrdemServico::query()->ativas()->where('veiculo_id', $veiculo->id)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['veiculo_id' => 'Este veículo já possui uma ordem de serviço ativa.']);
            }
            if (($dados['associado'] ?? false) && (blank($veiculo->associado) || ! $this->telefoneValido($veiculo->contato))) {
                throw ValidationException::withMessages(['associado' => 'Preencha o nome e o contato do associado no cadastro do veículo antes de prosseguir.']);
            }
            $telefoneAtendimento = ($dados['associado'] ?? false) ? $veiculo->contato : $veiculo->cliente?->telefone1;
            if (($dados['notificar_cliente'] ?? false) && ! $this->telefoneValido($telefoneAtendimento)) {
                throw ValidationException::withMessages(['notificar_cliente' => 'Corrija o telefone do cliente antes de ativar as notificações.']);
            }

            $tipo = OrdemServicoTipo::from($dados['tipo']);
            if ($tipo === OrdemServicoTipo::INSTALACAO && $veiculo->rastreador_id !== null) {
                throw ValidationException::withMessages(['tipo' => 'A instalação exige um veículo sem rastreador vinculado.']);
            }
            if ($tipo !== OrdemServicoTipo::INSTALACAO && ($veiculo->rastreador_id === null || $veiculo->rastreador?->chip_id === null)) {
                throw ValidationException::withMessages(['tipo' => 'Retirada e manutenção exigem rastreador e chip vinculados ao veículo.']);
            }

            [$latitude, $longitude] = $this->coordenadasDoLink($dados['localizacao_url'] ?? null);
            $dados['localizacao_latitude'] = $latitude;
            $dados['localizacao_longitude'] = $longitude;
            $numero = DB::table('ordem_servico_numeracoes')->insertGetId(['created_at' => now()]);
            $ordem = OrdemServico::query()->create(array_merge($dados, [
                'numero' => $numero, 'status' => OrdemServicoStatus::ABERTA,
                'rastreador_anterior_id' => $veiculo->rastreador_id,
                'chip_anterior_id' => $veiculo->rastreador?->chip_id,
            ]));
            $this->historico($ordem, 'abertura', null, OrdemServicoStatus::ABERTA, $operador);

            return ['ordem' => $ordem, 'token' => null];
        });
    }

    /** @return array{ordem: OrdemServico, token: string} */
    public function agendar(OrdemServico $ordem, OrdemServicoDisponibilidade $disponibilidade, CarbonImmutable $horario, User $operador): array
    {
        return DB::transaction(function () use ($ordem, $disponibilidade, $horario, $operador): array {
            $ordem = OrdemServico::query()->lockForUpdate()->findOrFail($ordem->id);
            $disponibilidade = OrdemServicoDisponibilidade::query()->with('tecnico')->lockForUpdate()->findOrFail($disponibilidade->id);
            if (! in_array($ordem->status, [OrdemServicoStatus::ABERTA, OrdemServicoStatus::ENVIADA, OrdemServicoStatus::ACEITA], true)) {
                throw ValidationException::withMessages(['status' => 'Esta ordem não pode mais ser agendada.']);
            }
            if (! $this->telefoneValido($disponibilidade->tecnico->telefone)) {
                throw ValidationException::withMessages(['tecnico_id' => 'Corrija o telefone do técnico antes de atribuir a ordem.']);
            }
            $this->agenda->validarBloco($disponibilidade, $horario, $ordem->id);
            $anterior = $ordem->status;
            $token = Str::random(64);
            $ordem->update([
                'tecnico_id' => $disponibilidade->tecnico_id, 'disponibilidade_id' => $disponibilidade->id,
                'agendado_em' => $horario, 'status' => OrdemServicoStatus::ENVIADA,
                'token_hash' => hash('sha256', $token), 'token_credencial' => $token, 'token_invalidado_em' => null, 'aceita_em' => null,
            ]);
            $this->historico($ordem, 'agendamento', $anterior, OrdemServicoStatus::ENVIADA, $operador);
            $this->notificacoes->registrarAtribuicaoTecnico($ordem, $token);

            return ['ordem' => $ordem->fresh(), 'token' => $token];
        });
    }

    /** @return array{ordem: OrdemServico, token: string} */
    public function agendarAbrindoHorario(OrdemServico $ordem, int $tecnicoId, CarbonImmutable $horario, User $operador): array
    {
        return DB::transaction(function () use ($ordem, $tecnicoId, $horario, $operador): array {
            $ordem = OrdemServico::query()->lockForUpdate()->findOrFail($ordem->id);
            $disponibilidade = $this->agenda->obterOuCriarHorario($tecnicoId, $horario);

            return $this->agendar($ordem, $disponibilidade, $horario, $operador);
        });
    }

    public function porToken(string $token): OrdemServico
    {
        return OrdemServico::query()->where('token_hash', hash('sha256', $token))->whereNull('token_invalidado_em')->firstOrFail();
    }

    public function aceitar(OrdemServico $ordem): void
    {
        $this->transicionarTecnico($ordem, OrdemServicoStatus::ENVIADA, OrdemServicoStatus::ACEITA, 'aceite', ['aceita_em' => now()]);
        $this->notificacoes->registrarAceiteCliente($ordem->refresh());
    }

    public function rejeitar(OrdemServico $ordem, string $motivo): void
    {
        if (blank(trim($motivo))) {
            throw ValidationException::withMessages(['motivo' => 'Informe o motivo da rejeição.']);
        }
        $this->transicionarTecnico($ordem, OrdemServicoStatus::ENVIADA, OrdemServicoStatus::ABERTA, 'rejeicao', [
            'tecnico_id' => null, 'disponibilidade_id' => null, 'agendado_em' => null,
            'token_invalidado_em' => now(), 'token_hash' => null, 'token_credencial' => null,
        ], $motivo);
    }

    public function iniciar(OrdemServico $ordem, ?float $latitude = null, ?float $longitude = null): void
    {
        if (OrdemServico::query()->where('tecnico_id', $ordem->tecnico_id)->whereKeyNot($ordem->id)->where('status', OrdemServicoStatus::EM_ATENDIMENTO->value)->exists()) {
            throw ValidationException::withMessages(['status' => 'Finalize ou interrompa o atendimento atual antes de iniciar outra OS.']);
        }
        $this->transicionarTecnico($ordem, OrdemServicoStatus::ACEITA, OrdemServicoStatus::EM_ATENDIMENTO, 'inicio_atendimento', [
            'iniciada_em' => now(), 'inicio_latitude' => $latitude, 'inicio_longitude' => $longitude,
        ]);
    }

    public function solicitarConferencia(OrdemServico $ordem): void
    {
        if (! in_array($ordem->status, [OrdemServicoStatus::EM_ATENDIMENTO, OrdemServicoStatus::PENDENTE], true)) {
            throw ValidationException::withMessages(['status' => 'A ordem não está disponível para solicitar conferência.']);
        }
        if ($ordem->fotos()->count() < 1) {
            throw ValidationException::withMessages(['fotos' => 'Envie pelo menos uma foto do atendimento.']);
        }
        if ($ordem->tipo === OrdemServicoTipo::INSTALACAO && $ordem->rastreador_novo_id === null) {
            throw ValidationException::withMessages(['rastreador_novo_id' => 'Informe o rastreador instalado.']);
        }
        if ($ordem->tipo === OrdemServicoTipo::INSTALACAO && $ordem->chip_novo_id === null) {
            throw ValidationException::withMessages(['chip_novo_id' => 'O rastreador precisa possuir um chip ou um chip deve ser selecionado.']);
        }
        if ($ordem->tipo !== OrdemServicoTipo::INSTALACAO && ! $ordem->equipamentos_confirmados) {
            throw ValidationException::withMessages(['equipamentos_confirmados' => 'Confirme o IMEI e o chip encontrados.']);
        }
        if ($ordem->tipo === OrdemServicoTipo::MANUTENCAO && (blank($ordem->resultado_manutencao) || blank($ordem->descricao_atendimento))) {
            throw ValidationException::withMessages(['descricao_atendimento' => 'Informe o resultado e a descrição da manutenção.']);
        }
        if ($ordem->tipo === OrdemServicoTipo::MANUTENCAO) {
            $this->validarResultadoManutencao($ordem);
        }
        $this->transicionarTecnico($ordem, $ordem->status, OrdemServicoStatus::EM_CONFERENCIA, 'solicitacao_conferencia', ['termino_tecnico_em' => now()]);
    }

    public function marcarPendente(OrdemServico $ordem, string $motivo, User $operador): void
    {
        if (blank(trim($motivo))) {
            throw ValidationException::withMessages(['motivo' => 'Informe o motivo da pendência.']);
        }
        $this->transicionarOperador($ordem, OrdemServicoStatus::EM_CONFERENCIA, OrdemServicoStatus::PENDENTE, 'conferencia_reprovada', $operador, ['motivo_pendencia' => $motivo], $motivo);
        $this->notificacoes->registrarPendenciaTecnico($ordem->refresh(), $motivo);
    }

    public function cancelarAgendamento(OrdemServico $ordem, User $operador): void
    {
        DB::transaction(function () use ($ordem, $operador): void {
            $ordem = OrdemServico::query()->with(['tecnico', 'cliente'])->lockForUpdate()->findOrFail($ordem->id);
            if (! in_array($ordem->status, [OrdemServicoStatus::ENVIADA, OrdemServicoStatus::ACEITA], true)) {
                throw ValidationException::withMessages(['status' => 'Este agendamento não pode mais ser cancelado.']);
            }

            $anterior = $ordem->status;
            $tecnicoId = $ordem->tecnico_id;
            $agendadoEm = $ordem->agendado_em;
            $ordem->update([
                'status' => OrdemServicoStatus::ABERTA,
                'tecnico_id' => null,
                'disponibilidade_id' => null,
                'agendado_em' => null,
                'aceita_em' => null,
                'token_invalidado_em' => now(),
                'token_hash' => null,
                'token_credencial' => null,
            ]);
            $this->historico($ordem, 'cancelamento_agendamento', $anterior, OrdemServicoStatus::ABERTA, $operador, $tecnicoId);
            $this->notificacoes->registrarCancelamentoAgendamentoTecnico($ordem, $agendadoEm);
        });
    }

    public function finalizar(OrdemServico $ordem, User $operador, array $checklist = []): void
    {
        DB::transaction(function () use ($ordem, $operador, $checklist): void {
            $ordem = OrdemServico::query()->with(['veiculo', 'rastreadorNovo'])->lockForUpdate()->findOrFail($ordem->id);
            if ($ordem->status !== OrdemServicoStatus::EM_CONFERENCIA) {
                throw ValidationException::withMessages(['status' => 'A ordem não está em conferência.']);
            }
            if ($ordem->tipo !== OrdemServicoTipo::RETIRADA) {
                $ordem->update([
                    'check_funcionamento' => (bool) ($checklist['check_funcionamento'] ?? false),
                    'check_pos_chave' => (bool) ($checklist['check_pos_chave'] ?? false),
                    'check_bloqueio' => in_array($checklist['check_bloqueio'] ?? null, ['conferido', 'nao_se_aplica'], true) ? $checklist['check_bloqueio'] : null,
                ]);
            }
            if ($ordem->tipo !== OrdemServicoTipo::RETIRADA && (! $ordem->check_funcionamento || ! $ordem->check_pos_chave || ! in_array($ordem->check_bloqueio, ['conferido', 'nao_se_aplica'], true))) {
                throw ValidationException::withMessages(['checklist' => 'Conclua todos os itens obrigatórios da conferência.']);
            }
            EquipamentoStatusWorkflow::executar(
                fn () => OrdemServicoEquipamentoReserva::duranteOrdem(
                    $ordem->id,
                    fn () => $this->movimentarEquipamentos($ordem),
                ),
            );
            $anterior = $ordem->status;
            $ordem->update(['status' => OrdemServicoStatus::FINALIZADA, 'finalizada_em' => now(), 'finalizada_por' => $operador->id]);
            $this->historico($ordem, 'finalizacao', $anterior, OrdemServicoStatus::FINALIZADA, $operador);
            $this->notificacoes->registrarFinalizacaoTecnico($ordem);
            $this->notificacoes->registrarFinalizacaoCliente($ordem);
        });
    }

    public function cancelar(OrdemServico $ordem, string $motivo, User $operador): void
    {
        if (blank(trim($motivo))) {
            throw ValidationException::withMessages(['motivo' => 'Informe o motivo do cancelamento.']);
        }
        if ($ordem->status->isFinal()) {
            throw ValidationException::withMessages(['status' => 'Esta ordem já está encerrada.']);
        }
        $this->transicionarOperador($ordem, $ordem->status, OrdemServicoStatus::CANCELADA, 'cancelamento', $operador, [
            'cancelada_em' => now(), 'cancelada_por' => $operador->id, 'motivo_cancelamento' => $motivo,
            'token_invalidado_em' => now(), 'token_hash' => null, 'token_credencial' => null,
        ], $motivo);
        $ordem->refresh()->loadMissing(['tecnico', 'cliente']);
        if ($ordem->tecnico_id) {
            $this->notificacoes->registrarCancelamentoTecnico($ordem, $motivo);
        }
        if ($ordem->aceita_em) {
            $this->notificacoes->registrarCancelamentoCliente($ordem);
        }
    }

    public function reenviarLink(OrdemServico $ordem): void
    {
        if ($ordem->status->isFinal() || blank($ordem->token_credencial) || blank($ordem->tecnico_id)) {
            throw ValidationException::withMessages(['status' => 'Não há link ativo para reenviar.']);
        }
        $this->notificacoes->registrarReenvioTecnico($ordem);
    }

    public function cadastroCorrigido(OrdemServico $ordem, User $operador): void
    {
        $ordem->load('veiculo.rastreador.chip');
        if ($ordem->status !== OrdemServicoStatus::AGUARDANDO_CORRECAO_CADASTRAL || ! $ordem->veiculo->rastreador_id || ! $ordem->veiculo->rastreador?->chip_id) {
            throw ValidationException::withMessages(['cadastro' => 'O veículo ainda não possui rastreador e chip corretamente vinculados.']);
        }
        $ordem->update(['rastreador_anterior_id' => $ordem->veiculo->rastreador_id, 'chip_anterior_id' => $ordem->veiculo->rastreador->chip_id]);
        $this->transicionarOperador($ordem, OrdemServicoStatus::AGUARDANDO_CORRECAO_CADASTRAL, OrdemServicoStatus::EM_ATENDIMENTO, 'correcao_cadastral', $operador);
        $this->notificacoes->registrarCorrecaoCadastralTecnico($ordem->refresh());
    }

    private function movimentarEquipamentos(OrdemServico $ordem): void
    {
        $disponivel = StatusRastreador::query()->where('label', 'Disponivel')->value('id');
        $ativo = StatusRastreador::query()->where('label', 'Ativo')->value('id');
        $chipInstaladoId = $ordem->chip_novo_id;
        if ($ordem->tipo === OrdemServicoTipo::MANUTENCAO && $ordem->rastreador_novo_id !== null && $chipInstaladoId === null) {
            $chipInstaladoId = $ordem->chip_anterior_id;
        }
        if ($ordem->rastreador_novo_id !== null) {
            $rastreador = $ordem->rastreadorNovo()->lockForUpdate()->firstOrFail();
            if ((int) $rastreador->tecnico_id !== (int) $ordem->tecnico_id || ! $rastreador->is_estoque || (int) $rastreador->status_rastreador_id !== (int) $disponivel) {
                throw ValidationException::withMessages(['rastreador_novo_id' => 'O rastreador não está mais disponível no estoque do técnico.']);
            }
        }
        if ($ordem->chip_novo_id !== null) {
            $chip = $ordem->chipNovo()->lockForUpdate()->firstOrFail();
            $chipJaVinculadoAoRastreador = $ordem->tipo === OrdemServicoTipo::INSTALACAO && $rastreador->chip_id === $chip->id;
            if ((int) $chip->status_rastreador_id !== (int) $disponivel || (! $chipJaVinculadoAoRastreador && (int) $chip->tecnico_id !== (int) $ordem->tecnico_id)) {
                throw ValidationException::withMessages(['chip_novo_id' => 'O chip não está mais disponível no estoque do técnico.']);
            }
            if ($ordem->tipo === OrdemServicoTipo::INSTALACAO) {
                $rastreadorDoChip = Rastreador::query()->where('chip_id', $chip->id)->lockForUpdate()->first();
                if ($rastreadorDoChip && $rastreadorDoChip->id !== $ordem->rastreador_novo_id) {
                    throw ValidationException::withMessages(['chip_novo_id' => 'O chip foi vinculado a outro rastreador. Retorne a OS ao técnico para escolher outro chip.']);
                }
                if ($rastreador->chip_id !== null && $rastreador->chip_id !== $chip->id) {
                    throw ValidationException::withMessages(['chip_novo_id' => 'Use o chip que já está vinculado ao rastreador selecionado.']);
                }
            }
            Rastreador::query()->where('chip_id', $chip->id)->when($ordem->rastreador_novo_id, fn ($q) => $q->whereKeyNot($ordem->rastreador_novo_id))->update(['chip_id' => null]);
        }
        if ($ordem->tipo === OrdemServicoTipo::RETIRADA) {
            $ordem->veiculo->update(['rastreador_id' => null, 'tecnico_remocao_id' => $ordem->tecnico_id, 'data_retirada' => today()]);
            $ordem->rastreadorAnterior()->update([
                'tecnico_id' => $ordem->tecnico_id,
                'status_rastreador_id' => $disponivel,
                'is_estoque' => true,
            ]);
            $ordem->chipAnterior()->update(['tecnico_id' => $ordem->tecnico_id, 'status_rastreador_id' => $disponivel]);

            return;
        }
        if ($ordem->tipo === OrdemServicoTipo::MANUTENCAO && $ordem->rastreador_novo_id === null && $ordem->chip_novo_id !== null) {
            $ordem->rastreadorAnterior()->update(['chip_id' => $ordem->chip_novo_id]);
            $ordem->chipNovo()->update(['tecnico_id' => null, 'status_rastreador_id' => $ativo]);
            if ($ordem->chip_anterior_id !== $ordem->chip_novo_id) {
                $ordem->chipAnterior()->update(['tecnico_id' => $ordem->tecnico_id, 'status_rastreador_id' => $disponivel]);
            }

            return;
        }
        if ($ordem->rastreador_novo_id !== null) {
            if ($chipInstaladoId !== null) {
                Rastreador::query()
                    ->where('chip_id', $chipInstaladoId)
                    ->whereKeyNot($ordem->rastreador_novo_id)
                    ->update(['chip_id' => null]);
            }
            $ordem->rastreadorNovo()->update([
                'chip_id' => $chipInstaladoId,
                'tecnico_id' => null,
                'status_rastreador_id' => $ativo,
                'is_estoque' => false,
            ]);
            if ($chipInstaladoId !== null) {
                Chip::query()->whereKey($chipInstaladoId)->update(['tecnico_id' => null, 'status_rastreador_id' => $ativo]);
            }
            $ordem->veiculo->update([
                'rastreador_id' => $ordem->rastreador_novo_id,
                'status_rastreador_id' => $ativo,
                'tecnico_instala_id' => $ordem->tecnico_id,
                'data_instalacao' => today(),
                'tecnico_remocao_id' => null,
                'data_retirada' => null,
            ]);
        }
        if ($ordem->rastreador_anterior_id !== null && $ordem->rastreador_anterior_id !== $ordem->rastreador_novo_id) {
            $ordem->rastreadorAnterior()->update([
                'tecnico_id' => $ordem->tecnico_id,
                'status_rastreador_id' => $disponivel,
                'is_estoque' => true,
            ]);
        }
        if ($ordem->chip_anterior_id !== null && $ordem->chip_anterior_id !== $chipInstaladoId) {
            $ordem->chipAnterior()->update(['tecnico_id' => $ordem->tecnico_id, 'status_rastreador_id' => $disponivel]);
        }
    }

    private function transicionarTecnico(OrdemServico $ordem, OrdemServicoStatus $esperado, OrdemServicoStatus $novo, string $evento, array $dados = [], ?string $observacao = null): void
    {
        DB::transaction(function () use ($ordem, $esperado, $novo, $evento, $dados, $observacao): void {
            $ordem = OrdemServico::query()->lockForUpdate()->findOrFail($ordem->id);
            if ($ordem->status !== $esperado) {
                throw ValidationException::withMessages(['status' => 'Esta ação não está mais disponível.']);
            }
            $ordem->update(array_merge($dados, ['status' => $novo]));
            $this->historico($ordem, $evento, $esperado, $novo, null, $ordem->tecnico_id, $observacao);
        });
    }

    private function transicionarOperador(OrdemServico $ordem, OrdemServicoStatus $esperado, OrdemServicoStatus $novo, string $evento, User $operador, array $dados = [], ?string $observacao = null): void
    {
        DB::transaction(function () use ($ordem, $esperado, $novo, $evento, $operador, $dados, $observacao): void {
            $ordem = OrdemServico::query()->lockForUpdate()->findOrFail($ordem->id);
            if ($ordem->status !== $esperado) {
                throw ValidationException::withMessages(['status' => 'Esta ação não está mais disponível.']);
            }
            $ordem->update(array_merge($dados, ['status' => $novo]));
            $this->historico($ordem, $evento, $esperado, $novo, $operador, null, $observacao);
        });
    }

    private function historico(OrdemServico $ordem, string $evento, ?OrdemServicoStatus $anterior, OrdemServicoStatus $novo, ?User $user = null, ?int $tecnicoId = null, ?string $observacao = null): void
    {
        OrdemServicoHistorico::query()->create(['ordem_servico_id' => $ordem->id, 'evento' => $evento,
            'status_anterior' => $anterior?->value, 'status_novo' => $novo->value, 'user_id' => $user?->id,
            'tecnico_id' => $tecnicoId, 'observacao' => $observacao]);
    }

    private function telefoneValido(?string $telefone): bool
    {
        return strlen(preg_replace('/\D+/', '', (string) $telefone) ?? '') >= 10;
    }

    private function validarResultadoManutencao(OrdemServico $ordem): void
    {
        $rastreador = $ordem->rastreador_novo_id !== null;
        $chip = $ordem->chip_novo_id !== null;
        $valido = match ($ordem->resultado_manutencao) {
            'reparo_sem_troca', 'sem_defeito' => ! $rastreador && ! $chip,
            'troca_rastreador' => $rastreador,
            'troca_chip' => ! $rastreador && $chip,
            'troca_rastreador_chip' => $rastreador && $chip,
            default => false,
        };
        if (! $valido) {
            throw ValidationException::withMessages(['resultado_manutencao' => 'Os equipamentos informados não correspondem ao resultado da manutenção.']);
        }
    }

    /** @return array{?float, ?float} */
    private function coordenadasDoLink(?string $link): array
    {
        if (blank($link)) {
            return [null, null];
        }

        $coordenadas = $this->extrairCoordenadas($link);
        if ($coordenadas !== [null, null]) {
            return $coordenadas;
        }

        $urlAtual = $link;
        try {
            for ($redirecionamentos = 0; $redirecionamentos < 5; $redirecionamentos++) {
                if (! $this->hostGoogleMapsPermitido($urlAtual)) {
                    break;
                }
                $resposta = Http::connectTimeout(3)->timeout(5)->withoutRedirecting()->head($urlAtual);
                $destino = $resposta->header('Location');
                if (blank($destino) || ! $this->hostGoogleMapsPermitido($destino)) {
                    break;
                }
                $coordenadas = $this->extrairCoordenadas($destino);
                if ($coordenadas !== [null, null]) {
                    return $coordenadas;
                }
                $urlAtual = $destino;
            }
        } catch (Throwable) {
            // O link continua salvo e o endereço será usado como alternativa.
        }

        return [null, null];
    }

    /** @return array{?float, ?float} */
    private function extrairCoordenadas(string $link): array
    {
        $decodificado = urldecode($link);
        $encontrou = preg_match('/!3d(-?\d{1,2}(?:\.\d+)?)!4d(-?\d{1,3}(?:\.\d+)?)/', $decodificado, $matches) === 1
            || preg_match('/(?:@|query=|q=)(-?\d{1,2}(?:\.\d+)?)[,\s]+(-?\d{1,3}(?:\.\d+)?)/', $decodificado, $matches) === 1;
        if (! $encontrou) {
            return [null, null];
        }
        $latitude = (float) $matches[1];
        $longitude = (float) $matches[2];

        return abs($latitude) <= 90 && abs($longitude) <= 180 ? [$latitude, $longitude] : [null, null];
    }

    private function hostGoogleMapsPermitido(string $url): bool
    {
        return in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), [
            'maps.app.goo.gl',
            'goo.gl',
            'google.com',
            'www.google.com',
            'maps.google.com',
        ], true);
    }
}
