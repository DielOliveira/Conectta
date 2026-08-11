<?php

namespace App\Services\Whatsapp;

use App\Models\CobrancaEnvio;
use App\Models\OrdemServicoNotificacao;
use App\Models\WhatsappJob;
use Illuminate\Database\Eloquent\Model;

class WhatsappJobService
{
    public function __construct(private readonly JapiWhatsappService $japi) {}

    /** @param array<string, mixed> $response */
    public function registrar(Model $origem, string $etapa, string $idempotencyKey, array $response): WhatsappJob
    {
        $jobId = is_string($response['jobId'] ?? null) ? $response['jobId'] : null;
        $driver = $jobId === null ? 'zapi' : 'japi';
        $status = $driver === 'japi' ? (string) ($response['status'] ?? 'pending') : 'sent';

        return WhatsappJob::query()->updateOrCreate(['idempotency_key' => $idempotencyKey], [
            'origem_type' => $origem::class, 'origem_id' => $origem->getKey(), 'etapa' => $etapa,
            'driver' => $driver, 'sessao' => $response['session'] ?? null, 'job_id' => $jobId,
            'status' => $status, 'whatsapp_message_id' => $response['whatsappMessageId'] ?? $response['messageId'] ?? null,
            'resposta' => $response, 'enfileirado_em' => $driver === 'japi' ? now() : null,
            'enviado_em' => $status === 'sent' ? now() : null,
        ]);
    }

    /** @return array{processados:int,enviados:int,falhos:int,pendentes:int,erros:int} */
    public function reconciliar(int $limite = 100): array
    {
        $resultado = ['processados' => 0, 'enviados' => 0, 'falhos' => 0, 'pendentes' => 0, 'erros' => 0];

        WhatsappJob::query()->where('driver', 'japi')->whereIn('status', ['pending', 'processing'])
            ->oldest('id')->limit($limite)->get()->each(function (WhatsappJob $registro) use (&$resultado): void {
                $resultado['processados']++;
                try {
                    $response = $this->japi->consultarJob((string) $registro->job_id);
                    $job = is_array($response['job'] ?? null) ? $response['job'] : [];
                    $status = (string) ($job['status'] ?? '');
                    if (! in_array($status, ['pending', 'processing', 'sent', 'failed'], true)) {
                        throw new WhatsappException('O J-API retornou um status de job invalido.');
                    }
                    $registro->update([
                        'status' => $status, 'tentativas' => (int) ($job['attempts'] ?? $registro->tentativas),
                        'whatsapp_message_id' => $job['whatsappMessageId'] ?? null,
                        'ultimo_erro' => $job['lastError'] ?? null, 'resposta' => $response,
                        'enviado_em' => $status === 'sent' ? now() : null,
                        'falhou_em' => $status === 'failed' ? now() : null,
                    ]);
                    $resultado[match ($status) {
                        'sent' => 'enviados', 'failed' => 'falhos', default => 'pendentes'
                    }]++;
                    $this->atualizarOrigem($registro->fresh());
                } catch (WhatsappException $e) {
                    $registro->update(['ultimo_erro' => $e->getMessage()]);
                    $resultado['erros']++;
                }
            });

        return $resultado;
    }

    private function atualizarOrigem(WhatsappJob $registro): void
    {
        $origem = $registro->origem;
        if (! $origem) {
            return;
        }

        if (! $origem instanceof CobrancaEnvio && ! $origem instanceof OrdemServicoNotificacao) {
            return;
        }

        $query = WhatsappJob::query()->where('origem_type', $registro->origem_type)->where('origem_id', $registro->origem_id);
        $statuses = (clone $query)->pluck('status');
        if ($statuses->contains('failed')) {
            $erro = (clone $query)->where('status', 'failed')->value('ultimo_erro');
            $origem->update(['status' => 'erro', 'erro' => $erro ?: 'O J-API nao conseguiu concluir o envio.']);

            return;
        }
        if ($statuses->isEmpty() || $statuses->contains(fn (string $status): bool => $status !== 'sent')) {
            return;
        }

        if ($origem instanceof CobrancaEnvio) {
            $origem->update(['status' => 'enviado', 'enviado_em' => now(), 'erro' => null]);
        } elseif ($origem instanceof OrdemServicoNotificacao) {
            $origem->update(['status' => 'enviada', 'enviada_em' => now(), 'erro' => null]);
        }
    }
}
