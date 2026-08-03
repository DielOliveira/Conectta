<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServico;
use App\Models\OrdemServicoNotificacao;
use App\Models\Pais;
use App\Services\Whatsapp\ZapiWhatsappService;
use Throwable;

class OrdemServicoNotificacaoService
{
    public function __construct(private readonly ZapiWhatsappService $whatsapp) {}

    public function registrarTecnico(OrdemServico $ordem, string $evento, string $mensagem): void
    {
        $this->registrar($ordem, 'tecnico', $evento, $this->telefoneComDdi($ordem->tecnico?->telefone, '55'), $mensagem);
    }

    public function registrarCliente(OrdemServico $ordem, string $evento, string $mensagem): void
    {
        if ($ordem->notificar_cliente) {
            $ddi = Pais::codigoTelefone($ordem->cliente?->telefone1_pais ?: 'BR');
            $this->registrar($ordem, 'cliente', $evento, $this->telefoneComDdi($ordem->cliente?->telefone1, $ddi), $mensagem);
        }
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
}
