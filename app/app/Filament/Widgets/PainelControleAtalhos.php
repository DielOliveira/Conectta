<?php

namespace App\Filament\Widgets;

use App\Models\Permission;
use Filament\Widgets\Widget;

class PainelControleAtalhos extends Widget
{
    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.painel-controle-atalhos';

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $links = [];

        if ($user?->hasPermission(Permission::CADASTRO_ESCRITA)) {
            $links[] = ['label' => 'Novo cliente', 'url' => '/admin/clientes/create'];
            $links[] = ['label' => 'Novo rastreador', 'url' => '/admin/rastreadores/create'];
        }

        if ($user?->hasPermission(Permission::CADASTRO_LEITURA)) {
            $links[] = ['label' => 'Clientes', 'url' => '/admin/clientes'];
            $links[] = ['label' => 'Rastreadores', 'url' => '/admin/rastreadores'];
        }

        if ($user?->hasPermission(Permission::FINANCEIRO_LEITURA)) {
            $links[] = ['label' => 'Financeiro', 'url' => '/admin/financeiro'];
            $links[] = ['label' => 'Relatorio geral', 'url' => '/admin/relatorio-geral'];
        }

        if ($user?->hasPermission(Permission::BOLETOS_LEITURA)) {
            $links[] = ['label' => 'Boletos', 'url' => '/admin/boletos'];
        }

        if ($user?->hasPermission(Permission::ESTOQUE_LEITURA)) {
            $links[] = ['label' => 'Estoque de rastreadores', 'url' => '/admin/estoque-rastreadores'];
        }

        if ($user?->hasPermission(Permission::FATURAMENTO_LEITURA)) {
            $links[] = ['label' => 'Faturamento', 'url' => '/admin/faturamento'];
        }

        if ($user?->isAdmin()) {
            $links[] = ['label' => 'Integracoes', 'url' => '/admin/integracoes'];
            $links[] = ['label' => 'Restore backup', 'url' => '/admin/restore-backup'];
            $links[] = ['label' => 'Usuarios', 'url' => '/admin/usuarios'];
        }

        return ['links' => $links];
    }
}
