<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoIntegracao;

class WhatsappManager implements WhatsappService
{
    public function __construct(
        private readonly ZapiWhatsappService $zapi,
        private readonly JapiWhatsappService $japi,
    ) {}

    public function enviarTexto(string $telefone, string $mensagem): array
    {
        return $this->driver()->enviarTexto($telefone, $mensagem);
    }

    public function enviarDocumentoPdf(string $telefone, string $documento, string $nomeArquivo): array
    {
        return $this->driver()->enviarDocumentoPdf($telefone, $documento, $nomeArquivo);
    }

    public function enviarPix(string $telefone, string $pixCopiaCola): array
    {
        return $this->driver()->enviarPix($telefone, $pixCopiaCola);
    }

    private function driver(): WhatsappService
    {
        return ConfiguracaoIntegracao::whatsappDriver() === 'japi' ? $this->japi : $this->zapi;
    }
}
