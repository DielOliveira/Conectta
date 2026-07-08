<?php

namespace App\Filament\Pages;

use App\Models\Cliente;
use App\Models\Invoice;
use App\Models\Lancamento;
use App\Models\Permission;
use App\Models\StatusCliente;
use App\Rules\CpfCnpj;
use App\Services\Audit\AuditLogger;
use App\Services\Lytex\LytexException;
use App\Services\Lytex\LytexInvoiceData;
use App\Services\Lytex\LytexInvoiceService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class Financeiro extends Page
{
    private const BUSCA_COMPARTILHADA_SESSION = 'conectta.busca_cadastros';

    private const STATUS_CLIENTE_COMPARTILHADO_SESSION = 'conectta.status_cliente';

    protected static ?string $slug = 'financeiro';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Financeiro';

    protected static ?string $title = 'Financeiro';

    protected string $view = 'filament.pages.financeiro';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::FINANCEIRO_LEITURA) ?? false;
    }

    public function getHeading(): string
    {
        return '';
    }

    public ?int $statusClienteId = null;

    public string $clienteSearch = '';

    public ?int $diaVencimento = null;

    public int $linhas = 15;

    public int $pagina = 1;

    public string $ordenarPor = 'cliente';

    public string $ordenarDirecao = 'asc';

    public string $consultaMes1 = '';

    public string $consultaMes2 = '';

    public int $numeroBoletoMes1 = 2;

    public int $numeroBoletoMes2 = 2;

    public int $valorEfetuadoMes1 = 2;

    public int $valorEfetuadoMes2 = 2;

    public int $mesBase;

    public int $anoBase;

    public array $vencimentos = [];

    public array $anotacoes = [];

    public bool $lancamentoModalAberto = false;

    public ?int $modalClienteId = null;

    public ?string $modalClienteNome = null;

    public ?int $modalMes = null;

    public ?int $modalAno = null;

    public string $modalAba = 'lancamento';

    public ?int $modalLancamentoId = null;

    public ?string $modalDataLancamento = null;

    public string $modalNumeroBoleto = '';

    public string $modalValorPlanejado = '';

    public string $modalValorEfetivado = '';

    public string $modalObservacao = '';

    public ?string $parcelamentoDataLancamento = null;

    public string $parcelamentoValorEfetivado = '';

    public ?string $boletoVencimento = null;

    public array $ultimoCriarFaturaRequest = [];

    public function mount(): void
    {
        $this->statusClienteId = $this->statusClienteCompartilhado();
        $this->clienteSearch = (string) session(self::BUSCA_COMPARTILHADA_SESSION, '');
        $this->mesBase = (int) now()->month;
        $this->anoBase = (int) now()->year;
    }

    private function autorizar(string $permissao): bool
    {
        if (auth()->user()?->hasPermission($permissao)) {
            return true;
        }

        Notification::make()
            ->title('Voce nao tem permissao para esta acao.')
            ->danger()
            ->send();

        return false;
    }

    public function limparFiltros(): void
    {
        $this->statusClienteId = $this->statusAtivoId();
        $this->clienteSearch = '';
        session()->forget(self::BUSCA_COMPARTILHADA_SESSION);
        $this->sincronizarStatusClienteCompartilhado($this->statusClienteId);
        $this->diaVencimento = null;
        $this->linhas = 15;
        $this->pagina = 1;
        $this->limparFiltrosMensais();
    }

    public function mesAnterior(): void
    {
        $base = $this->mesAtual()->subMonth();

        $this->mesBase = (int) $base->month;
        $this->anoBase = (int) $base->year;
    }

    public function mesProximo(): void
    {
        $base = $this->mesAtual()->addMonth();

        $this->mesBase = (int) $base->month;
        $this->anoBase = (int) $base->year;
    }

    public function updated(string $property, mixed $value): void
    {
        if (in_array($property, ['statusClienteId', 'clienteSearch', 'diaVencimento', 'linhas'], true)) {
            $this->pagina = 1;
            $this->linhas = max(1, min((int) $this->linhas, 200));

            if ($property === 'clienteSearch') {
                $this->sincronizarBuscaCompartilhada($this->clienteSearch);
            }

            if ($property === 'statusClienteId') {
                $this->sincronizarStatusClienteCompartilhado($this->statusClienteId);
            }

            return;
        }

        if (in_array($property, ['consultaMes1', 'numeroBoletoMes1', 'valorEfetuadoMes1'], true)) {
            $this->pagina = 1;
            $this->limparFiltrosMes2();

            return;
        }

        if (in_array($property, ['consultaMes2', 'numeroBoletoMes2', 'valorEfetuadoMes2'], true)) {
            $this->pagina = 1;
            $this->limparFiltrosMes1();

            return;
        }

        if (str_starts_with($property, 'vencimentos.')) {
            $clienteId = (int) str($property)->after('vencimentos.')->toString();
            $this->salvarVencimento($clienteId);

            return;
        }

        if (str_starts_with($property, 'anotacoes.')) {
            $clienteId = (int) str($property)->after('anotacoes.')->toString();
            $this->salvarAnotacao($clienteId);
        }
    }

    public function paginaAnterior(): void
    {
        $this->pagina = max(1, $this->pagina - 1);
    }

    public function paginaProxima(): void
    {
        $this->pagina = min($this->totalPaginas(), $this->pagina + 1);
    }

    public function irParaPagina(int $pagina): void
    {
        $this->pagina = max(1, min($pagina, $this->totalPaginas()));
    }

    public function ordenarClientesPor(string $campo): void
    {
        if (! in_array($campo, ['qtd', 'vendedor', 'cliente', 'vencimento'], true)) {
            return;
        }

        if ($this->ordenarPor === $campo) {
            $this->ordenarDirecao = $this->ordenarDirecao === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->ordenarDirecao = 'asc';
        }

        $this->pagina = 1;
    }

    public function ordenacaoClientesIcone(string $campo): string
    {
        if ($this->ordenarPor !== $campo) {
            return '↕';
        }

        return $this->ordenarDirecao === 'asc' ? '↑' : '↓';
    }

    public function alternarNumeroBoletoMes1(): void
    {
        $this->numeroBoletoMes1 = $this->proximoEstadoFiltro($this->numeroBoletoMes1);
        $this->pagina = 1;
        $this->limparFiltrosMes2();
    }

    public function alternarNumeroBoletoMes2(): void
    {
        $this->numeroBoletoMes2 = $this->proximoEstadoFiltro($this->numeroBoletoMes2);
        $this->pagina = 1;
        $this->limparFiltrosMes1();
    }

    public function alternarValorEfetuadoMes1(): void
    {
        $this->valorEfetuadoMes1 = $this->proximoEstadoFiltro($this->valorEfetuadoMes1);
        $this->pagina = 1;
        $this->limparFiltrosMes2();
    }

    public function alternarValorEfetuadoMes2(): void
    {
        $this->valorEfetuadoMes2 = $this->proximoEstadoFiltro($this->valorEfetuadoMes2);
        $this->pagina = 1;
        $this->limparFiltrosMes1();
    }

    public function salvarVencimento(int $clienteId): void
    {
        if (! $this->autorizar(Permission::FINANCEIRO_ESCRITA)) {
            return;
        }

        $dia = (int) ($this->vencimentos[$clienteId] ?? 0);

        if ($dia < 1 || $dia > 31) {
            Notification::make()
                ->title('Informe um vencimento entre 1 e 31.')
                ->danger()
                ->send();

            return;
        }

        $cliente = Cliente::query()->findOrFail($clienteId);
        $antes = AuditLogger::snapshot($cliente);

        $cliente->forceFill(['dia_pagamento' => $dia])->save();

        AuditLogger::registrar(
            'cliente.vencimento_financeiro',
            'Vencimento do cliente alterado pelo financeiro.',
            $cliente,
            antes: $antes,
            depois: AuditLogger::snapshot($cliente),
            contexto: [
                'dia_pagamento_antes' => $antes['dia_pagamento'] ?? null,
                'dia_pagamento_depois' => $cliente->dia_pagamento,
            ],
        );
    }

    public function salvarAnotacao(int $clienteId): void
    {
        if (! $this->autorizar(Permission::FINANCEIRO_ESCRITA)) {
            return;
        }

        $anotacao = $this->anotacoes[$clienteId] ?? null;

        $cliente = Cliente::query()->findOrFail($clienteId);
        $antes = AuditLogger::snapshot($cliente);

        $cliente->forceFill(['anotacoes' => blank($anotacao) ? null : (string) $anotacao])->save();

        AuditLogger::registrar(
            'cliente.anotacao_financeiro',
            'Anotacao do cliente alterada pelo financeiro.',
            $cliente,
            antes: $antes,
            depois: AuditLogger::snapshot($cliente),
        );
    }

    public function toggleReplicar(int $clienteId): void
    {
        if (! $this->autorizar(Permission::FINANCEIRO_ESCRITA)) {
            return;
        }

        $cliente = Cliente::query()->findOrFail($clienteId);
        $antes = AuditLogger::snapshot($cliente);

        $cliente->forceFill([
            'replicar_pagamento' => ! $cliente->replicar_pagamento,
        ])->save();

        AuditLogger::registrar(
            'cliente.replicar_pagamento_financeiro',
            'Replicar pagamento do cliente alterado pelo financeiro.',
            $cliente,
            antes: $antes,
            depois: AuditLogger::snapshot($cliente),
            contexto: [
                'replicar_pagamento_antes' => $antes['replicar_pagamento'] ?? null,
                'replicar_pagamento_depois' => $cliente->replicar_pagamento,
            ],
        );
    }

    public function replicarPlanejadoMes(int $mes, int $ano): void
    {
        if (! $this->autorizar(Permission::FINANCEIRO_ESCRITA)) {
            return;
        }

        $mesDestino = CarbonImmutable::create($ano, $mes, 1);
        $mesOrigem = $mesDestino->subMonth();

        $clienteIds = (clone $this->clientesQuery())
            ->where('replicar_pagamento', true)
            ->pluck('id');

        if ($clienteIds->isEmpty()) {
            Notification::make()
                ->title('Nenhum cliente com replicacao ativa encontrado.')
                ->warning()
                ->send();

            return;
        }

        $origens = Lancamento::query()
            ->select('cliente_id')
            ->selectRaw('MAX(valor_planejado) as valor_planejado')
            ->whereIn('cliente_id', $clienteIds)
            ->where('mes_referencia', (int) $mesOrigem->month)
            ->where('ano_referencia', (int) $mesOrigem->year)
            ->where('valor_planejado', '>', 0)
            ->groupBy('cliente_id')
            ->get();

        $copiados = 0;
        $ignorados = 0;

        foreach ($origens as $origem) {
            $valorPlanejado = (float) $origem->valor_planejado;

            if ($valorPlanejado <= 0) {
                $ignorados++;

                continue;
            }

            $jaTemValor = Lancamento::query()
                ->where('cliente_id', $origem->cliente_id)
                ->where('mes_referencia', $mes)
                ->where('ano_referencia', $ano)
                ->where('valor_planejado', '>', 0)
                ->exists();

            if ($jaTemValor) {
                $ignorados++;

                continue;
            }

            $lancamento = Lancamento::query()
                ->where('cliente_id', $origem->cliente_id)
                ->where('mes_referencia', $mes)
                ->where('ano_referencia', $ano)
                ->orderBy('id')
                ->first();

            if ($lancamento) {
                $lancamento->forceFill([
                    'valor_planejado' => $valorPlanejado,
                    'time_stamp' => now(),
                ])->save();
            } else {
                Lancamento::query()->create([
                    'cliente_id' => $origem->cliente_id,
                    'mes_referencia' => $mes,
                    'ano_referencia' => $ano,
                    'valor_planejado' => $valorPlanejado,
                    'time_stamp' => now(),
                ]);
            }

            $copiados++;
        }

        AuditLogger::registrar(
            'financeiro.planejados_replicados',
            'Valores planejados replicados do mes anterior.',
            entidadeTipo: 'Lancamento',
            contexto: [
                'mes_origem' => (int) $mesOrigem->month,
                'ano_origem' => (int) $mesOrigem->year,
                'mes_destino' => $mes,
                'ano_destino' => $ano,
                'clientes_com_replicacao' => $clienteIds->count(),
                'lancamentos_copiados' => $copiados,
                'lancamentos_ignorados' => $ignorados,
            ],
        );

        Notification::make()
            ->title($copiados === 1 ? '1 valor planejado replicado.' : $copiados.' valores planejados replicados.')
            ->body($ignorados > 0 ? $ignorados.' clientes foram ignorados por ja terem valor ou nao terem valor no mes anterior.' : null)
            ->success()
            ->send();
    }

    public function abrirLancamento(int $clienteId, int $mes, int $ano): void
    {
        $cliente = Cliente::query()->findOrFail($clienteId);

        $this->modalClienteId = $cliente->id;
        $this->modalClienteNome = $cliente->nome;
        $this->modalMes = $mes;
        $this->modalAno = $ano;
        $this->modalAba = 'lancamento';
        $this->carregarLancamentoModal();
        $this->lancamentoModalAberto = true;
    }

    public function fecharLancamento(): void
    {
        $this->reset([
            'lancamentoModalAberto',
            'modalClienteId',
            'modalClienteNome',
            'modalMes',
            'modalAno',
            'modalAba',
            'modalLancamentoId',
            'modalDataLancamento',
            'modalNumeroBoleto',
            'modalValorPlanejado',
            'modalValorEfetivado',
            'modalObservacao',
            'parcelamentoDataLancamento',
            'parcelamentoValorEfetivado',
            'boletoVencimento',
            'ultimoCriarFaturaRequest',
        ]);

        $this->modalAba = 'lancamento';
    }

    public function modalMesAnterior(): void
    {
        if ($this->modalMes === null || $this->modalAno === null) {
            return;
        }

        $data = CarbonImmutable::create($this->modalAno, $this->modalMes, 1)->subMonth();
        $this->modalMes = (int) $data->month;
        $this->modalAno = (int) $data->year;
        $this->carregarLancamentoModal();
    }

    public function modalMesProximo(): void
    {
        if ($this->modalMes === null || $this->modalAno === null) {
            return;
        }

        $data = CarbonImmutable::create($this->modalAno, $this->modalMes, 1)->addMonth();
        $this->modalMes = (int) $data->month;
        $this->modalAno = (int) $data->year;
        $this->carregarLancamentoModal();
    }

    public function selecionarAbaModal(string $aba): void
    {
        if (! in_array($aba, ['lancamento', 'parcelamento', 'boleto'], true)) {
            return;
        }

        $this->modalAba = $aba;
    }

    public function confirmarCancelamentoBoletoAction(): Action
    {
        return Action::make('confirmarCancelamentoBoleto')
            ->label('Cancelar boleto')
            ->modalSubmitActionLabel('Cancelar boleto')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Deseja realmente cancelar este boleto na Lytex?')
            ->action(fn (): mixed => $this->cancelarBoleto());
    }

    public function salvarLancamentoModal(): void
    {
        if (! $this->autorizar(Permission::FINANCEIRO_ESCRITA)) {
            return;
        }

        if ($this->modalClienteId === null || $this->modalMes === null || $this->modalAno === null) {
            return;
        }

        $this->validate([
            'modalDataLancamento' => ['required', 'date'],
            'modalNumeroBoleto' => ['nullable', 'string', 'max:500'],
            'modalValorPlanejado' => ['nullable', 'string', 'max:50'],
            'modalValorEfetivado' => ['nullable', 'string', 'max:50'],
            'modalObservacao' => ['nullable', 'string', 'max:500'],
        ], [], [
            'modalDataLancamento' => 'data lancamento',
            'modalNumeroBoleto' => 'numero boleto',
            'modalValorPlanejado' => 'valor planejado',
            'modalValorEfetivado' => 'valor efetivado',
            'modalObservacao' => 'observacao',
        ]);

        $antes = null;
        $totalAntes = $this->totalEfetivadoReferencia($this->modalMes, $this->modalAno);

        if ($this->modalLancamentoId) {
            $lancamentoAtual = Lancamento::query()->find($this->modalLancamentoId);
            $antes = $lancamentoAtual ? AuditLogger::snapshot($lancamentoAtual) : null;
        }

        $lancamento = Lancamento::query()->updateOrCreate(
            ['id' => $this->modalLancamentoId],
            [
                'cliente_id' => $this->modalClienteId,
                'data_lancamento' => $this->modalDataLancamento,
                'numero_boleto' => blank($this->modalNumeroBoleto) ? null : $this->modalNumeroBoleto,
                'ano_referencia' => $this->modalAno,
                'mes_referencia' => $this->modalMes,
                'valor_planejado' => $this->valorDecimal($this->modalValorPlanejado),
                'valor_efetivado' => $this->valorDecimal($this->modalValorEfetivado),
                'observacao' => blank($this->modalObservacao) ? null : $this->modalObservacao,
                'time_stamp' => now(),
            ],
        );

        $this->modalLancamentoId = $lancamento->id;
        $totalDepois = $this->totalEfetivadoReferencia($this->modalMes, $this->modalAno);

        AuditLogger::registrar(
            $antes ? 'financeiro.lancamento_editado' : 'financeiro.lancamento_criado',
            $antes ? 'Lancamento financeiro editado.' : 'Lancamento financeiro criado.',
            $lancamento,
            antes: $antes,
            depois: AuditLogger::snapshot($lancamento),
            contexto: [
                'cliente_id' => $this->modalClienteId,
                'mes_referencia' => $this->modalMes,
                'ano_referencia' => $this->modalAno,
                'total_antes' => $totalAntes,
                'total_depois' => $totalDepois,
            ],
        );

        Notification::make()
            ->title('Lancamento salvo.')
            ->success()
            ->send();

        $this->fecharLancamento();
    }

    public function mesNome(?int $mes): string
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Marco',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ][$mes] ?? '';
    }

    public function podeLancarParcelamento(): bool
    {
        if ($this->modalLancamentoId === null) {
            return false;
        }

        return Lancamento::query()
            ->whereKey($this->modalLancamentoId)
            ->whereNotNull('valor_efetivado')
            ->where('valor_efetivado', '<>', 0)
            ->exists();
    }

    public function parcelamentosModal(): EloquentCollection
    {
        if ($this->modalClienteId === null || $this->modalMes === null || $this->modalAno === null) {
            return new EloquentCollection;
        }

        return Lancamento::query()
            ->where('cliente_id', $this->modalClienteId)
            ->where('mes_referencia', $this->modalMes)
            ->where('ano_referencia', $this->modalAno)
            ->orderBy('id')
            ->get();
    }

    public function totalParcelamentosModal(): float
    {
        return $this->parcelamentosModal()
            ->sum(fn (Lancamento $lancamento): float => (float) ($lancamento->valor_efetivado ?? 0));
    }

    public function lancarParcelamento(): void
    {
        if (! $this->autorizar(Permission::FINANCEIRO_ESCRITA)) {
            return;
        }

        if (! $this->podeLancarParcelamento() || $this->modalClienteId === null || $this->modalMes === null || $this->modalAno === null) {
            return;
        }

        $this->validate([
            'parcelamentoDataLancamento' => ['required', 'date'],
            'parcelamentoValorEfetivado' => ['required', 'string', 'max:50'],
        ], [], [
            'parcelamentoDataLancamento' => 'data lancamento',
            'parcelamentoValorEfetivado' => 'valor efetivado',
        ]);

        $totalAntes = $this->totalEfetivadoReferencia($this->modalMes, $this->modalAno);

        $lancamento = Lancamento::query()->create([
            'cliente_id' => $this->modalClienteId,
            'data_lancamento' => $this->parcelamentoDataLancamento,
            'valor_efetivado' => $this->valorDecimal($this->parcelamentoValorEfetivado),
            'mes_referencia' => $this->modalMes,
            'ano_referencia' => $this->modalAno,
            'time_stamp' => now(),
        ]);
        $totalDepois = $this->totalEfetivadoReferencia($this->modalMes, $this->modalAno);

        AuditLogger::registrar(
            'financeiro.parcelamento_criado',
            'Parcelamento financeiro criado.',
            $lancamento,
            depois: AuditLogger::snapshot($lancamento),
            contexto: [
                'lancamento_principal_id' => $this->modalLancamentoId,
                'cliente_id' => $this->modalClienteId,
                'mes_referencia' => $this->modalMes,
                'ano_referencia' => $this->modalAno,
                'total_antes' => $totalAntes,
                'total_depois' => $totalDepois,
            ],
        );

        $this->parcelamentoDataLancamento = null;
        $this->parcelamentoValorEfetivado = '';

        Notification::make()
            ->title('Parcelamento lancado.')
            ->success()
            ->send();
    }

    public function excluirParcelamento(int $lancamentoId): void
    {
        if (! $this->autorizar(Permission::FINANCEIRO_ESCRITA)) {
            return;
        }

        if ($this->modalLancamentoId === $lancamentoId) {
            return;
        }

        $lancamento = Lancamento::query()
            ->whereKey($lancamentoId)
            ->where('cliente_id', $this->modalClienteId)
            ->first();

        if (! $lancamento) {
            return;
        }

        $antes = AuditLogger::snapshot($lancamento);
        $mesReferencia = (int) $lancamento->mes_referencia;
        $anoReferencia = (int) $lancamento->ano_referencia;
        $totalAntes = $this->totalEfetivadoReferencia($mesReferencia, $anoReferencia);
        $lancamento->delete();
        $totalDepois = $this->totalEfetivadoReferencia($mesReferencia, $anoReferencia);

        AuditLogger::registrar(
            'financeiro.parcelamento_excluido',
            'Parcelamento financeiro excluido.',
            entidadeTipo: 'Lancamento',
            entidadeId: $lancamentoId,
            antes: $antes,
            contexto: [
                'lancamento_principal_id' => $this->modalLancamentoId,
                'cliente_id' => $this->modalClienteId,
                'mes_referencia' => $mesReferencia,
                'ano_referencia' => $anoReferencia,
                'total_antes' => $totalAntes,
                'total_depois' => $totalDepois,
            ],
        );
    }

    public function gerarBoleto(): void
    {
        if (! $this->autorizar(Permission::BOLETOS_ESCRITA)) {
            return;
        }

        $erro = $this->validarGeracaoBoleto();

        if ($erro !== null) {
            Notification::make()
                ->title($erro)
                ->danger()
                ->send();

            return;
        }

        $this->ultimoCriarFaturaRequest = $this->criarFaturaRequest();

        try {
            $response = app(LytexInvoiceService::class)->criarFatura($this->ultimoCriarFaturaRequest);
        } catch (LytexException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $invoice = $this->salvarInvoiceLytex($response);

        AuditLogger::registrar(
            'boleto.gerado',
            'Boleto gerado pela Lytex.',
            $invoice,
            depois: AuditLogger::snapshot($invoice),
            contexto: [
                'lancamento_id' => $this->modalLancamentoId,
                'cliente_id' => $this->modalClienteId,
                'mes_referencia' => $this->modalMes,
                'ano_referencia' => $this->modalAno,
            ],
        );

        Notification::make()
            ->title('Boleto gerado com sucesso.')
            ->success()
            ->send();
    }

    public function atualizarBoleto(): void
    {
        if (! $this->autorizar(Permission::BOLETOS_ESCRITA)) {
            return;
        }

        $invoice = $this->boletoModal();

        if ($invoice === null || blank($invoice->fatura_id)) {
            Notification::make()
                ->title('Boleto nao encontrado para atualizar.')
                ->danger()
                ->send();

            return;
        }

        try {
            $response = app(LytexInvoiceService::class)->detalhesFatura($invoice->fatura_id);
        } catch (LytexException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $antes = AuditLogger::snapshot($invoice);
        $invoice = $this->salvarInvoiceLytex($response);

        AuditLogger::registrar(
            'boleto.atualizado',
            'Boleto atualizado pela Lytex.',
            $invoice,
            antes: $antes,
            depois: AuditLogger::snapshot($invoice),
            contexto: [
                'lancamento_id' => $this->modalLancamentoId,
                'cliente_id' => $this->modalClienteId,
            ],
        );

        Notification::make()
            ->title('Boleto atualizado.')
            ->success()
            ->send();
    }

    public function realizarBaixaBoleto(): void
    {
        if (! $this->autorizar(Permission::BOLETOS_BAIXAR)) {
            return;
        }

        Notification::make()
            ->title('Baixa de boleto sera conectada na proxima etapa.')
            ->warning()
            ->send();
    }

    public function cancelarBoleto(): void
    {
        if (! $this->autorizar(Permission::BOLETOS_ESCRITA)) {
            return;
        }

        $invoice = $this->boletoModal();

        if ($invoice === null || blank($invoice->fatura_id)) {
            Notification::make()
                ->title('Boleto nao encontrado para cancelar.')
                ->danger()
                ->send();

            return;
        }

        try {
            app(LytexInvoiceService::class)->cancelarFatura($invoice->fatura_id);
            $response = app(LytexInvoiceService::class)->detalhesFatura($invoice->fatura_id);
        } catch (LytexException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $antes = AuditLogger::snapshot($invoice);
        $invoice = $this->salvarInvoiceLytex($response);

        AuditLogger::registrar(
            'boleto.cancelado',
            'Boleto cancelado pela Lytex.',
            $invoice,
            antes: $antes,
            depois: AuditLogger::snapshot($invoice),
            contexto: [
                'lancamento_id' => $this->modalLancamentoId,
                'cliente_id' => $this->modalClienteId,
            ],
        );

        Notification::make()
            ->title('Boleto cancelado.')
            ->success()
            ->send();
    }

    public function boletoModal(): ?Invoice
    {
        if ($this->modalLancamentoId === null) {
            return null;
        }

        return Invoice::query()
            ->where('lancamento_id', $this->modalLancamentoId)
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['Cancelado', 'canceled', 'cancelled']);
            })
            ->latest('id')
            ->first();
    }

    public function boletosModal(): EloquentCollection
    {
        if ($this->modalLancamentoId === null) {
            return new EloquentCollection;
        }

        return Invoice::query()
            ->where('lancamento_id', $this->modalLancamentoId)
            ->latest('id')
            ->get();
    }

    public function boletoValor(): string
    {
        $invoice = $this->boletoModal();

        if ($invoice?->total_value !== null) {
            return 'R$'.$this->moeda($invoice->total_value);
        }

        return 'R$'.$this->moeda($this->valorPlanejadoBoleto());
    }

    public function boletoVencimentoExibicao(): string
    {
        $invoice = $this->boletoModal();

        return $invoice?->vencimento?->format('Y/m/d') ?? (string) $this->boletoVencimento;
    }

    public function boletoStatus(): string
    {
        return $this->statusBoletoLabel($this->boletoModal()?->status ?? 'Nao gerado');
    }

    public function boletoStatusClasse(?string $status = null): string
    {
        return match ($this->statusBoletoKey($status ?? $this->boletoModal()?->status ?? 'Nao gerado')) {
            'aguardando_pagamento', 'waiting_payment', 'waitingpayment', 'waiting', 'pending' => 'ct-fin-boleto-status-warning',
            'pago', 'paid' => 'ct-fin-boleto-status-success',
            'atrasado', 'overdue', 'late', 'cancelado', 'canceled', 'cancelled' => 'ct-fin-boleto-status-danger',
            'processando', 'processing' => 'ct-fin-boleto-status-orange',
            'nao_gerado' => 'ct-fin-boleto-status-neutral',
            default => 'ct-fin-boleto-status-neutral',
        };
    }

    public function statusBoletoLabel(?string $status): string
    {
        $status = trim((string) $status);

        if ($status === '') {
            return '';
        }

        return match ($this->statusBoletoKey($status)) {
            'paid', 'pago' => 'Pago',
            'canceled', 'cancelled', 'cancelado' => 'Cancelado',
            'overdue', 'late', 'atrasado' => 'Atrasado',
            'processing', 'processando' => 'Processando',
            'waiting_payment', 'waitingpayment', 'waiting', 'pending', 'aguardando_pagamento' => 'Aguardando Pagamento',
            'nao_gerado' => 'Nao gerado',
            default => str($status)->headline()->toString(),
        };
    }

    private function statusBoletoKey(?string $status): string
    {
        return str((string) $status)
            ->lower()
            ->ascii()
            ->replace(['-', ' '], '_')
            ->toString();
    }

    public function boletoInvoiceUrl(?Invoice $invoice = null): ?string
    {
        $boleto = $invoice ?? $this->boletoModal();
        $url = trim((string) ($boleto?->link_checkout ?? ''));
        $hash = trim((string) ($boleto?->hash_id ?? ''));

        return $url !== '' ? $url : ($hash === '' ? null : 'https://checkout-pay.lytex.com.br/fatura/'.$hash);
    }

    public function boletoPrintUrl(?Invoice $invoice = null): ?string
    {
        $boleto = $invoice ?? $this->boletoModal();
        $url = trim((string) ($boleto?->link_boleto ?? ''));
        $hash = trim((string) ($boleto?->hash_id ?? ''));

        return $url !== '' ? $url : ($hash === '' ? null : 'https://public-api-pay.lytex.com.br/v1/invoices/print/'.$hash);
    }

    public function boletoPodeRealizarBaixa(?Invoice $invoice = null): bool
    {
        return str((string) (($invoice ?? $this->boletoModal())?->status ?? ''))
            ->lower()
            ->ascii()
            ->toString() === 'pago';
    }

    private function validarGeracaoBoleto(): ?string
    {
        if ($this->modalClienteId === null) {
            return 'Cliente nao informado para gerar boleto.';
        }

        $cliente = Cliente::query()->find($this->modalClienteId);

        if ($cliente === null) {
            return 'Cliente nao encontrado.';
        }

        if ($this->modalLancamentoId === null || $this->modalMes === null || $this->modalAno === null) {
            return 'Lancamento nao encontrado para gerar boleto.';
        }

        $lancamento = Lancamento::query()
            ->whereKey($this->modalLancamentoId)
            ->where('cliente_id', $cliente->id)
            ->first();

        if ($lancamento === null) {
            return 'Lancamento nao encontrado para gerar boleto.';
        }

        if ($this->existeBoletoPago($cliente->id, $this->modalMes, $this->modalAno)) {
            return 'Ja existe boleto pago para esta referencia.';
        }

        if ($this->existeBoletoEmAberto($cliente->id, $this->modalMes, $this->modalAno)) {
            return 'Ja existe boleto gerado para esta referencia.';
        }

        if ($this->valorEfetivadoReferencia($cliente->id, $this->modalMes, $this->modalAno) > 0) {
            return 'Esta referencia ja esta quitada.';
        }

        if ((float) ($lancamento->valor_planejado ?? 0) <= 0) {
            return 'A fatura ainda nao esta disponivel para ser gerada.';
        }

        if (! $this->referenciaDisponivelParaBoleto($this->modalMes, $this->modalAno)) {
            return 'A fatura ainda nao esta disponivel para ser gerada.';
        }

        if (Validator::make(['email' => $cliente->email], ['email' => ['required', 'email:rfc']])->fails()) {
            return 'Email do cliente invalido.';
        }

        $telefone = preg_replace('/\D+/', '', (string) $cliente->telefone1);

        if (strlen($telefone) !== 11 || ! ctype_digit($telefone)) {
            return 'Telefone celular do cliente invalido.';
        }

        if (Validator::make(['cpf_cnpj' => $cliente->cpf_cnpj], ['cpf_cnpj' => ['required', new CpfCnpj]])->fails()) {
            return 'CPF ou CNPJ do cliente invalido.';
        }

        if (! $this->nascimentoValido($cliente)) {
            return 'Data de nascimento do cliente invalida.';
        }

        return null;
    }

    private function valorPlanejadoBoleto(): float
    {
        if ($this->modalLancamentoId === null) {
            return 0;
        }

        return (float) (Lancamento::query()
            ->whereKey($this->modalLancamentoId)
            ->value('valor_planejado') ?? 0);
    }

    private function existeBoletoPago(int $clienteId, int $mes, int $ano): bool
    {
        return $this->invoicesDaReferencia($clienteId, $mes, $ano)
            ->whereIn('invoices.status', ['paid', 'Pago'])
            ->exists();
    }

    private function existeBoletoEmAberto(int $clienteId, int $mes, int $ano): bool
    {
        return $this->invoicesDaReferencia($clienteId, $mes, $ano)
            ->whereIn('invoices.status', [
                'Aguardando Pagamento',
                'Processando',
                'Atrasado',
                'waiting_payment',
                'pending',
                'processing',
                'overdue',
            ])
            ->exists();
    }

    private function invoicesDaReferencia(int $clienteId, int $mes, int $ano)
    {
        return Invoice::query()
            ->join('lancamentos', 'invoices.lancamento_id', '=', 'lancamentos.id')
            ->where('lancamentos.cliente_id', $clienteId)
            ->where('lancamentos.mes_referencia', $mes)
            ->where('lancamentos.ano_referencia', $ano);
    }

    private function valorEfetivadoReferencia(int $clienteId, int $mes, int $ano): float
    {
        return (float) Lancamento::query()
            ->where('cliente_id', $clienteId)
            ->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano)
            ->sum('valor_efetivado');
    }

    private function referenciaDisponivelParaBoleto(int $mes, int $ano): bool
    {
        $referencia = ($ano * 12) + $mes;
        $referenciaAtual = ((int) now()->year * 12) + (int) now()->month;

        return $referencia >= ($referenciaAtual - 6)
            && $referencia <= ($referenciaAtual + 2);
    }

    private function nascimentoValido(Cliente $cliente): bool
    {
        if (blank($cliente->nascimento)) {
            return true;
        }

        return $cliente->nascimento instanceof \DateTimeInterface;
    }

    private function criarFaturaRequest(): array
    {
        $cliente = Cliente::query()->findOrFail($this->modalClienteId);
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
                    'name' => sprintf('Mensalidade %s/%s', $this->modalMes, $this->modalAno),
                    'quantity' => 1,
                    'value' => $this->valorCentavos($this->valorPlanejadoBoleto()),
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
                'pix' => [
                    'enable' => true,
                ],
                'boleto' => [
                    'enable' => true,
                ],
                'creditCard' => [
                    'enable' => false,
                ],
            ],
            'dueDate' => $this->boletoVencimento,
        ];
    }

    private function valorCentavos(float $valor): string
    {
        return (string) (int) round($valor * 100);
    }

    private function salvarInvoiceLytex(array $response): Invoice
    {
        $dueDate = data_get($response, 'dueDate', $this->boletoVencimento);
        $totalValue = data_get($response, 'totalValue');

        $faturaId = data_get($response, '_id');
        $hashId = data_get($response, '_hashId');

        $invoice = Invoice::query()->updateOrCreate(
            filled($faturaId)
                ? ['fatura_id' => $faturaId]
                : ['lancamento_id' => $this->modalLancamentoId],
            [
                'client_id' => data_get($response, '_clientId'),
                'cpf_cnpj' => data_get($response, 'client.cpfCnpj'),
                'fatura_id' => $faturaId,
                'lancamento_id' => $this->modalLancamentoId,
                'total_value' => is_numeric($totalValue) ? ((float) $totalValue / 100) : $this->valorPlanejadoBoleto(),
                'created_at_external' => data_get($response, 'createdAt'),
                'updated_at_external' => data_get($response, 'updatedAt'),
                'hash_id' => $hashId,
                'link_checkout' => data_get($response, 'linkCheckout') ?: (filled($hashId) ? 'https://checkout-pay.lytex.com.br/fatura/'.$hashId : null),
                'link_boleto' => data_get($response, 'linkBoleto') ?: (filled($hashId) ? 'https://public-api-pay.lytex.com.br/v1/invoices/print/'.$hashId : null),
                'linha_digitavel' => LytexInvoiceData::linhaDigitavel($response),
                'pix_copia_cola' => LytexInvoiceData::pixCopiaCola($response),
                'status' => $this->statusBoletoLocal(data_get($response, 'status')),
                'vencimento' => $this->dataInvoice($dueDate),
                'user_id' => auth()->id(),
            ],
        );

        Lancamento::query()
            ->whereKey($this->modalLancamentoId)
            ->update(['numero_boleto' => 'Lytex']);

        return $invoice;
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

    public function exportarCsv(): StreamedResponse
    {
        $paginaAtual = $this->pagina;
        $this->pagina = 1;
        $linhasOriginais = $this->linhas;
        $this->linhas = min(max($this->totalClientes(), 1), 10000);
        $linhas = $this->linhasFinanceiro();
        $this->linhas = $linhasOriginais;
        $this->pagina = $paginaAtual;
        [$mes1, $mes2] = $this->mesesVisiveis();
        $fileName = 'financeiro-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($linhas, $mes1, $mes2): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Qtd',
                'Vendedor',
                'Cliente',
                'Vencimento',
                'Anotacoes',
                'Replicar Pagamento',
                "Boleto {$mes1['label']}",
                "Planejado {$mes1['label']}",
                "Efetuado {$mes1['label']}",
                "Observacao {$mes1['label']}",
                "Boleto {$mes2['label']}",
                "Planejado {$mes2['label']}",
                "Efetuado {$mes2['label']}",
                "Observacao {$mes2['label']}",
            ], ';');

            foreach ($linhas as $linha) {
                fputcsv($handle, [
                    $linha['qtd'],
                    $linha['vendedor'],
                    $linha['cliente']->nome,
                    $linha['cliente']->dia_pagamento,
                    $linha['cliente']->anotacoes,
                    $linha['cliente']->replicar_pagamento ? 'Sim' : 'Nao',
                    $linha['mes1']->numero_boleto ?? '',
                    $linha['mes1']->valor_planejado ?? '',
                    $linha['mes1']->valor_efetivado ?? '',
                    $linha['mes1']->observacao ?? '',
                    $linha['mes2']->numero_boleto ?? '',
                    $linha['mes2']->valor_planejado ?? '',
                    $linha['mes2']->valor_efetivado ?? '',
                    $linha['mes2']->observacao ?? '',
                ], ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function statusClientes(): EloquentCollection
    {
        return StatusCliente::query()
            ->orderBy('order')
            ->orderBy('label')
            ->get();
    }

    public function mesesVisiveis(): array
    {
        $mes1 = $this->mesAtual();
        $mes2 = $mes1->addMonth();

        return [
            [
                'mes' => (int) $mes1->month,
                'ano' => (int) $mes1->year,
                'label' => $mes1->format('n/Y'),
            ],
            [
                'mes' => (int) $mes2->month,
                'ano' => (int) $mes2->year,
                'label' => $mes2->format('n/Y'),
            ],
        ];
    }

    public function linhasFinanceiro(): Collection
    {
        [$mes1, $mes2] = $this->mesesVisiveis();
        $perPage = $this->linhasPorPagina();

        $clientes = $this->clientesQuery()
            ->with(['vendedor', 'statusCliente'])
            ->withCount(['veiculosAtivos as qtd_rastreadores'])
            ->tap(fn (Builder $query): Builder => $this->aplicarOrdenacaoClientes($query))
            ->offset(($this->pagina - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $clientes->each(function (Cliente $cliente): void {
            if (! array_key_exists($cliente->id, $this->vencimentos)) {
                $this->vencimentos[$cliente->id] = $cliente->dia_pagamento;
            }

            if (! array_key_exists($cliente->id, $this->anotacoes)) {
                $this->anotacoes[$cliente->id] = $cliente->anotacoes;
            }
        });

        $clienteIds = $clientes->pluck('id');
        $agregadoMes1 = $this->lancamentosAgregados($mes1['mes'], $mes1['ano'], $clienteIds);
        $agregadoMes2 = $this->lancamentosAgregados($mes2['mes'], $mes2['ano'], $clienteIds);

        return $clientes->map(fn (Cliente $cliente): array => [
            'cliente' => $cliente,
            'qtd' => $cliente->qtd_rastreadores,
            'vendedor' => $cliente->vendedor?->nome ?? '',
            'mes1' => $agregadoMes1->get($cliente->id),
            'mes2' => $agregadoMes2->get($cliente->id),
        ]);
    }

    public function moeda(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        $valor = (float) $valor;

        if (abs($valor) < 0.005) {
            return '';
        }

        return number_format($valor, 2, ',', '.');
    }

    public function totalClientes(): int
    {
        return (clone $this->clientesQuery())->toBase()->getCountForPagination();
    }

    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->totalClientes() / $this->linhasPorPagina()));
    }

    public function inicioPagina(): int
    {
        if ($this->totalClientes() === 0) {
            return 0;
        }

        return (($this->pagina - 1) * $this->linhasPorPagina()) + 1;
    }

    public function fimPagina(): int
    {
        return min($this->totalClientes(), $this->pagina * $this->linhasPorPagina());
    }

    public function paginasVisiveis(): array
    {
        $total = $this->totalPaginas();

        if ($total <= 7) {
            return range(1, $total);
        }

        $pages = collect([1, $this->pagina - 1, $this->pagina, $this->pagina + 1, $total])
            ->filter(fn (int $page): bool => $page >= 1 && $page <= $total)
            ->unique()
            ->sort()
            ->values();

        return $pages->all();
    }

    public function totaisMes(int $mes, int $ano): array
    {
        $clienteIds = (clone $this->clientesQuery())->pluck('id');
        $agregados = $this->lancamentosAgregados($mes, $ano, $clienteIds);

        return [
            'planejado' => $agregados->sum(fn (mixed $lancamento): float => (float) ($lancamento->valor_planejado ?? 0)),
            'efetivado' => $agregados->sum(fn (mixed $lancamento): float => (float) ($lancamento->valor_efetivado ?? 0)),
        ];
    }

    private function clientesQuery(): Builder
    {
        [$mes1, $mes2] = $this->mesesVisiveis();

        return Cliente::query()
            ->whereNull('data_exclusao')
            ->when($this->statusClienteId, fn ($query): mixed => $query->where('status_cliente_id', $this->statusClienteId))
            ->when($this->diaVencimento, fn ($query): mixed => $query->where('dia_pagamento', $this->diaVencimento))
            ->when($this->clienteSearch !== '', function ($query): void {
                $search = '%'.$this->clienteSearch.'%';
                $digits = preg_replace('/\D+/', '', $this->clienteSearch);

                $query->where(function ($query) use ($search, $digits): void {
                    $query
                        ->where('nome', 'like', $search)
                        ->orWhere('anotacoes', 'like', $search);

                    if ($digits !== '') {
                        $query->orWhere('cpf_cnpj', 'like', '%'.$digits.'%');
                    }
                });
            })
            ->tap(fn ($query) => $this->aplicarFiltrosMensais($query, $mes1, $mes2));
    }

    private function aplicarOrdenacaoClientes(Builder $query): Builder
    {
        $direcao = $this->ordenarDirecao === 'desc' ? 'desc' : 'asc';

        return match ($this->ordenarPor) {
            'qtd' => $query
                ->orderBy('qtd_rastreadores', $direcao)
                ->orderBy('nome'),
            'vendedor' => $query
                ->orderBy(
                    \App\Models\Vendedor::query()
                        ->select('nome')
                        ->whereColumn('vendedores.id', 'clientes.vendedor_id')
                        ->limit(1),
                    $direcao,
                )
                ->orderBy('nome'),
            'vencimento' => $query
                ->orderBy('dia_pagamento', $direcao)
                ->orderBy('nome'),
            default => $query->orderBy('nome', $direcao),
        };
    }

    private function aplicarFiltrosMensais(Builder $query, array $mes1, array $mes2): void
    {
        $this->aplicarFiltroMensal(
            $query,
            $mes1['mes'],
            $mes1['ano'],
            $this->consultaMes1,
            $this->numeroBoletoMes1,
            $this->valorEfetuadoMes1,
        );

        $this->aplicarFiltroMensal(
            $query,
            $mes2['mes'],
            $mes2['ano'],
            $this->consultaMes2,
            $this->numeroBoletoMes2,
            $this->valorEfetuadoMes2,
        );
    }

    private function aplicarFiltroMensal(
        Builder $query,
        int $mes,
        int $ano,
        string $consulta,
        int $numeroBoleto,
        int $valorEfetuado,
    ): void {
        if ($consulta === '' && $numeroBoleto === 2 && $valorEfetuado === 2) {
            return;
        }

        if ($consulta !== '') {
            $query->whereIn('id', $this->clientesComConsultaMensal($mes, $ano, $consulta));
        }

        if ($numeroBoleto === 1) {
            $query->whereIn('id', $this->clientesComNumeroBoleto($mes, $ano));
        }

        if ($numeroBoleto === 0) {
            $query->whereNotIn('id', $this->clientesComNumeroBoleto($mes, $ano));
        }

        if ($valorEfetuado === 1) {
            $query->whereIn('id', $this->clientesComValorEfetuado($mes, $ano));
        }

        if ($valorEfetuado === 0) {
            $query->whereNotIn('id', $this->clientesComValorEfetuado($mes, $ano));
        }
    }

    private function linhasPorPagina(): int
    {
        return max(1, min((int) $this->linhas, 200));
    }

    private function carregarLancamentoModal(): void
    {
        if ($this->modalClienteId === null || $this->modalMes === null || $this->modalAno === null) {
            return;
        }

        $lancamento = Lancamento::query()
            ->where('cliente_id', $this->modalClienteId)
            ->where('mes_referencia', $this->modalMes)
            ->where('ano_referencia', $this->modalAno)
            ->orderBy('id')
            ->first();

        $this->modalLancamentoId = $lancamento?->id;
        $this->modalDataLancamento = $lancamento?->data_lancamento?->toDateString() ?? now()->toDateString();
        $this->modalNumeroBoleto = (string) ($lancamento?->numero_boleto ?? '');
        $this->modalValorPlanejado = $lancamento?->valor_planejado !== null ? $this->moeda($lancamento->valor_planejado) : '';
        $this->modalValorEfetivado = $lancamento?->valor_efetivado !== null ? $this->moeda($lancamento->valor_efetivado) : '';
        $this->modalObservacao = (string) ($lancamento?->observacao ?? '');
        $this->parcelamentoDataLancamento = null;
        $this->parcelamentoValorEfetivado = '';
        $this->boletoVencimento = $this->vencimentoPadraoBoleto();
    }

    private function vencimentoPadraoBoleto(): ?string
    {
        if ($this->modalClienteId === null || $this->modalMes === null || $this->modalAno === null) {
            return now()->toDateString();
        }

        $diaPagamento = Cliente::query()
            ->whereKey($this->modalClienteId)
            ->value('dia_pagamento') ?: 1;

        $ultimoDia = CarbonImmutable::create($this->modalAno, $this->modalMes, 1)->endOfMonth()->day;
        $dia = min((int) $diaPagamento, $ultimoDia);

        return CarbonImmutable::create($this->modalAno, $this->modalMes, $dia)->toDateString();
    }

    private function valorDecimal(string $valor): ?float
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return (float) $valor;
    }

    private function lancamentosAgregados(int $mes, int $ano, Collection $clienteIds): Collection
    {
        if ($clienteIds->isEmpty()) {
            return collect();
        }

        return Lancamento::query()
            ->select('cliente_id')
            ->selectRaw('MAX(numero_boleto) as numero_boleto')
            ->selectRaw('MAX(valor_planejado) as valor_planejado')
            ->selectRaw('SUM(valor_efetivado) as valor_efetivado')
            ->selectRaw('MAX(observacao) as observacao')
            ->whereIn('cliente_id', $clienteIds)
            ->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano)
            ->groupBy('cliente_id')
            ->get()
            ->keyBy('cliente_id');
    }

    private function clientesComConsultaMensal(int $mes, int $ano, string $consulta): Collection
    {
        $search = '%'.$consulta.'%';

        return Lancamento::query()
            ->select('cliente_id')
            ->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano)
            ->groupBy('cliente_id')
            ->havingRaw(
                '(MAX(observacao) LIKE ? OR MAX(numero_boleto) LIKE ? OR CAST(SUM(valor_efetivado) AS CHAR) LIKE ?)',
                [$search, $search, $search],
            )
            ->pluck('cliente_id');
    }

    private function clientesComNumeroBoleto(int $mes, int $ano): Collection
    {
        return Lancamento::query()
            ->select('cliente_id')
            ->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano)
            ->groupBy('cliente_id')
            ->havingRaw("(MAX(numero_boleto) IS NOT NULL AND MAX(numero_boleto) <> '')")
            ->pluck('cliente_id');
    }

    private function clientesComValorEfetuado(int $mes, int $ano): Collection
    {
        return Lancamento::query()
            ->select('cliente_id')
            ->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano)
            ->groupBy('cliente_id')
            ->havingRaw('(SUM(valor_efetivado) IS NOT NULL AND SUM(valor_efetivado) <> 0)')
            ->pluck('cliente_id');
    }

    private function totalEfetivadoReferencia(int $mes, int $ano): float
    {
        return (float) Lancamento::query()
            ->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano)
            ->sum('valor_efetivado');
    }

    private function sincronizarBuscaCompartilhada(string $busca): void
    {
        $busca = trim($busca);

        if ($busca === '') {
            session()->forget(self::BUSCA_COMPARTILHADA_SESSION);

            return;
        }

        session()->put(self::BUSCA_COMPARTILHADA_SESSION, $busca);
    }

    private function statusClienteCompartilhado(): ?int
    {
        if (session()->exists(self::STATUS_CLIENTE_COMPARTILHADO_SESSION)) {
            $statusId = session(self::STATUS_CLIENTE_COMPARTILHADO_SESSION);

            return $statusId ? (int) $statusId : null;
        }

        return $this->statusAtivoId();
    }

    private function sincronizarStatusClienteCompartilhado(?int $statusId): void
    {
        session()->put(self::STATUS_CLIENTE_COMPARTILHADO_SESSION, $statusId ?: null);
    }

    private function limparFiltrosMensais(): void
    {
        $this->limparFiltrosMes1();
        $this->limparFiltrosMes2();
    }

    private function limparFiltrosMes1(): void
    {
        $this->consultaMes1 = '';
        $this->numeroBoletoMes1 = 2;
        $this->valorEfetuadoMes1 = 2;
    }

    private function limparFiltrosMes2(): void
    {
        $this->consultaMes2 = '';
        $this->numeroBoletoMes2 = 2;
        $this->valorEfetuadoMes2 = 2;
    }

    private function proximoEstadoFiltro(int $estadoAtual): int
    {
        return match ($estadoAtual) {
            2 => 1,
            1 => 0,
            default => 2,
        };
    }

    private function mesAtual(): CarbonImmutable
    {
        return CarbonImmutable::create($this->anoBase, $this->mesBase, 1);
    }

    private function statusAtivoId(): ?int
    {
        return StatusCliente::query()
            ->where('label', 'Ativo')
            ->value('id');
    }
}
