<?php

namespace App\Services\Whatsapp;

use App\Enums\WhatsappCanal;

interface WhatsappService
{
    /** @return array<string, mixed> */
    public function enviarTexto(string $telefone, string $mensagem, ?string $idempotencyKey = null, ?WhatsappCanal $canal = null): array;

    /** @return array<string, mixed> */
    public function enviarDocumentoPdf(string $telefone, string $documento, string $nomeArquivo, ?string $idempotencyKey = null, ?WhatsappCanal $canal = null): array;

    /** @return array<string, mixed> */
    public function enviarPix(string $telefone, string $pixCopiaCola, ?string $idempotencyKey = null, ?WhatsappCanal $canal = null): array;
}
