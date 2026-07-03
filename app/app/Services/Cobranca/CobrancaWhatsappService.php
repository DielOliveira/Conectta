<?php

namespace App\Services\Cobranca;

use App\Models\Cliente;
use App\Models\CobrancaEnvio;
use App\Models\CobrancaMensagemModelo;
use App\Models\Invoice;
use App\Models\Lancamento;
use App\Services\Lytex\LytexException;
use App\Services\Lytex\LytexInvoiceData;
use App\Services\Lytex\LytexInvoiceService;
use App\Services\Whatsapp\WhatsappException;
use App\Services\Whatsapp\ZapiWhatsappService;
use Illuminate\Database\Eloquent\Builder;

class CobrancaWhatsappService
{
    public function __construct(
        private readonly ZapiWhatsappService $whatsapp,
        private readonly LytexInvoiceService $lytex,
    )
    {
    }

    /**
     * @return array{processados:int,enviados:int,simulados:int,erros:int}
     */
    public function enviarPendentes(?int $limit = null, ?int $envioId = null, ?int $clienteId = null, bool $dryRun = true, ?int $execucaoId = null): array
    {
        $contadores = [
            'processados' => 0,
            'enviados' => 0,
            'simulados' => 0,
            'erros' => 0,
        ];

        $query = CobrancaEnvio::query()
            ->with(['cliente', 'lancamento', 'invoice'])
            ->where('status', 'pendente_whatsapp')
            ->when($envioId !== null, fn (Builder $query): Builder => $query->whereKey($envioId))
            ->when($clienteId !== null, fn (Builder $query): Builder => $query->where('cliente_id', $clienteId))
            ->when($execucaoId !== null, fn (Builder $query): Builder => $query->where('cobranca_execucao_id', $execucaoId))
            ->oldest('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        foreach ($query->get() as $envio) {
            $contadores['processados']++;

            try {
                $resultado = $this->enviar($envio, $dryRun);
                $contadores[$resultado]++;
            } catch (WhatsappException $exception) {
                $envio->update([
                    'status' => 'erro',
                    'erro' => $exception->getMessage(),
                    'processado_em' => now(),
                ]);

                $contadores['erros']++;
            }
        }

        return $contadores;
    }

