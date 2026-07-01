<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoIntegracao;
use App\Models\Contrato;
use App\Models\StatusContrato;
use App\Models\ZapSignWebhookLog;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZapSignWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $eventType = $this->eventType($payload);
        $docToken = $this->docToken($payload);
        $status = $this->status($payload, $eventType);

        $log = ZapSignWebhookLog::query()->create([
            'event_type' => $eventType ?: null,
            'doc_token' => $docToken ?: null,
            'status' => $status ?: null,
            'payload' => $payload,
            'is_valid' => false,
            'processed' => false,
            'message' => 'Recebido.',
        ]);

        if ($payload === []) {
            $log->update(['message' => 'Payload invalido ou vazio.']);

            return response()->json(['message' => 'Payload invalido.'], 422);
        }

        $configuracao = $this->configuracaoValida($request);

        if ($configuracao === null) {
            $log->update(['message' => 'Webhook Secret da ZapSign invalido ou nao configurado.']);

            return response()->json(['message' => 'Nao autorizado.'], 401);
        }

        $log->update([
            'configuracao_integracao_id' => $configuracao->id,
            'is_valid' => true,
        ]);

        $statusLocal = $this->statusContratoLocal($eventType);

        if ($statusLocal === null) {
            $log->update([
                'processed' => true,
                'message' => 'Evento ignorado: event_type sem mapeamento no Conectta.',
            ]);

            return response()->json(['message' => 'Evento ignorado.']);
        }

        if ($docToken === '') {
            $log->update(['message' => 'Token do documento nao encontrado no payload.']);

            return response()->json(['message' => 'Token do documento nao encontrado.'], 202);
        }

        $contrato = Contrato::query()
            ->where('doc_token', $docToken)
            ->latest('id')
            ->first();

        if (! $contrato) {
            $log->update(['message' => 'Contrato nao encontrado para o token informado.']);

            return response()->json(['message' => 'Contrato nao encontrado.'], 202);
        }

        $statusAnterior = $contrato->status_contrato_id;

        $contrato->forceFill([
            'status_contrato_id' => $statusLocal,
        ])->save();

        $log->update([
            'contrato_id' => $contrato->id,
            'processed' => true,
            'message' => 'Status do contrato atualizado pelo event_type.',
        ]);

        Log::info('Webhook ZapSign processado', [
            'event_type' => $eventType,
            'contrato_id' => $contrato->id,
            'status' => $status,
        ]);

        AuditLogger::registrar(
            'contrato.status_webhook',
            'Status do contrato atualizado pelo webhook da ZapSign.',
            $contrato,
            antes: ['status_contrato_id' => $statusAnterior],
            depois: ['status_contrato_id' => $statusLocal],
            contexto: [
                'event_type' => $eventType,
                'status_recebido' => $status,
                'webhook_log_id' => $log->id,
            ],
        );

        return response()->json(['message' => 'Webhook processado.']);
    }

    private function configuracaoValida(Request $request): ?ConfiguracaoIntegracao
    {
        $secretRecebido = trim((string) $request->header('X-Conectta-Webhook-Token'));
        $authorization = trim((string) $request->header('Authorization'));

        if ($secretRecebido === '' && str_starts_with(strtolower($authorization), 'bearer ')) {
            $secretRecebido = trim(substr($authorization, 7));
        }

        if ($secretRecebido === '') {
            $secretRecebido = trim((string) ($request->query('token') ?: $request->query('secret') ?: $request->query('webhook_secret')));
        }

        if ($secretRecebido === '') {
            $secretRecebido = trim((string) data_get($request->json()->all(), 'webhook_secret', ''));
        }

        if ($secretRecebido === '') {
            return null;
        }

        $configs = ConfiguracaoIntegracao::query()
            ->where('integracao', 'zapsign')
            ->orderByDesc('ativo')
            ->get();

        foreach ($configs as $configuracao) {
            $secret = (string) $configuracao->callback_secret;

            if ($secret !== '' && hash_equals($secret, $secretRecebido)) {
                return $configuracao;
            }
        }

        return null;
    }

    private function eventType(array $payload): string
    {
        foreach (['event_type', 'eventType', 'type', 'event', 'webhook_type', 'webhookType'] as $key) {
            $value = data_get($payload, $key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private function docToken(array $payload): string
    {
        foreach (['token', 'doc_token', 'document_token', 'data.token', 'data.doc_token', 'data.document.token', 'document.token'] as $key) {
            $value = data_get($payload, $key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private function status(array $payload, string $eventType): string
    {
        foreach (['status', 'data.status', 'document.status', 'data.document.status'] as $key) {
            $value = data_get($payload, $key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return $eventType;
    }

    private function statusContratoLocal(string $eventType): ?int
    {
        $label = match ($eventType) {
            'doc_signed' => 'Assinado',
            'doc_refused' => 'Rejeitado',
            'doc_expired' => 'Expirado',
            'doc_deleted' => 'Deletado',
            default => null,
        };

        if ($label === null) {
            return null;
        }

        return StatusContrato::query()->where('label', $label)->value('id');
    }
}
