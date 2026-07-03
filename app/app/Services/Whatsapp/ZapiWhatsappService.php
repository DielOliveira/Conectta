<?php

namespace App\Services\Whatsapp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ZapiWhatsappService
{
    /**
     * @return array<string, mixed>
     */
    public function enviarTexto(string $telefone, string $mensagem): array
    {
        return $this->post('send-text', [
            'phone' => $telefone,
            'message' => $mensagem,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function enviarDocumentoPdf(string $telefone, string $documentoUrl, string $nomeArquivo): array
    {
        return $this->post('send-document/PDF', [
            'phone' => $telefone,
            'document' => $documentoUrl,
            'fileName' => $nomeArquivo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function enviarPix(string $telefone, string $pixCopiaCola): array
    {
        return $this->post((string) config('services.whatsapp.zapi.pix_endpoint', 'send-button-pix'), [
            'phone' => $telefone,
            'pixKey' => $pixCopiaCola,
            'type' => 'EVP',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $payload): array
    {
        $baseUrl = rtrim((string) config('services.whatsapp.zapi.base_url'), '/');
        $instanceId = trim((string) config('services.whatsapp.zapi.instance_id'));
        $token = trim((string) config('services.whatsapp.zapi.token'));
        $clientToken = trim((string) config('services.whatsapp.zapi.client_token'));
        $timeout = (int) config('services.whatsapp.zapi.timeout', 30);

        if ($baseUrl === '' || $instanceId === '' || $token === '' || $clientToken === '') {
            throw new WhatsappException('Configuracao da Z-API incompleta.');
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->withHeaders(['Client-Token' => $clientToken])
                ->post('/instances/'.$instanceId.'/token/'.$token.'/'.ltrim($endpoint, '/'), $payload);
        } catch (ConnectionException) {
            throw new WhatsappException('Nao foi possivel conectar com a Z-API.');
        }

        $data = $response->json();

        if ($response->failed()) {
            throw new WhatsappException($this->mensagemErro($response->status(), $data));
        }

        if (! is_array($data)) {
            throw new WhatsappException('A Z-API retornou uma resposta invalida.');
        }

        if (filled($data['error'] ?? null)) {
            throw new WhatsappException('Z-API retornou erro: '.(string) ($data['message'] ?? $data['error']));
        }

        return $data;
    }

    private function mensagemErro(int $status, mixed $body): string
    {
        $message = is_array($body) ? ($body['message'] ?? $body['error'] ?? null) : null;

        if (is_string($message) && trim($message) !== '') {
            return 'Z-API recusou o envio: '.trim($message);
        }

        return 'Z-API retornou erro HTTP '.$status.'.';
    }
}
