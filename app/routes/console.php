<?php

use App\Http\Controllers\LytexWebhookController;
use App\Models\ConfiguracaoIntegracao;
use App\Models\Invoice;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('conectta:lytex-webhook-test {invoiceId? : fatura_id externa da Lytex} {--event=liquidateInvoice : liquidateInvoice, scheduleInvoicePayment ou cancelInvoice} {--status= : Status externo opcional} {--show-payload : Exibe o payload assinado antes de processar}', function () {
    $event = (string) $this->option('event');

    if (! in_array($event, ['liquidateInvoice', 'scheduleInvoicePayment', 'cancelInvoice'], true)) {
        $this->error('Evento invalido. Use liquidateInvoice, scheduleInvoicePayment ou cancelInvoice.');

        return self::FAILURE;
    }

    $configuracao = ConfiguracaoIntegracao::lytexAtiva();
    $secret = (string) $configuracao->callback_secret;

    if ($secret === '') {
        $this->error('Callback Secret da Lytex nao esta configurado para o ambiente ativo.');

        return self::FAILURE;
    }

    $invoiceExternalId = (string) ($this->argument('invoiceId') ?: Invoice::query()
        ->whereNotNull('fatura_id')
        ->where('fatura_id', '<>', '')
        ->latest('id')
        ->value('fatura_id'));

    if ($invoiceExternalId === '') {
        $this->error('Nenhuma Invoice com fatura_id foi encontrada. Informe o invoiceId manualmente.');

        return self::FAILURE;
    }

    $status = (string) ($this->option('status') ?: match ($event) {
        'liquidateInvoice' => 'paid',
        'scheduleInvoicePayment' => 'processing',
        'cancelInvoice' => 'canceled',
    });

    $data = [
        'invoiceId' => $invoiceExternalId,
        'status' => $status,
        'invoiceValue' => 1000,
        'referenceId' => 'conectta-teste-' . now()->format('YmdHis'),
        'dueDate' => now()->toDateString(),
    ];

    if ($event === 'liquidateInvoice') {
        $data['payedAt'] = now()->toJSON();
        $data['payedValue'] = 1000;
        $data['paymentMethod'] = 'pix';
    }

    if ($event === 'scheduleInvoicePayment') {
        $data['scheduledAt'] = now()->toJSON();
        $data['scheduleDate'] = now()->addDay()->toJSON();
        $data['payedValue'] = 1000;
        $data['paymentMethod'] = 'boleto';
    }

    if ($event === 'cancelInvoice') {
        $data['canceledAt'] = now()->toJSON();
    }

    $signature = base64_encode(hash_hmac(
        'sha256',
        (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $secret,
        true,
    ));

    $payload = [
        'webhookType' => $event,
        'signature' => $signature,
        'data' => $data,
    ];

    if ($this->option('show-payload')) {
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    $request = Request::create(
        '/api/webhooks/lytex',
        'POST',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    );

    $response = app(LytexWebhookController::class)($request);

    $this->info('HTTP ' . $response->getStatusCode());
    $this->line((string) $response->getContent());

    return $response->getStatusCode() < 400 ? self::SUCCESS : self::FAILURE;
})->purpose('Testa o webhook Lytex sem montar JSON no terminal.');