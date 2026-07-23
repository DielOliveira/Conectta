<?php

use App\Http\Controllers\ContratoDocumentoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');
Route::redirect('/login', '/admin/login');

Route::middleware('auth')->group(function (): void {
    Route::get('/admin/contratos/{contrato}/documento', ContratoDocumentoController::class)
        ->name('contratos.documento');
});
