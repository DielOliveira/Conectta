<?php

namespace App\Filament\Widgets;

use App\Models\Permission;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PainelControleResumo extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected ?string $heading = 'Resumo operacional';

    protected int|array|null $columns = ['@xl' => 4, '!@lg' => 2, '!@md' => 1];

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) $user?->canAnyPermission([
            Permission::CADASTRO_LEITURA,
            Permission::FINANCEIRO_LEITURA,
            Permission::BOLETOS_LEITURA,
            Permission::ESTOQUE_LEITURA,
        ]);
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if ($user?->hasPermission(Permission::CADASTRO_LEITURA)) {
            $ativoId = $this->idPorLabel('status_clientes', 'Ativo');
            $inativoId = $this->idPorLabel('status_clientes', 'Inativo');
            $rastreadorAtivoId = $this->idPorLabel('status_rastreadores', 'Ativo');

            $stats[] = Stat::make('Clientes ativos', $this->numero(DB::table('clientes')->where('status_cliente_id', $ativoId)->whereNull('data_exclusao')->count()))
                ->description('Clientes em operacao')
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedUsers)
                ->url('/admin/clientes');

            $stats[] = Stat::make('Clientes inativos', $this->numero(DB::table('clientes')->where('status_cliente_id', $inativoId)->whereNull('data_exclusao')->count()))
                ->description('Sem veiculo ativo')
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedUserMinus)
                ->url('/admin/clientes');

            $stats[] = Stat::make('Rastreadores ativos', $this->numero(DB::table('veiculos')->where('status_rastreador_id', $rastreadorAtivoId)->whereNull('data_exclusao')->count()))
                ->description('Veiculos com rastreador ativo')
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedTruck)
                ->url('/admin/rastreadores');
        }

        if ($user?->hasPermission(Permission::FINANCEIRO_LEITURA)) {
            $mes = (int) now()->month;
            $ano = (int) now()->year;
            $planejado = (float) DB::table('lancamentos')->where('mes_referencia', $mes)->where('ano_referencia', $ano)->sum('valor_planejado');
            $efetivadoReferencia = (float) DB::table('lancamentos')->where('mes_referencia', $mes)->where('ano_referencia', $ano)->sum('valor_efetivado');
            $recebidoMes = (float) DB::table('lancamentos')->whereYear('data_lancamento', $ano)->whereMonth('data_lancamento', $mes)->sum('valor_efetivado');

            $stats[] = Stat::make('Planejado no mes', $this->moeda($planejado))
                ->description('Referencia ' . now()->format('m/Y'))
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url('/admin/financeiro');

            $stats[] = Stat::make('Recebido no mes', $this->moeda($recebidoMes))
                ->description('Por data de lancamento')
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedBanknotes)
                ->url('/admin/financeiro');

            $stats[] = Stat::make('Aberto no mes', $this->moeda(max(0, $planejado - $efetivadoReferencia)))
                ->description('Planejado menos efetivado')
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedExclamationCircle)
                ->url('/admin/relatorio-geral');
        }

        if ($user?->hasPermission(Permission::BOLETOS_LEITURA)) {
            $stats[] = Stat::make('Boletos atrasados', $this->numero($this->boletosAtrasadosQuery()->count()))
                ->description('Pendencias da Lytex')
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedDocumentText)
                ->url('/admin/boletos');
        }

        if ($user?->hasPermission(Permission::ESTOQUE_LEITURA)) {
            $disponivelId = $this->idPorLabel('status_rastreadores', 'Disponivel');
            $lixoId = $this->idPorLabel('status_rastreadores', 'Lixo');

            $stats[] = Stat::make('Estoque disponivel', $this->numero(DB::table('rastreadores')->where('status_rastreador_id', $disponivelId)->count()))
                ->description('Rastreadores prontos para uso')
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->url('/admin/estoque-rastreadores');

            $stats[] = Stat::make('Rastreadores em lixo', $this->numero(DB::table('rastreadores')->where('status_rastreador_id', $lixoId)->count()))
                ->description('Itens fora de operacao')
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedTrash)
                ->url('/admin/estoque-rastreadores');
        }

        return $stats;
    }

    private function idPorLabel(string $tabela, string $label): ?int
    {
        $id = DB::table($tabela)->where('label', $label)->value('id');

        return $id === null ? null : (int) $id;
    }

    private function boletosAtrasadosQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('invoices')
            ->where(function ($query): void {
                $query->whereRaw('lower(status) = ?', ['atrasado'])
                    ->orWhereRaw('lower(status) = ?', ['overdue']);
            });
    }

    private function moeda(float $valor): string
    {
        return 'R$' . number_format($valor, 2, ',', '.');
    }

    private function numero(int $valor): string
    {
        return number_format($valor, 0, ',', '.');
    }
}
