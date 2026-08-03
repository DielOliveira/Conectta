<?php

use App\Http\Controllers\LytexWebhookController;
use App\Models\ConfiguracaoIntegracao;
use App\Models\Invoice;
use App\Models\OrdemServico;
use App\Services\Cobranca\CobrancaAgendamentoService;
use App\Services\Cobranca\CobrancaAutomaticaService;
use App\Services\Cobranca\CobrancaWhatsappService;
use App\Services\OrdemServico\OrdemServicoNotificacaoService;
use App\Services\Tracksolid\TracksolidDiagnosticService;
use App\Services\Tracksolid\TracksolidException;
use App\Services\Tracksolid\TracksolidService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('cobrancas:processar {--data= : Data de processamento no formato YYYY-MM-DD} {--tipo= : boleto_7_dias, lembrete_vencimento ou atraso_X} {--limit= : Limite de itens para processar} {--cliente= : ID do cliente para processar isoladamente} {--executar : Executa de verdade. Sem esta flag roda em simulacao.}', function (CobrancaAutomaticaService $service) {
    $dataOption = trim((string) $this->option('data'));
    $data = $dataOption === '' ? CarbonImmutable::today() : CarbonImmutable::parse($dataOption);
    $tipo = trim((string) $this->option('tipo')) ?: null;
    $limitOption = $this->option('limit');
    $limit = is_numeric($limitOption) ? max(1, (int) $limitOption) : null;
    $clienteOption = $this->option('cliente');
    $clienteId = is_numeric($clienteOption) ? max(1, (int) $clienteOption) : null;
    $dryRun = ! (bool) $this->option('executar');

    $resultado = $service->processar($data, $dryRun, $tipo, $limit, $clienteId);

    $this->info($dryRun ? 'Simulacao concluida.' : 'Processamento concluido.');
    $this->line('Execucoes: '.implode(', ', $resultado['execucao_ids']));
    $this->line('Processados: '.$resultado['total_processados']);
    $this->line('Enviados/pendentes: '.$resultado['total_enviados']);
    $this->line('Ignorados: '.$resultado['total_ignorados']);
    $this->line('Erros: '.$resultado['total_erros']);

    return self::SUCCESS;
})->purpose('Processa as rotinas automaticas de cobranca.');

Artisan::command('cobrancas:enviar-whatsapp {--limit= : Limite de envios pendentes} {--envio= : ID especifico do envio} {--cliente= : ID do cliente para enviar isoladamente} {--execucao= : ID da execucao de cobranca} {--executar : Executa de verdade. Sem esta flag roda em simulacao.}', function (CobrancaWhatsappService $service) {
    $limitOption = $this->option('limit');
    $limit = is_numeric($limitOption) ? max(1, (int) $limitOption) : null;
    $envioOption = $this->option('envio');
    $envioId = is_numeric($envioOption) ? max(1, (int) $envioOption) : null;
    $clienteOption = $this->option('cliente');
    $clienteId = is_numeric($clienteOption) ? max(1, (int) $clienteOption) : null;
    $execucaoOption = $this->option('execucao');
    $execucaoId = is_numeric($execucaoOption) ? max(1, (int) $execucaoOption) : null;
    $dryRun = ! (bool) $this->option('executar');

    $resultado = $service->enviarPendentes($limit, $envioId, $clienteId, $dryRun, $execucaoId);

    $this->info($dryRun ? 'Simulacao de WhatsApp concluida.' : 'Envio de WhatsApp concluido.');
    $this->line('Processados: '.$resultado['processados']);
    $this->line('Enviados: '.$resultado['enviados']);
    $this->line('Simulados: '.$resultado['simulados']);
    $this->line('Erros: '.$resultado['erros']);

    return self::SUCCESS;
})->purpose('Envia pelo WhatsApp as cobrancas pendentes.');

