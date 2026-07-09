<?php

namespace App\Filament\Widgets;

use App\Models\DashboardAnotacao;
use Filament\Widgets\Widget;

class PainelControleAnotacoes extends Widget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.painel-controle-anotacoes';

    public string $conteudo = '';

    public ?string $salvoEm = null;

    public function mount(): void
    {
        $anotacao = DashboardAnotacao::query()
            ->where('user_id', auth()->id())
            ->first();

        $this->conteudo = (string) ($anotacao?->conteudo ?? '');
        $this->salvoEm = $this->formatarSalvoEm($anotacao?->updated_at);
    }

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function updatedConteudo(): void
    {
        $anotacao = DashboardAnotacao::query()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['conteudo' => $this->conteudo],
        );

        $this->salvoEm = $this->formatarSalvoEm($anotacao->updated_at);
    }

    private function formatarSalvoEm(mixed $data): ?string
    {
        return $data ? $data->timezone(config('app.timezone'))->format('H:i') : null;
    }
}
