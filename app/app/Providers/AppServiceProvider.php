<?php

namespace App\Providers;

use App\Models\Chip;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\Rastreador;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Veiculo;
use App\Models\Vendedor;
use App\Observers\AuditDeletionObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cliente::observe(AuditDeletionObserver::class);
        Contrato::observe(AuditDeletionObserver::class);
        Veiculo::observe(AuditDeletionObserver::class);
        Rastreador::observe(AuditDeletionObserver::class);
        Chip::observe(AuditDeletionObserver::class);
        Tecnico::observe(AuditDeletionObserver::class);
        Vendedor::observe(AuditDeletionObserver::class);
        User::observe(AuditDeletionObserver::class);

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceRootUrl((string) config('app.url'));
            URL::forceScheme('https');
        }
    }
}
