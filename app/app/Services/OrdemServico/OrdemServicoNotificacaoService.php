<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServico;
use App\Models\OrdemServicoNotificacao;
use App\Models\Pais;
use App\Services\Whatsapp\WhatsappService;
use Carbon\CarbonInterface;
use Throwable;

class OrdemServicoNotificacaoService
{
    public function __construct(private readonly WhatsappService $whatsapp) {}

    public function registrarTecnico(OrdemServico $ordem, string $evento, string $mensagem): void
    {
        $this->registrar($ordem, 'tecnico', $evento, $this->telefoneComDdi($ordem->tecnico?->telefone, '55'), $mensagem);
    }

    public function registrarCliente(OrdemServico $ordem, string $evento, string $mensagem): void
    {
        if ($ordem->notificar_cliente) {
            $ddi = Pais::codigoTelefone($ordem->telefone_pais_atendimento);
            $this->registrar($ordem, 'cliente', $evento, $this->telefoneComDdi($ordem->telefone_atendimento, $ddi), $mensagem);
        }
    }

    public function registrarAtribuicaoTecnico(OrdemServico $ordem, string $token): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarTecnico($ordem, 'atribuicao', implode("\n", [
            "Olá, {$ordem->tecnico->nome}! Tudo bem?",
            '',
            'Você recebeu uma nova ordem de serviço da *Conectta Rastreamento*.',
            '',
            "🔧 *{$ordem->numero_formatado} — {$ordem->tipo->label()}*",
            "Cliente: {$ordem->nome_atendimento}",
            'Veículo: '.$this->veiculo($ordem),
            'Data: '.$ordem->agendado_em->format('d/m/Y'),
            'Horário: '.$ordem->agendado_em->format('H:i'),
            "Endereço: {$ordem->endereco}",
            "Serviço: {$ordem->descricao}",
            '',
            'Acesse o link abaixo para consultar os detalhes e aceitar ou rejeitar o atendimento:',
            $this->link($token),
            '',
            'Este link é pessoal e deve ser utilizado durante todo o atendimento.',
        ]));
    }

    public function registrarAceiteCliente(OrdemServico $ordem): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarCliente($ordem, 'aceite', implode("\n", [
            "Olá, {$ordem->nome_atendimento}!",
            'Aqui é da *Conectta Rastreamento*. Tudo bem?',
            '',
            'Seu atendimento foi confirmado.',
            '',
            "🔧 Serviço: {$ordem->tipo->label()}",
            '🚗 Veículo: '.$this->veiculo($ordem),
            '📅 Data: '.$ordem->agendado_em->format('d/m/Y'),
            '🕐 Horário: '.$ordem->agendado_em->format('H:i'),
            "👤 Técnico responsável: {$ordem->tecnico->nome}",
            "📍 Local: {$ordem->endereco}",
            '',
            'Pedimos que alguém responsável esteja disponível no local no horário combinado.',
            '',
            'Atenciosamente,',
            '*Conectta Rastreamento*',
        ]));
    }

    public function registrarPendenciaTecnico(OrdemServico $ordem, string $motivo): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarTecnico($ordem, 'pendencia', implode("\n", [
            "Olá, {$ordem->tecnico->nome}!",
            '',
            "A central analisou a *{$ordem->numero_formatado}* e identificou uma pendência.",
            '',
            "Cliente: {$ordem->nome_atendimento}",
            'Veículo: '.$this->veiculo($ordem),
            "Pendência: {$motivo}",
            '',
            'Acesse novamente a ordem para realizar a correção:',
            $this->link((string) $ordem->token_credencial),
        ]));
    }

    public function registrarLembreteTecnico(OrdemServico $ordem): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarTecnico($ordem, 'lembrete_2h', implode("\n", [
            "Olá, {$ordem->tecnico->nome}!",
            '',
            "Passando para lembrar que a *{$ordem->numero_formatado}* está próxima.",
            '',
            "Cliente: {$ordem->nome_atendimento}",
            'Veículo: '.$this->veiculo($ordem),
            'Data e horário: '.$ordem->agendado_em->format('d/m/Y \à\s H:i'),
            "Endereço: {$ordem->endereco}",
            '',
            'Consulte os detalhes do atendimento:',
            $this->link((string) $ordem->token_credencial),
        ]));
    }

    public function registrarCancelamentoAgendamentoTecnico(OrdemServico $ordem, ?CarbonInterface $agendadoEm): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarTecnico($ordem, 'cancelamento_agendamento', implode("\n", [
            "Olá, {$ordem->tecnico->nome}!",
            '',
            "O seu agendamento da *{$ordem->numero_formatado}* foi cancelado pela central.",
            '',
            "Cliente: {$ordem->nome_atendimento}",
            'Veículo: '.$this->veiculo($ordem),
            'Data e horário anteriores: '.($agendadoEm?->format('d/m/Y \à\s H:i') ?? 'Não informado'),
            '',
            'Nenhuma ação adicional é necessária.',
        ]));
    }

    public function registrarFinalizacaoTecnico(OrdemServico $ordem): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarTecnico($ordem, 'finalizacao', implode("\n", [
            "Olá, {$ordem->tecnico->nome}!",
            '',
            "A central realizou a conferência e finalizou a *{$ordem->numero_formatado}* com sucesso.",
            '',
            "Cliente: {$ordem->nome_atendimento}",
            'Veículo: '.$this->veiculo($ordem),
            "Serviço: {$ordem->tipo->label()}",
            '',
            'Obrigado pelo atendimento!',
        ]));
    }

    public function registrarFinalizacaoCliente(OrdemServico $ordem): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarCliente($ordem, 'finalizacao', implode("\n", [
            "Olá, {$ordem->nome_atendimento}!",
            'Aqui é da *Conectta Rastreamento*.',
            '',
            'Seu atendimento foi concluído com sucesso.',
            '',
            "✅ *{$ordem->numero_formatado} finalizada*",
            "🔧 Serviço: {$ordem->tipo->label()}",
            '🚗 Veículo: '.$this->veiculo($ordem),
            "👤 Técnico responsável: {$ordem->tecnico->nome}",
            '📅 Conclusão: '.$ordem->finalizada_em->format('d/m/Y \à\s H:i'),
            '',
            'Agradecemos pela confiança.',
            '',
            'Atenciosamente,',
            '*Conectta Rastreamento*',
        ]));
    }

    public function registrarCancelamentoTecnico(OrdemServico $ordem, string $motivo): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarTecnico($ordem, 'cancelamento', implode("\n", [
            "Olá, {$ordem->tecnico->nome}!",
            '',
            "A *{$ordem->numero_formatado}* foi cancelada pela central.",
            '',
            "Cliente: {$ordem->nome_atendimento}",
            'Veículo: '.$this->veiculo($ordem),
            "Motivo: {$motivo}",
            '',
            'O link dessa ordem de serviço não está mais disponível.',
        ]));
    }

    public function registrarCancelamentoCliente(OrdemServico $ordem): void
    {
        $ordem->loadMissing(['cliente', 'veiculo']);
        $linhas = [
            "Olá, {$ordem->nome_atendimento}!",
            'Aqui é da *Conectta Rastreamento*.',
            '',
            "Informamos que o atendimento referente à *{$ordem->numero_formatado}* foi cancelado.",
            '',
            "🔧 Serviço: {$ordem->tipo->label()}",
            '🚗 Veículo: '.$this->veiculo($ordem),
        ];
        if ($ordem->agendado_em) {
            $linhas[] = '📅 Agendamento: '.$ordem->agendado_em->format('d/m/Y \à\s H:i');
        }
        $this->registrarCliente($ordem, 'cancelamento', implode("\n", [
            ...$linhas,
            '',
            'Caso seja necessário um novo atendimento, entre em contato conosco.',
            '',
            'Atenciosamente,',
            '*Conectta Rastreamento*',
        ]));
    }

    public function registrarReenvioTecnico(OrdemServico $ordem): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarTecnico($ordem, 'reenvio_link', implode("\n", [
            "Olá, {$ordem->tecnico->nome}!",
            '',
            "Conforme solicitado, segue novamente o acesso à *{$ordem->numero_formatado}*.",
            '',
            "Cliente: {$ordem->nome_atendimento}",
            'Veículo: '.$this->veiculo($ordem),
            'Data e horário: '.$ordem->agendado_em->format('d/m/Y \à\s H:i'),
            '',
            'Acesse a ordem de serviço:',
            $this->link((string) $ordem->token_credencial),
        ]));
    }

    public function registrarCorrecaoCadastralTecnico(OrdemServico $ordem): void
    {
        $ordem->loadMissing(['cliente', 'veiculo', 'tecnico']);
        $this->registrarTecnico($ordem, 'correcao_cadastral', implode("\n", [
            "Olá, {$ordem->tecnico->nome}!",
            '',
            "O cadastro de equipamento da *{$ordem->numero_formatado}* foi corrigido pela central.",
            '',
            "Cliente: {$ordem->nome_atendimento}",
            'Veículo: '.$this->veiculo($ordem),
            '',
            'Você já pode retomar o atendimento utilizando o mesmo link:',
            $this->link((string) $ordem->token_credencial),
        ]));
    }

    public function link(string $token): string
    {
        return route('ordens-servico.tecnico', ['token' => $token]);
    }

    public function processarPendentes(?int $limite = 50): array
    {
        $resultado = ['enviadas' => 0, 'erros' => 0];
        OrdemServicoNotificacao::query()->where('status', 'pendente')->oldest()->limit($limite)->get()->each(function (OrdemServicoNotificacao $item) use (&$resultado): void {
            try {
                $this->whatsapp->enviarTexto((string) $item->telefone, $item->mensagem);
                $item->update(['status' => 'enviada', 'enviada_em' => now(), 'tentativas' => $item->tentativas + 1, 'erro' => null]);
                $resultado['enviadas']++;
            } catch (Throwable $e) {
                $item->update(['status' => 'erro', 'tentativas' => $item->tentativas + 1, 'erro' => $e->getMessage()]);
                $resultado['erros']++;
            }
        });

        return $resultado;
    }

    private function registrar(OrdemServico $ordem, string $tipo, string $evento, ?string $telefone, string $mensagem): void
    {
        OrdemServicoNotificacao::query()->create(['ordem_servico_id' => $ordem->id, 'destinatario_tipo' => $tipo,
            'evento' => $evento, 'telefone' => $telefone, 'mensagem' => $mensagem]);
    }

    private function telefoneComDdi(?string $telefone, string $ddi): string
    {
        $numero = preg_replace('/\D+/', '', (string) $telefone) ?? '';

        return str_starts_with($numero, $ddi) ? $numero : $ddi.$numero;
    }

    private function veiculo(OrdemServico $ordem): string
    {
        $descricao = trim((string) $ordem->veiculo?->veiculo) ?: 'Veículo não informado';
        $placa = trim((string) $ordem->veiculo?->placa);

        return $placa === '' ? $descricao : "{$descricao} — {$placa}";
    }
}
