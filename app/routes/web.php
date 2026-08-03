<?php

use App\Http\Controllers\ContratoDocumentoController;
use App\Http\Controllers\OrdemServicoTecnicoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');
Route::redirect('/login', '/admin/login');

Route::get('/os/{token}', [OrdemServicoTecnicoController::class, 'show'])->name('ordens-servico.tecnico');
Route::post('/os/{token}', [OrdemServicoTecnicoController::class, 'action'])->name('ordens-servico.tecnico.action');
Route::get('/os/{token}/fotos/{foto}', [OrdemServicoTecnicoController::class, 'foto'])->name('ordens-servico.tecnico.foto');

Route::middleware('auth')->group(function (): void {
    Route::get('/admin/contratos/{contrato}/documento', ContratoDocumentoController::class)
        ->name('contratos.documento');
});
