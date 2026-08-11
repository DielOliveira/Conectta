<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoIntegracao;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class JapiWhatsappService implements WhatsappService
{
    public function enviarTexto(string $telefone, string $mensagem, ?string $idempotencyKey = null): array
    {
        return $this->post('send-text', ['phone' => $telefone, 'message' => $mensagem], $idempotencyKey);
    }

    public function enviarDocumentoPdf(string $telefone, string $documento, string $nomeArquivo, ?string $idempotencyKey = null): array
    {
        $origem = str_starts_with(strtolower($documento), 'https://')
            ? ['url' => $documento]
            : ['path' => $documento];

        return $this->post('send-file', [
            'phone' => $telefone,
            ...$origem,
            'filename' => $nomeArquivo,
        ], $idempotencyKey);
    }

    public function enviarPix(string $telefone, string $pixCopiaCola, ?string $idempotencyKey = null): array
    {
        return $this->post('send-pix', [
            'phone' => $telefone,
            'message' => 'Pague usando o PIX:',
            'pix' => $pixCopiaCola,
            'merchantName' => 'Conectta',
            'keyType' => 'EVP',
        ], $idempotencyKey);
    }

    /** @return array<string, mixed> */
    public function consultarJob(string $jobId): array
    {
        return $this->request('get', 'queue/'.rawurlencode($jobId));
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function post(string $endpoint, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->request('post', $endpoint, $payload, $idempotencyKey);

        if (($data['success'] ?? false) !== true || ! is_string($data['jobId'] ?? null) || trim($data['jobId']) === '') {
            throw new WhatsappException('O J-API aceitou o envio sem informar o jobId.');
        }

        return $data;
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function request(string $method, string $endpoint, array $payload = [], ?string $idempotencyKey = null): array
    {
        $configuracao = ConfiguracaoIntegracao::japiAtiva();
        $baseUrl = rtrim((string) $configuracao->base_url, '/');
        $sessao = trim((string) ($configuracao->client_id ?: 'default'));

        if ($baseUrl === '' || $sessao === '') {
            throw new WhatsappException('Configuracao do J-API incompleta.');
        }

        $prefixo = $sessao === 'default' ? '' : '/sessions/'.$sessao;

        try {
            $request = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->timeout((int) ($configuracao->timeout ?: 60));

            if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
                $request = $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
            }

            $response = $request->{$method}($prefixo.'/'.ltrim($endpoint, '/'), $payload);
        } catch (ConnectionException) {
            throw new WhatsappException('Nao foi possivel conectar com o J-API.');
        }

        $data = $response->json();

        if ($response->failed()) {
            $mensagem = is_array($data) ? ($data['error'] ?? null) : null;
            throw new WhatsappException(filled($mensagem)
                ? 'J-API recusou o envio: '.trim((string) $mensagem)
                : 'J-API retornou erro HTTP '.$response->status().'.');
        }

        if (! is_array($data)) {
            throw new WhatsappException('O J-API retornou uma resposta invalida.');
        }

        return $data;
    }
}
