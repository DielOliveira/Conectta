<?php

namespace Tests\Feature;

use App\Models\ConfiguracaoIntegracao;
use App\Services\Whatsapp\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_japi_driver_sends_text_pdf_and_pix_to_local_service(): void
    {
        Http::fake([
            'http://127.0.0.1:3001/*' => Http::response([
                'success' => true,
                'session' => 'default',
                'messageId' => 'message-123',
            ]),
        ]);

        ConfiguracaoIntegracao::query()->create([
            'integracao' => 'whatsapp',
            'ambiente' => 'global',
            'driver' => 'japi',
            'ativo' => true,
        ]);
        ConfiguracaoIntegracao::query()->create([
            'integracao' => 'japi',
            'ambiente' => 'producao',
            'base_url' => 'http://127.0.0.1:3001',
            'client_id' => 'default',
            'timeout' => 60,
            'ativo' => true,
        ]);

        $whatsapp = app(WhatsappService::class);
        $whatsapp->enviarTexto('5562999999999', 'Mensagem');
        $whatsapp->enviarDocumentoPdf('5562999999999', 'https://public-api-pay.lytex.com.br/boleto', 'boleto.pdf');
        $whatsapp->enviarPix('5562999999999', '000201010212');

        Http::assertSentCount(3);
        Http::assertSent(fn ($request): bool => $request->url() === 'http://127.0.0.1:3001/send-text'
            && $request['message'] === 'Mensagem');
        Http::assertSent(fn ($request): bool => $request->url() === 'http://127.0.0.1:3001/send-file'
            && $request['url'] === 'https://public-api-pay.lytex.com.br/boleto'
            && $request['filename'] === 'boleto.pdf');
        Http::assertSent(fn ($request): bool => $request->url() === 'http://127.0.0.1:3001/send-pix'
            && $request['pix'] === '000201010212');
    }

    public function test_zapi_remains_the_default_driver_for_rollback(): void
    {
        Http::fake([
            'https://api.z-api.io/*' => Http::response(['messageId' => 'message-123']),
        ]);

        ConfiguracaoIntegracao::query()->create([
            'integracao' => 'zapi',
            'ambiente' => 'producao',
            'base_url' => 'https://api.z-api.io',
            'client_id' => 'instance',
            'token' => 'token',
            'client_secret' => 'client-token',
            'timeout' => 30,
            'pix_endpoint' => 'send-button-pix',
            'ativo' => true,
        ]);

        app(WhatsappService::class)->enviarTexto('5562999999999', 'Rollback');

        $this->assertSame('zapi', ConfiguracaoIntegracao::whatsappDriver());
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/instances/instance/token/token/send-text'));
    }
}
