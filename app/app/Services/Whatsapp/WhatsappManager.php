<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoIntegracao;

class WhatsappManager implements WhatsappService
{
    public function __construct(
        private readonly ZapiWhatsappService $zapi,
        private readonly JapiWhatsappService $japi,
    ) {}

    public function enviarTexto(string $telefone, string $mensagem, ?string $idempotencyKey = null): array
    {
        return $this->driver()->enviarTexto($telefone, $mensagem, $idempotencyKey);
    }

    public function enviarDocumentoPdf(string $telefone, string $documento, string $nomeArquivo, ?string $idempotencyKey = null): array
    {
        return $this->driver()->enviarDocumentoPdf($telefone, $documento, $nomeArquivo, $idempotencyKey);
    }

    public function enviarPix(string $telefone, string $pixCopiaCola, ?string $idempotencyKey = null): array
    {
        return $this->driver()->enviarPix($telefone, $pixCopiaCola, $idempotencyKey);
    }

    private function driver(): WhatsappService
    {
        return ConfiguracaoIntegracao::whatsappDriver() === 'japi' ? $this->japi : $this->zapi;
    }
}
