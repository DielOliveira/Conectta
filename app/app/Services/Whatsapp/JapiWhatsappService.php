<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoIntegracao;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class JapiWhatsappService implements WhatsappService
{
    public function enviarTexto(string $telefone, string $mensagem): array
    {
        return $this->post('send-text', ['phone' => $telefone, 'message' => $mensagem]);
    }

    public function enviarDocumentoPdf(string $telefone, string $documento, string $nomeArquivo): array
    {
        $origem = str_starts_with(strtolower($documento), 'https://')
            ? ['url' => $documento]
            : ['path' => $documento];

        return $this->post('send-file', [
            'phone' => $telefone,
            ...$origem,
            'filename' => $nomeArquivo,
        ]);
    }

    public function enviarPix(string $telefone, string $pixCopiaCola): array
    {
        return $this->post('send-pix', [
            'phone' => $telefone,
            'message' => 'Pague usando o PIX:',
            'pix' => $pixCopiaCola,
            'merchantName' => 'Conectta',
            'keyType' => 'EVP',
        ]);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function post(string $endpoint, array $payload): array
    {
        $configuracao = ConfiguracaoIntegracao::japiAtiva();
        $baseUrl = rtrim((string) $configuracao->base_url, '/');
        $sessao = trim((string) ($configuracao->client_id ?: 'default'));

        if ($baseUrl === '' || $sessao === '') {
            throw new WhatsappException('Configuracao do J-API incompleta.');
        }

        $prefixo = $sessao === 'default' ? '' : '/sessions/'.$sessao;

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->timeout((int) ($configuracao->timeout ?: 60))
                ->post($prefixo.'/'.ltrim($endpoint, '/'), $payload);
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

        if (! is_array($data) || ($data['success'] ?? false) !== true) {
            throw new WhatsappException('O J-API retornou uma resposta invalida.');
        }

        return $data;
    }
}
