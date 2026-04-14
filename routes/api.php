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
    Route::post('/payment-check/{jenis_pajak}', PaymentCheckController::class)
        ->where('jenis_pajak', 'hotel|restoran|hiburan|reklame|ppj|parkir|at|minerba|bphtb');
});
