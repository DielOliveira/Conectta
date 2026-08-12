<?php

namespace Tests\Feature;

use App\Enums\WhatsappCanal;
use App\Models\ConfiguracaoIntegracao;
use App\Models\WhatsappJob;
use App\Services\Whatsapp\WhatsappException;
use App\Services\Whatsapp\WhatsappJobService;
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
                'queued' => true,
                'duplicate' => false,
                'jobId' => 'job-123',
                'status' => 'pending',
            ], 202),
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

    public function test_japi_accepts_idempotent_200_response_and_sends_header(): void
    {
        Http::fake(['http://127.0.0.1:3001/*' => Http::response([
            'success' => true, 'session' => 'default', 'queued' => true, 'duplicate' => true,
            'jobId' => 'existing-job', 'status' => 'pending',
        ], 200)]);
        $this->configureJapi();

        $response = app(WhatsappService::class)->enviarTexto('5562999999999', 'Mensagem', 'cobranca-10-texto');

        $this->assertTrue($response['duplicate']);
        Http::assertSent(fn ($request): bool => $request->hasHeader('Idempotency-Key', 'cobranca-10-texto'));
    }

    public function test_japi_routes_each_business_channel_to_its_own_session(): void
    {
        Http::fake(fn ($request) => Http::response([
            'success' => true,
            'session' => explode('/', $request->url())[4] ?? 'default',
            'jobId' => 'job-routed',
            'status' => 'pending',
        ], 202));
        $this->configureJapi([
            'japi_sessao_cobrancas' => 'cobrancas',
            'japi_sessao_os_campo' => 'os_campo',
            'japi_sessao_os_manutencao' => 'os_manutencao',
        ]);

        $whatsapp = app(WhatsappService::class);
        $whatsapp->enviarTexto('5562999999999', 'Cobrança', 'key-cobranca', WhatsappCanal::COBRANCAS);
        $whatsapp->enviarTexto('5562999999999', 'Instalação', 'key-campo', WhatsappCanal::OS_INSTALACAO_RETIRADA);
        $whatsapp->enviarTexto('5562999999999', 'Manutenção', 'key-manutencao', WhatsappCanal::OS_MANUTENCAO);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://127.0.0.1:3001/sessions/cobrancas/send-text');
        Http::assertSent(fn ($request): bool => $request->url() === 'http://127.0.0.1:3001/sessions/os_campo/send-text');
        Http::assertSent(fn ($request): bool => $request->url() === 'http://127.0.0.1:3001/sessions/os_manutencao/send-text');
    }

    public function test_reconciliation_queries_the_session_recorded_on_each_job(): void
    {
        $this->configureJapi();
        $origem = ConfiguracaoIntegracao::query()->where('integracao', 'japi')->firstOrFail();
        WhatsappJob::query()->create([
            'origem_type' => $origem::class, 'origem_id' => $origem->id, 'etapa' => 'texto',
            'driver' => 'japi', 'sessao' => 'os_manutencao', 'idempotency_key' => 'key-session-job',
            'job_id' => 'session-job', 'status' => 'pending', 'enfileirado_em' => now(),
        ]);
        Http::fake(['http://127.0.0.1:3001/sessions/os_manutencao/queue/session-job' => Http::response([
            'session' => 'os_manutencao',
            'job' => ['id' => 'session-job', 'status' => 'sent', 'attempts' => 1, 'whatsappMessageId' => 'wa-session'],
        ])]);

        $resultado = app(WhatsappJobService::class)->reconciliar();

        $this->assertSame(1, $resultado['enviados']);
        Http::assertSent(fn ($request): bool => $request->url() === 'http://127.0.0.1:3001/sessions/os_manutencao/queue/session-job');
    }

    public function test_japi_rejects_success_response_without_job_id(): void
    {
        Http::fake(['http://127.0.0.1:3001/*' => Http::response(['success' => true], 202)]);
        $this->configureJapi();

        $this->expectException(WhatsappException::class);
        $this->expectExceptionMessage('sem informar o jobId');
        app(WhatsappService::class)->enviarTexto('5562999999999', 'Mensagem');
    }

    public function test_japi_reconciliation_maps_sent_and_failed_jobs(): void
    {
        $this->configureJapi();
        $origem = ConfiguracaoIntegracao::query()->where('integracao', 'japi')->firstOrFail();
        foreach (['job-sent', 'job-failed'] as $jobId) {
            WhatsappJob::query()->create([
                'origem_type' => $origem::class, 'origem_id' => $origem->id, 'etapa' => 'texto',
                'driver' => 'japi', 'sessao' => 'default', 'idempotency_key' => 'key-'.$jobId,
                'job_id' => $jobId, 'status' => 'pending', 'enfileirado_em' => now(),
            ]);
        }
        Http::fake(function ($request) {
            $sent = str_ends_with($request->url(), '/job-sent');

            return Http::response(['session' => 'default', 'job' => [
                'id' => $sent ? 'job-sent' : 'job-failed', 'status' => $sent ? 'sent' : 'failed',
                'attempts' => $sent ? 1 : 5, 'whatsappMessageId' => $sent ? 'wa-123' : null,
                'lastError' => $sent ? null : 'tentativas esgotadas',
            ]]);
        });

        $resultado = app(WhatsappJobService::class)->reconciliar();

        $this->assertSame(1, $resultado['enviados']);
        $this->assertSame(1, $resultado['falhos']);
        $this->assertDatabaseHas('whatsapp_jobs', ['job_id' => 'job-sent', 'status' => 'sent', 'whatsapp_message_id' => 'wa-123']);
        $this->assertDatabaseHas('whatsapp_jobs', ['job_id' => 'job-failed', 'status' => 'failed', 'ultimo_erro' => 'tentativas esgotadas']);
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

    private function configureJapi(array $overrides = []): void
    {
        ConfiguracaoIntegracao::query()->create([
            'integracao' => 'whatsapp', 'ambiente' => 'global', 'driver' => 'japi', 'ativo' => true,
        ]);
        ConfiguracaoIntegracao::query()->create([
            'integracao' => 'japi', 'ambiente' => 'producao', 'base_url' => 'http://127.0.0.1:3001',
            'client_id' => 'default', 'timeout' => 60, 'ativo' => true,
            ...$overrides,
        ]);
    }
}
