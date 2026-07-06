<?php

namespace App\Services\Cobranca;

use App\Models\Cliente;
use App\Models\CobrancaEnvio;
use App\Models\CobrancaExecucao;
use App\Models\CobrancaMensagemModelo;
use App\Models\ConfiguracaoIntegracao;
use App\Models\Invoice;
use App\Models\Lancamento;
use App\Models\Pais;
use App\Rules\CpfCnpj;
use App\Services\Lytex\LytexException;
use App\Services\Lytex\LytexInvoiceData;
use App\Services\Lytex\LytexInvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CobrancaAutomaticaService
{
    public const BOLETO_7_DIAS = 'boleto_7_dias';
    public const LEMBRETE_VENCIMENTO = 'lembrete_vencimento';

    /** @var array<int, int> */
    public const DIAS_ATRASO = [2, 5, 7, 10, 12, 15];

    /**
     * @return array{execucao_ids:array<int, int>,total_processados:int,total_enviados:int,total_ignorados:int,total_erros:int,dry_run:bool}
     */
    public function processar(?CarbonImmutable $data = null, bool $dryRun = true, ?string $tipo = null, ?int $limit = null, ?int $clienteId = null, ?int $agendamentoId = null): array
    {
        $data ??= CarbonImmutable::today();

        $totais = [
            'total_processados' => 0,
            'total_enviados' => 0,
            'total_ignorados' => 0,
            'total_erros' => 0,
        ];
        $execucaoIds = [];

        foreach ($this->planosDoDia($data, $tipo) as $plano) {
            $resultadoPlano = $this->processarPlano($data, $dryRun, $plano, $limit, $clienteId, $agendamentoId);
            $execucaoIds[] = $resultadoPlano['execucao_id'];

            foreach (array_keys($totais) as $contador) {
                $totais[$contador] += $resultadoPlano[$contador];
            }
        }

        return [
            'execucao_ids' => $execucaoIds,
            ...$totais,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * @param array{tipo:string,vencimento:CarbonImmutable,dias_atraso:int|null} $plano
     * @return array{execucao_id:int,total_processados:int,total_enviados:int,total_ignorados:int,total_erros:int}
     */
    private function processarPlano(CarbonImmutable $data, bool $dryRun, array $plano, ?int $limit, ?int $clienteId, ?int $agendamentoId): array
    {
        $execucao = CobrancaExecucao::query()->create([
            'cobranca_agendamento_id' => $agendamentoId,
            'data_processamento' => $data->toDateString(),
            'tipo' => $plano['tipo'],
            'status' => 'processando',
            'dry_run' => $dryRun,
            'iniciado_em' => now(),
        ]);

        $contadores = [
            'total_processados' => 0,
            'total_enviados' => 0,
            'total_ignorados' => 0,
            'total_erros' => 0,
        ];

        try {
            foreach ($this->lancamentosParaVencimento($plano['vencimento'], $clienteId) as $lancamento) {
                if ($limit !== null && $contadores['total_processados'] >= $limit) {
                    break;
                }

                $resultado = $this->processarLancamento($execucao, $lancamento, $plano, $data, $dryRun);
                $contadores['total_processados']++;
                $contadores[$resultado]++;
            }

            $execucao->update([
                ...$contadores,
                'status' => 'concluido',
                'finalizado_em' => now(),
            ]);
        } catch (\Throwable $exception) {
            $execucao->update([
                ...$contadores,
                'status' => 'erro',
                'finalizado_em' => now(),
                'mensagem' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return [
            'execucao_id' => $execucao->id,
            ...$contadores,
        ];
    }

    /**
     * @return array<int, array{tipo:string,vencimento:CarbonImmutable,dias_atraso:int|null}>
     */
    private function planosDoDia(CarbonImmutable $data, ?string $tipo): array
    {
        $planos = [
            [
                'tipo' => self::BOLETO_7_DIAS,
                'vencimento' => $data->addDays(7),
                'dias_atraso' => null,
            ],
            [
                'tipo' => self::LEMBRETE_VENCIMENTO,
                'vencimento' => $data,
                'dias_atraso' => null,
            ],
        ];

        foreach (self::DIAS_ATRASO as $dias) {
            $planos[] = [
                'tipo' => 'atraso_'.$dias,
                'vencimento' => $data->subDays($dias),
                'dias_atraso' => $dias,
            ];
        }

        if ($tipo === null || $tipo === '') {
            return $planos;
        }

        return array_values(array_filter($planos, fn (array $plano): bool => $plano['tipo'] === $tipo));
    }

    /**
     * @return Collection<int, Lancamento>
     */
    private function lancamentosParaVencimento(CarbonImmutable $vencimento, ?int $clienteId = null): Collection
    {
        return Lancamento::query()
            ->with(['cliente', 'invoice'])
            ->when($clienteId !== null, fn (Builder $query): Builder => $query->where('cliente_id', $clienteId))
            ->where('mes_referencia', (int) $vencimento->month)
            ->where('ano_referencia', (int) $vencimento->year)
            ->where('valor_planejado', '>', 0)
            ->where(function (Builder $query): void {
                $query->whereNull('valor_efetivado')
                    ->orWhere('valor_efetivado', '<=', 0);
            })
            ->get()
            ->filter(fn (Lancamento $lancamento): bool => $this->vencimentoDoLancamento($lancamento)?->isSameDay($vencimento) ?? false)
            ->values();
    }

    /**
     * @param array{tipo:string,vencimento:CarbonImmutable,dias_atraso:int|null} $plano
     */
    private function processarLancamento(CobrancaExecucao $execucao, Lancamento $lancamento, array $plano, CarbonImmutable $dataReferencia, bool $dryRun): string
    {
        $tipo = $plano['tipo'];
        $cliente = $lancamento->cliente;
        $vencimento = $plano['vencimento'];
        $valor = (float) $lancamento->valor_planejado;

        if ($cliente === null) {
            $this->registrar($execucao, $lancamento, $tipo, 'erro', $dataReferencia, $vencimento, $valor, erro: 'Cliente nao encontrado.');

            return 'total_erros';
        }

        if ($this->jaEnviado($lancamento, $tipo, $dataReferencia)) {
            return 'total_ignorados';
        }

        if ($this->valorEfetivadoReferencia((int) $cliente->id, (int) $lancamento->mes_referencia, (int) $lancamento->ano_referencia) > 0) {
            return 'total_ignorados';
        }

        $telefone = $this->telefoneWhatsapp($cliente);

        if ($telefone === null) {
            $this->registrar($execucao, $lancamento, $tipo, 'erro', $dataReferencia, $vencimento, $valor, erro: 'Telefone celular invalido.');

            return 'total_erros';
        }

        $invoice = $this->invoiceAtiva($lancamento);

        if ($tipo === self::BOLETO_7_DIAS) {
            if ($invoice === null && blank($lancamento->numero_boleto)) {
                if ($dryRun) {
                    $mensagem = $this->montarMensagem($tipo, $cliente, $lancamento, $vencimento, $valor, $plano['dias_atraso']);
                    $this->registrar($execucao, $lancamento, $tipo, 'simulado', $dataReferencia, $vencimento, $valor, $telefone, mensagem: $mensagem);

                    return 'total_enviados';
                }

                try {
                    $invoice = $this->gerarBoleto($lancamento, $cliente, $vencimento);
                } catch (LytexException $exception) {
                    $this->registrar($execucao, $lancamento, $tipo, 'erro', $dataReferencia, $vencimento, $valor, $telefone, erro: $exception->getMessage());

                    return 'total_erros';
                }
            }

            if ($invoice === null || blank($invoice->hash_id)) {
                $this->registrar($execucao, $lancamento, $tipo, 'erro', $dataReferencia, $vencimento, $valor, $telefone, erro: 'Boleto nao encontrado ou sem hash_id.');

                return 'total_erros';
            }

            return $this->registrarEnvioComBoleto($execucao, $lancamento, $invoice, $tipo, $dataReferencia, $vencimento, $valor, $telefone, $plano['dias_atraso'], $dryRun);
        }

        if ($tipo === self::LEMBRETE_VENCIMENTO) {
            $mensagem = $this->montarMensagem($tipo, $cliente, $lancamento, $vencimento, $valor, null);
            $this->registrar($execucao, $lancamento, $tipo, $dryRun ? 'simulado' : 'pendente_whatsapp', $dataReferencia, $vencimento, $valor, $telefone, mensagem: $mensagem);

            return 'total_enviados';
        }

        if (! $this->numeroBoletoLytex($lancamento)) {
            $this->registrar($execucao, $lancamento, $tipo, 'erro', $dataReferencia, $vencimento, $valor, $telefone, erro: 'Atraso sem numero_boleto Lytex. O boleto deveria ter sido gerado antes.');

            return 'total_erros';
        }

        if ($invoice === null || blank($invoice->hash_id)) {
            $this->registrar($execucao, $lancamento, $tipo, 'erro', $dataReferencia, $vencimento, $valor, $telefone, erro: 'Invoice Lytex nao encontrada para envio do atraso.');

            return 'total_erros';
        }

        return $this->registrarEnvioComBoleto($execucao, $lancamento, $invoice, $tipo, $dataReferencia, $vencimento, $valor, $telefone, $plano['dias_atraso'], $dryRun);
    }

    private function registrarEnvioComBoleto(
        CobrancaExecucao $execucao,
        Lancamento $lancamento,
        Invoice $invoice,
        string $tipo,
        CarbonImmutable $dataReferencia,
        CarbonImmutable $vencimento,
        float $valor,
        string $telefone,
        ?int $diasAtraso,
        bool $dryRun,
    ): string {
        $mensagem = $this->montarMensagem($tipo, $lancamento->cliente, $lancamento, $vencimento, $valor, $diasAtraso);
        $whatsappPayload = null;

        if ($diasAtraso !== null) {
            $whatsappPayload = [
                'boletos' => $this->boletosAtrasadosParaWhatsapp($lancamento, $invoice, $vencimento),
            ];
        }

        $this->registrar(
            $execucao,
            $lancamento,
            $tipo,
            $dryRun ? 'simulado' : 'pendente_whatsapp',
            $dataReferencia,
            $vencimento,
            $valor,
            $telefone,
            $invoice,
            $this->invoiceUrl($invoice),
            $this->boletoUrl($invoice),
            $mensagem,
            whatsappPayload: $whatsappPayload,
        );

        return 'total_enviados';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function boletosAtrasadosParaWhatsapp(Lancamento $lancamentoPrincipal, Invoice $invoicePrincipal, CarbonImmutable $vencimentoPrincipal): array
    {
        $boletos = [
            $this->boletoWhatsappPayload($lancamentoPrincipal, $invoicePrincipal, $vencimentoPrincipal),
        ];

        $clienteId = (int) $lancamentoPrincipal->cliente_id;
        $mesPrincipal = (int) $lancamentoPrincipal->mes_referencia;
        $anoPrincipal = (int) $lancamentoPrincipal->ano_referencia;

        Lancamento::query()
            ->where('cliente_id', $clienteId)
            ->where('valor_planejado', '>', 0)
            ->whereNotNull('mes_referencia')
            ->whereNotNull('ano_referencia')
            ->where(function (Builder $query) use ($anoPrincipal, $mesPrincipal): void {
                $query->where('ano_referencia', '<', $anoPrincipal)
                    ->orWhere(function (Builder $query) use ($anoPrincipal, $mesPrincipal): void {
                        $query->where('ano_referencia', $anoPrincipal)
                            ->where('mes_referencia', '<', $mesPrincipal);
                    });
            })
            ->orderBy('ano_referencia')
            ->orderBy('mes_referencia')
            ->orderBy('id')
            ->get()
            ->each(function (Lancamento $lancamento) use (&$boletos): void {
                if (! $this->numeroBoletoLytex($lancamento)) {
                    return;
                }

                if ($this->valorEfetivadoReferencia((int) $lancamento->cliente_id, (int) $lancamento->mes_referencia, (int) $lancamento->ano_referencia) > 0) {
                    return;
                }

                $invoice = $this->invoiceAtiva($lancamento);

                if ($invoice === null || blank($invoice->hash_id)) {
                    return;
                }

                $vencimento = $this->vencimentoDoLancamento($lancamento);

                if ($vencimento === null || ! $vencimento->isPast()) {
                    return;
                }

                $boletos[] = $this->boletoWhatsappPayload($lancamento, $invoice, $vencimento);
            });

        return $boletos;
    }

    /**
     * @return array<string, mixed>
     */
    private function boletoWhatsappPayload(Lancamento $lancamento, Invoice $invoice, CarbonImmutable $vencimento): array
    {
        return [
            'lancamento_id' => $lancamento->id,
            'invoice_id' => $invoice->id,
            'mes_referencia' => (int) $lancamento->mes_referencia,
            'ano_referencia' => (int) $lancamento->ano_referencia,
            'vencimento' => $vencimento->toDateString(),
            'link_boleto' => $this->boletoUrl($invoice),
        ];
    }

    private function gerarBoleto(Lancamento $lancamento, Cliente $cliente, CarbonImmutable $vencimento): Invoice
    {
        $erro = $this->validarDadosBoleto($lancamento, $cliente);

        if ($erro !== null) {
            throw new LytexException($erro);
        }

        $response = app(LytexInvoiceService::class)->criarFatura($this->payloadLytex($lancamento, $cliente, $vencimento));

        return DB::transaction(function () use ($response, $lancamento, $vencimento): Invoice {
            $totalValue = data_get($response, 'totalValue');
            $faturaId = data_get($response, '_id');
            $hashId = data_get($response, '_hashId');

            $invoice = Invoice::query()->updateOrCreate(
                filled($faturaId) ? ['fatura_id' => $faturaId] : ['lancamento_id' => $lancamento->id],
                [
                    'client_id' => data_get($response, '_clientId'),
                    'cpf_cnpj' => data_get($response, 'client.cpfCnpj'),
                    'fatura_id' => $faturaId,
                    'lancamento_id' => $lancamento->id,
                    'total_value' => is_numeric($totalValue) ? ((float) $totalValue / 100) : (float) $lancamento->valor_planejado,
                    'created_at_external' => data_get($response, 'createdAt'),
                    'updated_at_external' => data_get($response, 'updatedAt'),
                    'hash_id' => $hashId,
                    'link_checkout' => data_get($response, 'linkCheckout') ?: (filled($hashId) ? $this->checkoutBaseUrl().'/fatura/'.$hashId : null),
                    'link_boleto' => data_get($response, 'linkBoleto') ?: (filled($hashId) ? $this->publicApiBaseUrl().'/v1/invoices/print/'.$hashId : null),
                    'linha_digitavel' => LytexInvoiceData::linhaDigitavel($response),
                    'pix_copia_cola' => LytexInvoiceData::pixCopiaCola($response),
                    'status' => $this->statusBoletoLocal(data_get($response, 'status')),
                    'vencimento' => $this->dataInvoice(data_get($response, 'dueDate')) ?? $vencimento,
                    'user_id' => auth()->id(),
                ],
            );

            $lancamento->update(['numero_boleto' => 'Lytex']);

            return $invoice;
        });
    }

    private function validarDadosBoleto(Lancamento $lancamento, Cliente $cliente): ?string
    {
        if ((float) $lancamento->valor_planejado <= 0) {
            return 'Valor planejado precisa ser maior que zero.';
        }

        if (Validator::make(['email' => $cliente->email], ['email' => ['required', 'email:rfc']])->fails()) {
            return 'Email do cliente invalido.';
        }

        if (Validator::make(['cpf_cnpj' => $cliente->cpf_cnpj], ['cpf_cnpj' => ['required', new CpfCnpj]])->fails()) {
            return 'CPF ou CNPJ do cliente invalido.';
        }

        $telefone = preg_replace('/\D+/', '', (string) $cliente->telefone1);

        if (strlen($telefone) !== 11 || ! ctype_digit($telefone)) {
            return 'Telefone celular do cliente invalido para a Lytex.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadLytex(Lancamento $lancamento, Cliente $cliente, CarbonImmutable $vencimento): array
    {
        $cpfCnpj = preg_replace('/\D+/', '', (string) $cliente->cpf_cnpj);

        return [
            'client' => [
                'treatmentPronoun' => 'you',
                'name' => trim((string) $cliente->nome),
                'type' => strlen($cpfCnpj) === 11 ? 'pf' : 'pj',
                'cpfCnpj' => $cpfCnpj,
                'email' => trim((string) $cliente->email),
                'cellphone' => preg_replace('/\D+/', '', (string) $cliente->telefone1),
            ],
            'items' => [
                [
                    'name' => sprintf('Mensalidade %s/%s', $lancamento->mes_referencia, $lancamento->ano_referencia),
                    'quantity' => 1,
                    'value' => (string) (int) round(((float) $lancamento->valor_planejado) * 100),
                ],
            ],
            'mulctAndInterest' => [
                'enable' => true,
                'mulct' => [
                    'type' => 'percentage',
                    'value' => 5,
                ],
                'interest' => [
                    'type' => 'percentage',
                    'value' => 0.1,
                ],
            ],
            'paymentMethods' => [
                'pix' => ['enable' => true],
                'boleto' => ['enable' => true],
                'creditCard' => ['enable' => false],
            ],
            'dueDate' => $vencimento->toDateString(),
        ];
    }

    private function registrar(
        CobrancaExecucao $execucao,
        Lancamento $lancamento,
        string $tipo,
        string $status,
        CarbonImmutable $dataReferencia,
        CarbonImmutable $vencimento,
        float $valor,
        ?string $telefone = null,
        ?Invoice $invoice = null,
        ?string $linkInvoice = null,
        ?string $linkBoleto = null,
        ?string $mensagem = null,
        ?string $erro = null,
        ?array $whatsappPayload = null,
    ): CobrancaEnvio {
        $envio = CobrancaEnvio::query()->firstOrNew([
            'lancamento_id' => $lancamento->id,
            'tipo' => $tipo,
            'data_referencia' => $dataReferencia->toDateString(),
        ]);

        $envio->fill([
            'cobranca_execucao_id' => $execucao->id,
            'cliente_id' => $lancamento->cliente_id,
            'invoice_id' => $invoice?->id,
            'status' => $status,
            'vencimento' => $vencimento->toDateString(),
            'valor' => $valor,
            'tentativas' => ((int) $envio->tentativas) + 1,
            'processado_em' => now(),
            'enviado_em' => in_array($status, ['enviado', 'simulado', 'pendente_whatsapp'], true) ? now() : null,
            'telefone' => $telefone,
            'link_invoice' => $linkInvoice,
            'link_boleto' => $linkBoleto,
            'mensagem' => $mensagem,
            'erro' => $erro,
            'whatsapp_payload' => $whatsappPayload === null ? $envio->whatsapp_payload : json_encode($whatsappPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        $envio->save();

        return $envio;
    }

    private function jaEnviado(Lancamento $lancamento, string $tipo, CarbonImmutable $dataReferencia): bool
    {
        return CobrancaEnvio::query()
            ->where('lancamento_id', $lancamento->id)
            ->where('tipo', $tipo)
            ->whereDate('data_referencia', $dataReferencia->toDateString())
            ->whereIn('status', ['enviado', 'pendente_whatsapp'])
            ->exists();
    }

    private function vencimentoDoLancamento(Lancamento $lancamento): ?CarbonImmutable
    {
        if ($lancamento->mes_referencia === null || $lancamento->ano_referencia === null) {
            return null;
        }

        $cliente = $lancamento->cliente;
        $dia = (int) ($cliente?->dia_pagamento ?: 0);

        if ($dia <= 0) {
            return null;
        }

        $base = CarbonImmutable::create((int) $lancamento->ano_referencia, (int) $lancamento->mes_referencia, 1);
        $diaReal = min($dia, (int) $base->endOfMonth()->day);

        return $base->setDay($diaReal);
    }

    private function valorEfetivadoReferencia(int $clienteId, int $mes, int $ano): float
    {
        return (float) Lancamento::query()
            ->where('cliente_id', $clienteId)
            ->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano)
            ->sum('valor_efetivado');
    }

    private function telefoneWhatsapp(Cliente $cliente): ?string
    {
        $telefone = preg_replace('/\D+/', '', (string) $cliente->telefone1);

        if ($telefone === '' || strlen($telefone) < 10) {
            return null;
        }

        $ddi = Pais::codigoTelefone($cliente->telefone1_pais ?: 'BR');

        return $ddi.$telefone;
    }

    private function invoiceAtiva(Lancamento $lancamento): ?Invoice
    {
        return Invoice::query()
            ->where('lancamento_id', $lancamento->id)
            ->where(function (Builder $query): void {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['Cancelado', 'canceled', 'cancelled']);
            })
            ->latest('id')
            ->first();
    }

    private function numeroBoletoLytex(Lancamento $lancamento): bool
    {
        return str((string) $lancamento->numero_boleto)->trim()->lower()->ascii()->toString() === 'lytex';
    }

    private function montarMensagem(string $tipo, ?Cliente $cliente, Lancamento $lancamento, CarbonImmutable $vencimento, float $valor, ?int $diasAtraso): string
    {
        $modelo = CobrancaMensagemModelo::query()
            ->where('tipo', $tipo)
            ->where('canal', 'whatsapp')
            ->where('ativo', true)
            ->orderBy('ordem')
            ->first();

        $conteudo = $modelo?->conteudo ?: $this->mensagemPadrao($tipo);

        $replaces = [
            '{cliente_nome}' => trim((string) ($cliente?->nome ?? '')),
            '{valor}' => 'R$'.number_format($valor, 2, ',', '.'),
            '{vencimento}' => $vencimento->format('d/m/Y'),
            '{dias_atraso}' => (string) ($diasAtraso ?? ''),
            '{mes}' => str_pad((string) $lancamento->mes_referencia, 2, '0', STR_PAD_LEFT),
            '{ano}' => (string) $lancamento->ano_referencia,
        ];

        return str_replace(array_keys($replaces), array_values($replaces), $conteudo);
    }

    private function mensagemPadrao(string $tipo): string
    {
        if ($tipo === self::BOLETO_7_DIAS) {
            return "Ola, {cliente_nome}\nAqui e da Conectta Rastreamento. Tudo bem?\n\nEstou passando para enviar seu boleto com vencimento em {vencimento}.\n\nValor do boleto: {valor}\n\nAtenciosamente,\nConectta Rastreamento";
        }

        if ($tipo === self::LEMBRETE_VENCIMENTO) {
            return "Ola, {cliente_nome}\nAqui e da Conectta Rastreamento. Tudo bem?\n\nEstou passando para lembrar que seu vencimento e hoje.\n\nValor: {valor}\n\nAtenciosamente,\nConectta Rastreamento";
        }

        return "Ola, {cliente_nome}\nAqui e da Conectta Rastreamento. Tudo bem?\n\nEstou passando para lembrar que seu boleto esta vencido ha {dias_atraso} dias. Para nao ter o servico de rastreamento suspenso, segue abaixo seu boleto para pagamento.\n\nValor do boleto: {valor}\n\nAtenciosamente,\nConectta Rastreamento";
    }

    private function invoiceUrl(Invoice $invoice): ?string
    {
        $url = trim((string) $invoice->link_checkout);
        $hash = trim((string) $invoice->hash_id);

        if ($url !== '') {
            return $url;
        }

        return $hash === '' ? null : $this->checkoutBaseUrl().'/fatura/'.$hash;
    }

    private function boletoUrl(Invoice $invoice): ?string
    {
        $url = trim((string) $invoice->link_boleto);
        $hash = trim((string) $invoice->hash_id);

        if ($url !== '') {
            return $url;
        }

        return $hash === '' ? null : $this->publicApiBaseUrl().'/v1/invoices/print/'.$hash;
    }

    private function checkoutBaseUrl(): string
    {
        return $this->ambienteLytex() === 'homologacao'
            ? 'https://sandbox-checkout-pay.lytex.com.br'
            : 'https://checkout-pay.lytex.com.br';
    }

    private function publicApiBaseUrl(): string
    {
        return $this->ambienteLytex() === 'homologacao'
            ? 'https://sandbox-public-api-pay.lytex.com.br'
            : 'https://public-api-pay.lytex.com.br';
    }

    private function ambienteLytex(): string
    {
        return (string) ConfiguracaoIntegracao::lytexAtiva()->ambiente;
    }

    private function dataInvoice(?string $date): ?CarbonImmutable
    {
        if (blank($date)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function statusBoletoLocal(?string $status): ?string
    {
        return match (str((string) $status)->lower()->ascii()->toString()) {
            'waitingpayment', 'waiting_payment', 'pending', 'aguardando pagamento' => 'Aguardando Pagamento',
            'paid', 'pago' => 'Pago',
            'overdue', 'atrasado' => 'Atrasado',
            'cancelled', 'canceled', 'cancelado' => 'Cancelado',
            'processing', 'processando' => 'Processando',
            default => $status,
        };
    }
}
