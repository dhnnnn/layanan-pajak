<?php

use App\Http\Controllers\Api\V1\PaymentCheckController;
use App\Http\Controllers\Api\V1\TaxSummaryController;
use App\Http\Controllers\Api\V1\TaxTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('api_token')->prefix('v1')->group(function () {
    // GET /api/v1/tax-summary?year=2026
    Route::get('/tax-summary', TaxSummaryController::class);

    // GET /api/v1/tax-types
    Route::get('/tax-types', [TaxTypeController::class, 'index']);

    // GET /api/v1/tax-realization?year=2026
    Route::get('/tax-realization', [TaxTypeController::class, 'realization']);

    // POST /api/v1/payment-check/{jenis_pajak}
    Route::post('/payment-check/hotel', [PaymentCheckController::class, 'hotel']);
    Route::post('/payment-check/restoran', [PaymentCheckController::class, 'restoran']);
    Route::post('/payment-check/hiburan', [PaymentCheckController::class, 'hiburan']);
    Route::post('/payment-check/reklame', [PaymentCheckController::class, 'reklame']);
    Route::post('/payment-check/ppj', [PaymentCheckController::class, 'ppj']);
    Route::post('/payment-check/parkir', [PaymentCheckController::class, 'parkir']);
    Route::post('/payment-check/at', [PaymentCheckController::class, 'at']);
    Route::post('/payment-check/minerba', [PaymentCheckController::class, 'minerba']);
    Route::post('/payment-check/bphtb', [PaymentCheckController::class, 'bphtb']);
});
