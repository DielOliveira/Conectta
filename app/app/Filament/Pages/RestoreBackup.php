<?php

namespace App\Filament\Pages;

use App\Models\Permission;
use App\Services\Audit\AuditLogger;
use App\Services\Backup\BackupRestoreService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class RestoreBackup extends Page
{
    use WithFileUploads;

    protected static ?string $slug = 'restore-backup';

    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?int $navigationSort = 9;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Restore Backup';

    protected static ?string $title = 'Restore Backup';

    protected string $view = 'filament.pages.restore-backup';

    public mixed $clientes = null;

    public mixed $veiculos = null;

    public mixed $rastreadores = null;

    public mixed $tecnicos = null;

    public mixed $vendedores = null;

    public mixed $lancamentos = null;

    public mixed $invoices = null;

    public mixed $faturamentos = null;

    public bool $confirmarLimpeza = false;

    /** @var array<string, mixed>|null */
    public ?array $resultado = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(Permission::TECNICO) ?? false;
    }

    public function restaurar(BackupRestoreService $service): void
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);

        if (! auth()->user()?->hasPermission(Permission::TECNICO)) {
            Notification::make()->title('Voce nao tem permissao para esta acao.')->danger()->send();

            return;
        }

        $this->validate([
            'clientes' => ['required', 'file', 'mimes:xlsx'],
            'veiculos' => ['required', 'file', 'mimes:xlsx'],
            'rastreadores' => ['required', 'file', 'mimes:xlsx'],
            'tecnicos' => ['required', 'file', 'mimes:xlsx'],
            'vendedores' => ['required', 'file', 'mimes:xlsx'],
            'lancamentos' => ['required', 'file', 'mimes:xlsx'],
            'invoices' => ['required', 'file', 'mimes:xlsx'],
            'faturamentos' => ['required', 'file', 'mimes:xlsx'],
            'confirmarLimpeza' => ['accepted'],
        ], [
            'required' => 'O arquivo :attribute e obrigatorio.',
            'mimes' => 'O arquivo :attribute deve ser XLSX.',
            'confirmarLimpeza.accepted' => 'Confirme que entende que a base operacional sera limpa antes do restore.',
        ], [
            'clientes' => 'Cliente.xlsx',
            'veiculos' => 'Veiculos.xlsx',
            'rastreadores' => 'Rastreador.xlsx',
            'tecnicos' => 'Tecnico.xlsx',
            'vendedores' => 'Vendedores.xlsx',
            'lancamentos' => 'Lancamento.xlsx',
            'invoices' => 'Invoice.xlsx',
            'faturamentos' => 'Faturamento.xlsx',
        ]);

        try {
            $this->resultado = $service->restore([
                'clientes' => $this->path($this->clientes),
                'veiculos' => $this->path($this->veiculos),
                'rastreadores' => $this->path($this->rastreadores),
                'tecnicos' => $this->path($this->tecnicos),
                'vendedores' => $this->path($this->vendedores),
                'lancamentos' => $this->path($this->lancamentos),
                'invoices' => $this->path($this->invoices),
                'faturamentos' => $this->path($this->faturamentos),
            ]);

            AuditLogger::registrar(
                acao: 'backup.restore',
                descricao: 'Restore de backup executado.',
                contexto: [
                    'limpar_base' => true,
                    'resultado' => $this->resultado,
                    'arquivos' => $this->nomesArquivos(),
                ],
            );

            Notification::make()
                ->title('Restore concluido.')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            Log::error('Erro ao restaurar backup', ['exception' => $exception]);

            Notification::make()
                ->title('Erro ao restaurar backup')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function path(mixed $file): string
    {
        if (! $file instanceof TemporaryUploadedFile) {
            return '';
        }

        return $file->getRealPath();
    }

    /**
     * @return array<string, string|null>
     */
    private function nomesArquivos(): array
    {
        return [
            'clientes' => $this->nomeArquivo($this->clientes),
            'veiculos' => $this->nomeArquivo($this->veiculos),
            'rastreadores' => $this->nomeArquivo($this->rastreadores),
            'tecnicos' => $this->nomeArquivo($this->tecnicos),
            'vendedores' => $this->nomeArquivo($this->vendedores),
            'lancamentos' => $this->nomeArquivo($this->lancamentos),
            'invoices' => $this->nomeArquivo($this->invoices),
            'faturamentos' => $this->nomeArquivo($this->faturamentos),
        ];
    }

    private function nomeArquivo(mixed $file): ?string
    {
        if (! $file instanceof TemporaryUploadedFile) {
            return null;
        }

        return $file->getClientOriginalName();
    }
}
