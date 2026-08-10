<?php

namespace App\Services\Whatsapp;

interface WhatsappService
{
    /** @return array<string, mixed> */
    public function enviarTexto(string $telefone, string $mensagem): array;

    /** @return array<string, mixed> */
    public function enviarDocumentoPdf(string $telefone, string $documento, string $nomeArquivo): array;

    /** @return array<string, mixed> */
    public function enviarPix(string $telefone, string $pixCopiaCola): array;
}
