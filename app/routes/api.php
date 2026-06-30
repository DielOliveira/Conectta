<?php

use App\Http\Controllers\LytexWebhookController;
use App\Http\Controllers\ZapSignWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/lytex', LytexWebhookController::class)->name('webhooks.lytex');
Route::post('/webhooks/zapsign', ZapSignWebhookController::class)->name('webhooks.zapsign');
