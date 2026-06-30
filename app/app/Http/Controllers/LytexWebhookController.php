<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoIntegracao;
use App\Models\Invoice;
use App\Models\LytexWebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LytexWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $webhookType = (string) data_get($payload, 'webhookType', '');
        $signature = (string) data_get($payload, 'signature', '');
        $data = data_get($payload, 'data');
        $invoiceExternalId = (string) data_get($data, 'invoiceId', '');
        $referenceId = (string) data_get($data, 'referenceId', '');
        $status = (string) data_get($data, 'status', '');

        $log = LytexWebhookLog::query()->create([
            'webhook_type' => $webhookType ?: null,
            'signature' => $signature ?: null,
            'invoice_external_id' => $invoiceExternalId ?: null,
            'reference_id' => $referenceId ?: null,
            'status' => $status ?: null,
            'payload' => $payload,
            'is_valid' => false,
            'processed' => false,
            'message' => 'Recebido.',
        ]);

        if ($webhookType === '' || $signature === '' || ! is_array($data)) {
            $log->update(['message' => 'Payload invalido: webhookType, signature ou data ausente.']);

            return response()->json(['message' => 'Payload invalido.'], 422);
        }

        $configuracao = $this->configuracaoValida($data, $signature);

        if ($configuracao === null) {
            $log->update(['message' => 'Assinatura invalida ou callbackSecret nao configurado.']);

            return response()->json(['message' => 'Assinatura invalida.'], 401);
        }

        $log->update([
            'configuracao_integracao_id' => $configuracao->id,
            'is_valid' => true,
        ]);

        if ($webhookType === 'createInvoice') {
            $log->update([
                'processed' => true,
                'message' => 'Evento createInvoice ignorado: invoices sao criadas pelo Conectta.',
            ]);

            return response()->json(['message' => 'Evento ignorado.']);
        }

        if (! in_array($webhookType, ['liquidateInvoice', 'scheduleInvoicePayment', 'cancelInvoice'], true)) {
            $log->update(['message' => 'Evento nao tratado: ' . $webhookType]);

            return response()->json(['message' => 'Evento nao tratado.'], 202);
        }

        $invoice = $this->localizarInvoice($invoiceExternalId, $referenceId);

        if ($invoice === null) {
            $log->update(['message' => 'Invoice nao encontrada para o webhook.']);

            return response()->json(['message' => 'Invoice nao encontrada.'], 202);
        }

        $statusLocal = $this->statusLocal($status, $webhookType);

        $invoice->forceFill([
            'status' => $statusLocal,
            'updated_at_external' => $this->dataExternaAtualizacao($data, $webhookType),
        ])->save();

        $log->update([
            'invoice_id' => $invoice->id,
            'status' => $statusLocal,
            'processed' => true,
            'message' => 'Status da invoice atualizado.',
        ]);

        Log::info('Webhook Lytex processado', [
            'webhook_type' => $webhookType,
            'invoice_id' => $invoice->id,
            'status' => $statusLocal,
        ]);

        return response()->json(['message' => 'Webhook processado.']);
    }

    private function configuracaoValida(array $data, string $signature): ?ConfiguracaoIntegracao
    {
        $configs = ConfiguracaoIntegracao::query()
            ->where('integracao', 'lytex')
            ->orderByDesc('ativo')
            ->get();

        foreach ($configs as $configuracao) {
            $secret = (string) $configuracao->callback_secret;

            if ($secret === '') {
                continue;
            }

            if (hash_equals($signature, $this->assinatura($data, $secret))) {
                return $configuracao;
            }
        }

        return null;
    }

    private function assinatura(array $data, string $secret): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return base64_encode(hash_hmac('sha256', (string) $json, $secret, true));
    }

    private function localizarInvoice(string $invoiceExternalId, string $referenceId): ?Invoice
    {
        if ($invoiceExternalId === '' && $referenceId === '') {
            return null;
        }

        return Invoice::query()
            ->where(function ($query) use ($invoiceExternalId, $referenceId): void {
                if ($invoiceExternalId !== '') {
                    $query->orWhere('fatura_id', $invoiceExternalId);
                }

                if ($referenceId !== '') {
                    $query->orWhere('hash_id', $referenceId);
                }
            })
            ->latest('id')
            ->first();
    }

    private function statusLocal(string $status, string $webhookType): string
    {
        $normalizado = str($status)->lower()->ascii()->replace(['-', '_', ' '], '')->toString();

        return match ($normalizado) {
            'paid', 'pago' => 'Pago',
            'processing', 'processando' => 'Processando',
            'canceled', 'cancelled', 'cancelado' => 'Cancelado',
            'waitingpayment', 'pending', 'aguardandopagamento' => 'Aguardando Pagamento',
            default => match ($webhookType) {
                'liquidateInvoice' => 'Pago',
                'scheduleInvoicePayment' => 'Processando',
                'cancelInvoice' => 'Cancelado',
                default => $status,
            },
        };
    }

    private function dataExternaAtualizacao(array $data, string $webhookType): ?string
    {
        return match ($webhookType) {
            'liquidateInvoice' => data_get($data, 'payedAt'),
            'scheduleInvoicePayment' => data_get($data, 'scheduledAt'),
            'cancelInvoice' => data_get($data, 'canceledAt'),
            default => null,
        };
    }
}