    private function enviar(CobrancaEnvio $envio, bool $dryRun): string
    {
        $cliente = $envio->cliente;
        $lancamento = $envio->lancamento;

        if (! $cliente || ! $lancamento) {
            throw new WhatsappException('Cliente ou lancamento nao encontrado para o envio.');
        }

        $etapas = $this->etapas($envio, $cliente, $lancamento);

        if ($dryRun) {
            $envio->update([
                'whatsapp_payload' => json_encode($etapas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'whatsapp_response' => json_encode(['simulado' => true], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'processado_em' => now(),
            ]);

            return 'simulados';
        }

        $responses = [];

        foreach ($etapas as $etapa) {
            $responses[] = match ($etapa['tipo']) {
                'texto' => $this->whatsapp->enviarTexto($etapa['telefone'], $etapa['mensagem']),
                'documento' => $this->whatsapp->enviarDocumentoPdf($etapa['telefone'], $etapa['documento'], $etapa['nome_arquivo']),
                'pix' => $this->whatsapp->enviarPix($etapa['telefone'], $etapa['pix']),
            };
        }

        $envio->update([
            'status' => 'enviado',
            'enviado_em' => now(),
            'processado_em' => now(),
            'erro' => null,
            'whatsapp_payload' => json_encode($etapas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'whatsapp_response' => json_encode($responses, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return 'enviados';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function etapas(CobrancaEnvio $envio, Cliente $cliente, Lancamento $lancamento): array
    {
        $telefone = trim((string) $envio->telefone);

        if ($telefone === '') {
            throw new WhatsappException('Telefone do envio nao informado.');
        }

        $mensagem = trim((string) $envio->mensagem);

        if ($mensagem === '') {
            throw new WhatsappException('Mensagem principal nao informada.');
        }

        $etapas = [
            [
                'tipo' => 'texto',
                'telefone' => $telefone,
                'mensagem' => $mensagem,
            ],
        ];

        if ($envio->tipo === CobrancaAutomaticaService::LEMBRETE_VENCIMENTO) {
            return $etapas;
        }

        $invoice = $envio->invoice;

        if (! $invoice) {
            throw new WhatsappException('Invoice nao encontrada para envio do boleto.');
        }

        $invoice = $this->garantirDadosPagamento($invoice);
        $documento = $this->documentoPdfUrl($envio, $invoice);
        $linhaDigitavel = trim((string) $invoice->linha_digitavel);
        $pix = trim((string) $invoice->pix_copia_cola);

        if ($documento === '') {
            throw new WhatsappException('Link do boleto nao informado.');
        }

        if ($linhaDigitavel === '') {
            throw new WhatsappException('Linha digitavel nao informada na invoice.');
        }

        if ($pix === '') {
            throw new WhatsappException('PIX copia e cola nao informado na invoice.');
        }

        $etapas[] = [
            'tipo' => 'documento',
            'telefone' => $telefone,
            'documento' => $documento,
            'nome_arquivo' => $this->nomeArquivoBoleto($lancamento),
        ];

        $etapas[] = [
            'tipo' => 'texto',
            'telefone' => $telefone,
            'mensagem' => $linhaDigitavel,
        ];

        $etapas[] = [
            'tipo' => 'texto',
            'telefone' => $telefone,
            'mensagem' => $this->modeloMensagem('pix_instrucao', 'Caso prefira pagar com PIX, segue o codigo copia e cola:'),
        ];

        $etapas[] = [
            'tipo' => 'pix',
            'telefone' => $telefone,
            'pix' => $pix,
        ];

        $etapas[] = [
            'tipo' => 'texto',
            'telefone' => $telefone,
            'mensagem' => $this->modeloMensagem('finalizacao', "Atendimento finalizado\n\nEstou finalizando nossa interacao, qualquer duvida estou a disposicao"),
        ];

        return $etapas;
    }

    private function garantirDadosPagamento(Invoice $invoice): Invoice
    {
        if (filled($invoice->linha_digitavel) && filled($invoice->pix_copia_cola)) {
            return $invoice;
        }

        if (blank($invoice->fatura_id)) {
            return $invoice;
        }

        try {
            $response = $this->lytex->detalhesFatura($invoice->fatura_id);
        } catch (LytexException $exception) {
            throw new WhatsappException('Nao foi possivel buscar detalhes da invoice na Lytex: '.$exception->getMessage());
        }

        $invoice->update([
            'linha_digitavel' => $invoice->linha_digitavel ?: LytexInvoiceData::linhaDigitavel($response),
            'pix_copia_cola' => $invoice->pix_copia_cola ?: LytexInvoiceData::pixCopiaCola($response),
            'link_checkout' => $invoice->link_checkout ?: data_get($response, 'linkCheckout'),
            'link_boleto' => $invoice->link_boleto ?: data_get($response, 'linkBoleto'),
        ]);

        return $invoice->fresh();
    }

    private function documentoPdfUrl(CobrancaEnvio $envio, Invoice $invoice): string
    {
        $url = trim((string) ($envio->link_boleto ?: $invoice->link_boleto));

        if ($url === '') {
            return '';
        }

        return str($url)->lower()->endsWith('.pdf') ? $url : $url.'.PDF';
    }

    private function nomeArquivoBoleto(Lancamento $lancamento): string
    {
        $meses = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Marco',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return 'BoletoConectta_'.($meses[(int) $lancamento->mes_referencia] ?? 'Mensalidade').'.pdf';
    }

    private function modeloMensagem(string $tipo, string $padrao): string
    {
        return (string) (CobrancaMensagemModelo::query()
            ->where('tipo', $tipo)
            ->where('canal', 'whatsapp')
            ->where('ativo', true)
            ->orderBy('ordem')
            ->value('conteudo') ?: $padrao);
    }
}
