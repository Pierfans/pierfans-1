<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Webhook do SuitPay (necessário para receber notificações do gateway)
Route::post('/webhook/suitpay', [App\Http\Controllers\SuitPayWebhookController::class, 'handle'])->name('api.webhook.suitpay');

// API para consultar usuários associados a um afiliado específico
// Exclusivo para o slug "ZjOMZKiHDT"
Route::post('/affiliate/users', [App\Http\Controllers\Api\AffiliateTrackingController::class, 'getAffiliateUsers'])->name('api.affiliate.users');
