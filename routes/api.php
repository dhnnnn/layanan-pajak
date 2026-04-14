<?php

use App\Http\Controllers\Api\TaxSummaryController;
use App\Http\Controllers\Api\TaxTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('api_token')->group(function () {
    // GET /api/tax-summary?year=2026  — total target & realisasi per tahun
    Route::get('/tax-summary', TaxSummaryController::class);

    // GET /api/tax-types  — daftar semua jenis pajak
    Route::get('/tax-types', [TaxTypeController::class, 'index']);

    // GET /api/tax-realization?year=2026  — realisasi per jenis pajak
    Route::get('/tax-realization', [TaxTypeController::class, 'realization']);
});
