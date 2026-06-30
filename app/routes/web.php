<?php

use App\Http\Controllers\ContratosRastreadorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/admin/contratos-rastreador/{veiculo}', [ContratosRastreadorController::class, 'show'])->name('contratos-rastreador.show');
    Route::post('/admin/contratos-rastreador/{veiculo}/enviar', [ContratosRastreadorController::class, 'enviar'])->name('contratos-rastreador.enviar');
});