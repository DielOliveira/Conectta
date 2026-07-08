<?php

namespace App\Filament\Pages;

use App\Models\Faturamento as FaturamentoModel;
use App\Models\Lancamento;
use App\Models\Permission;
use App\Services\Audit\AuditLogger;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class Faturamento extends Page
{
    private const ANOS_PERMITIDOS = [2023, 2024, 2025, 2026, 2027];

    protected static ?string $slug = 'faturamento';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 4;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Faturamento';

    protected static ?string $title = 'Faturamento';

    protected string $view = 'filament.pages.faturamento';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::FATURAMENTO_LEITURA) ?? false;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => 'Financeiro',
            self::getUrl() => 'Faturamento',
        ];
    }

    public int $ano;

    public string $graficoSelecionado = 'mensal';

    public int $mesComparativo;

    public function mount(): void
    {
        $this->ano = (int) now()->year;
        $this->mesComparativo = (int) now()->month;
    }

    public function alternarAberto(int $mes): void
    {
        if (! auth()->user()?->hasPermission(Permission::FATURAMENTO_ESCRITA)) {
            \Filament\Notifications\Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $faturamento = FaturamentoModel::query()->firstOrCreate(
            [
                'ano' => $this->ano,
                'mes' => $mes,
            ],
            [
                'is_aberto' => false,
            ],
        );

        $novoStatus = ! $faturamento->is_aberto;
        $antes = AuditLogger::snapshot($faturamento);

        FaturamentoModel::query()
            ->where('ano', $this->ano)
            ->update(['is_aberto' => false]);

        $faturamento->update(['is_aberto' => $novoStatus]);
        $faturamento->refresh();

        AuditLogger::registrar(
            $novoStatus ? 'faturamento.mes_aberto' : 'faturamento.mes_fechado',
            $novoStatus ? 'Mes de faturamento aberto.' : 'Mes de faturamento fechado.',
            $faturamento,
            antes: $antes,
            depois: AuditLogger::snapshot($faturamento),
            contexto: [
                'ano' => $this->ano,
                'mes' => $mes,
            ],
        );

        \Filament\Notifications\Notification::make()
            ->title($novoStatus ? 'Mes marcado como aberto.' : 'Mes fechado.')
            ->success()
            ->send();
    }

    public function anosDisponiveis(): array
    {
        return self::ANOS_PERMITIDOS;
    }

    public function graficosDisponiveis(): array
    {
        return [
            'mensal' => 'Faturamento mensal',
            'panorama' => 'Panorama anual',
            'comparativo_mes' => 'Comparativo por mes',
        ];
    }

    public function linhasFaturamento(): Collection
    {
        $totaisPlanejados = $this->totaisPlanejados();
        $totaisLancados = $this->totaisLancados();
        $totaisRecebidos = $this->totaisRecebidos();
        $abertos = FaturamentoModel::query()
            ->where('ano', $this->ano)
            ->pluck('is_aberto', 'mes');

        return collect(range(1, 12))
            ->map(fn (int $mes): array => [
                'mes' => $mes,
                'nome' => $this->mesNome($mes),
                'is_aberto' => (bool) ($abertos[$mes] ?? false),
                'total_planejado' => (float) ($totaisPlanejados[$mes] ?? 0),
                'total_lancado' => (float) ($totaisLancados[$mes] ?? 0),
                'total_recebido' => (float) ($totaisRecebidos[$mes] ?? 0),
            ]);
    }

    public function totalPlanejadoAno(): float
    {
        return (float) $this->linhasFaturamento()->sum('total_planejado');
    }

    public function totalLancadoAno(): float
    {
        return (float) $this->linhasFaturamento()->sum('total_lancado');
    }

    public function totalRecebidoAno(): float
    {
        return (float) $this->linhasFaturamento()->sum('total_recebido');
    }

    public function panoramaAnual(): Collection
    {
        $totaisPlanejados = Lancamento::query()
            ->select('ano_referencia', DB::raw('sum(valor_planejado) as total'))
            ->whereIn('ano_referencia', self::ANOS_PERMITIDOS)
            ->whereNotNull('cliente_id')
            ->groupBy('ano_referencia')
            ->pluck('total', 'ano_referencia');

        $totaisLancados = Lancamento::query()
            ->select('ano_referencia', DB::raw('sum(valor_efetivado) as total'))
            ->whereIn('ano_referencia', self::ANOS_PERMITIDOS)
            ->whereNotNull('cliente_id')
            ->groupBy('ano_referencia')
            ->pluck('total', 'ano_referencia');

        $totaisRecebidos = Lancamento::query()
            ->selectRaw('year(data_lancamento) as ano, sum(valor_efetivado) as total')
            ->whereNotNull('data_lancamento')
            ->groupByRaw('year(data_lancamento)')
            ->pluck('total', 'ano');

        return collect($this->anosDisponiveis())
            ->sort()
            ->values()
            ->map(fn (int $ano): array => [
                'ano' => $ano,
                'total_planejado' => (float) ($totaisPlanejados[$ano] ?? 0),
                'total_lancado' => (float) ($totaisLancados[$ano] ?? 0),
                'total_recebido' => (float) ($totaisRecebidos[$ano] ?? 0),
            ]);
    }

    public function comparativoMensal(): Collection
    {
        $totaisRecebidos = Lancamento::query()
            ->selectRaw('year(data_lancamento) as ano, sum(valor_efetivado) as total')
            ->whereNotNull('data_lancamento')
            ->whereMonth('data_lancamento', $this->mesComparativo)
            ->whereIn(DB::raw('year(data_lancamento)'), self::ANOS_PERMITIDOS)
            ->groupByRaw('year(data_lancamento)')
            ->pluck('total', 'ano');

        return collect($this->anosDisponiveis())
            ->sort()
            ->values()
            ->map(fn (int $ano): array => [
                'ano' => $ano,
                'total_recebido' => (float) ($totaisRecebidos[$ano] ?? 0),
            ]);
    }

    public function moeda(float|int|string|null $valor): string
    {
        return 'R$'.number_format((float) $valor, 2, ',', '.');
    }

    public function mesNome(int $mes): string
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
        ][$mes] ?? (string) $mes;
    }

    private function totaisLancados(): Collection
    {
        return Lancamento::query()
            ->select('mes_referencia', DB::raw('sum(valor_efetivado) as total'))
            ->where('ano_referencia', $this->ano)
            ->whereNotNull('cliente_id')
            ->groupBy('mes_referencia')
            ->pluck('total', 'mes_referencia');
    }

    private function totaisPlanejados(): Collection
    {
        return Lancamento::query()
            ->select('mes_referencia', DB::raw('sum(valor_planejado) as total'))
            ->where('ano_referencia', $this->ano)
            ->whereNotNull('cliente_id')
            ->groupBy('mes_referencia')
            ->pluck('total', 'mes_referencia');
    }

    private function totaisRecebidos(): Collection
    {
        return Lancamento::query()
            ->selectRaw('month(data_lancamento) as mes, sum(valor_efetivado) as total')
            ->whereYear('data_lancamento', $this->ano)
            ->groupByRaw('month(data_lancamento)')
            ->pluck('total', 'mes');
    }
}