Artisan::command('cobrancas:rodar-agendadas', function (CobrancaAgendamentoService $service) {
    $resultado = $service->processarVencidos();

    $this->info('Agendamentos verificados.');
    $this->line('Agendamentos executados: '.$resultado['agendamentos']);
    $this->line('Execucoes geradas: '.$resultado['execucoes']);
    $this->line('Erros: '.$resultado['erros']);

    return $resultado['erros'] > 0 ? self::FAILURE : self::SUCCESS;
})->purpose('Executa os agendamentos de cobranca vencidos.');

Schedule::command('cobrancas:rodar-agendadas')
    ->everyMinute()
    ->withoutOverlapping();

Artisan::command('ordens-servico:enviar-notificacoes {--limit=50}', function (OrdemServicoNotificacaoService $service) {
    $resultado = $service->processarPendentes(max(1, (int) $this->option('limit')));
    $this->info("Enviadas: {$resultado['enviadas']}; erros: {$resultado['erros']}.");

    return $resultado['erros'] ? self::FAILURE : self::SUCCESS;
})->purpose('Envia notificações pendentes das ordens de serviço.');

Schedule::call(function (): void {
    OrdemServico::query()->with('tecnico')->where('status', 'aceita')
        ->whereBetween('agendado_em', [now()->addMinutes(115), now()->addMinutes(125)])
        ->whereDoesntHave('notificacoes', fn ($q) => $q->where('evento', 'lembrete_2h'))
        ->each(fn (OrdemServico $ordem) => app(OrdemServicoNotificacaoService::class)->registrarTecnico($ordem, 'lembrete_2h', "Lembrete: {$ordem->numero_formatado} está agendada para {$ordem->agendado_em->format('d/m/Y H:i')}."));
})->name('ordens-servico:lembretes')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('ordens-servico:enviar-notificacoes')->everyMinute()->withoutOverlapping();

if (! app()->isProduction()) {
    Artisan::command('tracksolid:diagnostico {--base-url= : URL HTTPS do no Tracksolid} {--imei= : IMEI conhecido para validar detalhes} {--output= : Caminho relativo no storage privado}', function (TracksolidDiagnosticService $diagnostic) {
        $baseUrl = trim((string) $this->option('base-url')) ?: null;
        $output = trim((string) $this->option('output'))
            ?: 'tracksolid/diagnostico-'.now()->format('Ymd-His').'.json';

        try {
            $service = new TracksolidService($baseUrl);
            $token = $service->token();
            $accessToken = (string) $token['accessToken'];
            $this->info('Autenticacao Tracksolid concluida.');

            $imei = preg_replace('/\D+/', '', (string) $this->option('imei')) ?? '';
            $detail = $imei === '' ? null : $service->deviceDetail($accessToken, $imei);

            if ($detail !== null) {
                $this->info("Consulta do IMEI {$imei} concluida.");
            }

            $devices = $service->devices($accessToken);
            $report = $diagnostic->build($devices);
            $report['connection'] = [
                'base_url' => $baseUrl ?: config('services.tracksolid.base_url'),
                'account' => (string) config('services.tracksolid.account'),
                'token_expires_in' => $token['expiresIn'] ?? null,
                'sample_imei' => $imei ?: null,
                'sample_detail' => $detail,
            ];

            Storage::disk('local')->put(
                $output,
                json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            );

            $this->table(
                ['Metrica', 'Total'],
                collect($report['summary'])->map(fn (mixed $value, string $key): array => [$key, $value])->values()->all(),
            );
            $this->info('Relatorio privado salvo em: '.Storage::disk('local')->path($output));

            return self::SUCCESS;
        } catch (TracksolidException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    })->purpose('Concilia o inventario Tracksolid com os rastreadores locais, sem alterar dados.');

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
            'referenceId' => 'conectta-teste-'.now()->format('YmdHis'),
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

        $this->info('HTTP '.$response->getStatusCode());
        $this->line((string) $response->getContent());

        return $response->getStatusCode() < 400 ? self::SUCCESS : self::FAILURE;
    })->purpose('Testa o webhook Lytex sem montar JSON no terminal.');
}
