<?php

namespace App\Filament\Widgets;

use App\Models\Permission;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PainelControleAtencao extends Widget
{
    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.painel-controle-atencao';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) $user?->canAnyPermission([
            Permission::CADASTRO_LEITURA,
            Permission::BOLETOS_LEITURA,
        ]);
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $items = [];

        if ($user?->hasPermission(Permission::BOLETOS_LEITURA)) {
            $atrasados = DB::table('invoices')
                ->leftJoin('lancamentos', 'lancamentos.id', '=', 'invoices.lancamento_id')
                ->leftJoin('clientes', 'clientes.id', '=', 'lancamentos.cliente_id')
                ->where(function ($query): void {
                    $query->whereRaw('lower(invoices.status) = ?', ['atrasado'])
                        ->orWhereRaw('lower(invoices.status) = ?', ['overdue']);
                })
                ->orderBy('invoices.vencimento')
                ->limit(5)
                ->get(['clientes.nome', 'invoices.total_value', 'invoices.vencimento', 'invoices.status']);

            $items[] = [
                'titulo' => 'Boletos atrasados',
                'total' => DB::table('invoices')->whereRaw('lower(status) in (?, ?)', ['atrasado', 'overdue'])->count(),
                'url' => '/admin/boletos',
                'tipo' => 'danger',
                'linhas' => $atrasados->map(fn ($boleto): string => trim(($boleto->nome ?: 'Cliente nao identificado') . ' - ' . $this->moeda((float) $boleto->total_value) . ' - vence ' . $this->data($boleto->vencimento)))->all(),
            ];
        }

        if ($user?->hasPermission(Permission::CADASTRO_LEITURA)) {
            $rastreadorAtivoId = DB::table('status_rastreadores')->where('label', 'Ativo')->value('id');
            $clienteAtivoId = DB::table('status_clientes')->where('label', 'Ativo')->value('id');
            $contratoEnviadoId = DB::table('status_contratos')->where('label', 'Enviado')->value('id');
            $contratoAssinadoId = DB::table('status_contratos')->where('label', 'Assinado')->value('id');

            $semVeiculoAtivo = DB::table('clientes')
                ->where('status_cliente_id', $clienteAtivoId)
                ->whereNull('data_exclusao')
                ->whereNotExists(function ($query) use ($rastreadorAtivoId): void {
                    $query->selectRaw('1')
                        ->from('veiculos')
                        ->whereColumn('veiculos.cliente_id', 'clientes.id')
                        ->where('veiculos.status_rastreador_id', $rastreadorAtivoId)
                        ->whereNull('veiculos.data_exclusao');
                })
                ->count();

            $cpfTecnico = DB::table('clientes')
                ->where(function ($query): void {
                    $query->where('cpf_cnpj', 'like', '%-DUP-%')
                        ->orWhere('cpf_cnpj', 'like', 'IMPORTADO-%');
                })
                ->count();

            $contatoIncompleto = DB::table('clientes')
                ->whereNull('data_exclusao')
                ->where(function ($query): void {
                    $query->whereNull('email')
                        ->orWhere('email', '')
                        ->orWhereNull('telefone1')
                        ->orWhere('telefone1', '')
                        ->orWhere('telefone1', '00000000000');
                })
                ->count();

            $contratosPendentes = DB::table('contratos')
                ->where('status_contrato_id', $contratoEnviadoId)
                ->count();

            $contratosAssinados = DB::table('contratos')
                ->where('status_contrato_id', $contratoAssinadoId)
                ->count();

            $items[] = [
                'titulo' => 'Clientes ativos sem veiculo ativo',
                'total' => $semVeiculoAtivo,
                'url' => '/admin/clientes',
                'tipo' => $semVeiculoAtivo > 0 ? 'warning' : 'success',
                'linhas' => ['Valida clientes marcados como ativos, mas sem rastreador ativo vinculado.'],
            ];

            $items[] = [
                'titulo' => 'CPFs/CNPJs tecnicos do restore',
                'total' => $cpfTecnico,
                'url' => '/admin/clientes',
                'tipo' => $cpfTecnico > 0 ? 'warning' : 'success',
                'linhas' => ['Inclui registros com sufixo DUP ou IMPORTADO para revisar depois.'],
            ];

            $items[] = [
                'titulo' => 'Clientes com contato incompleto',
                'total' => $contatoIncompleto,
                'url' => '/admin/clientes',
                'tipo' => $contatoIncompleto > 0 ? 'warning' : 'success',
                'linhas' => ['Clientes sem email ou telefone celular valido.'],
            ];

            $items[] = [
                'titulo' => 'Contratos enviados',
                'total' => $contratosPendentes,
                'url' => '/admin/rastreadores',
                'tipo' => $contratosPendentes > 0 ? 'warning' : 'success',
                'linhas' => ['Contratos enviados ainda sem status Assinado.', 'Assinados no historico: ' . number_format($contratosAssinados, 0, ',', '.')],
            ];
        }

        return ['items' => $items];
    }

    private function moeda(float $valor): string
    {
        return 'R$' . number_format($valor, 2, ',', '.');
    }

    private function data(mixed $data): string
    {
        if (blank($data)) {
            return '-';
        }

        return \Carbon\Carbon::parse($data)->format('d/m/Y');
    }
}
