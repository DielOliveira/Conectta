<?php

namespace App\Services\Whatsapp;

interface WhatsappService
{
    /** @return array<string, mixed> */
    public function enviarTexto(string $telefone, string $mensagem, ?string $idempotencyKey = null): array;

    /** @return array<string, mixed> */
    public function enviarDocumentoPdf(string $telefone, string $documento, string $nomeArquivo, ?string $idempotencyKey = null): array;

    /** @return array<string, mixed> */
    public function enviarPix(string $telefone, string $pixCopiaCola, ?string $idempotencyKey = null): array;
}
