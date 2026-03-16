<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ImportController as AdminImportController;
use App\Http\Controllers\Admin\TaxTargetController;
use App\Http\Controllers\Admin\TaxTypeController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\ImportController as EmployeeImportController;
use App\Http\Controllers\Employee\RealizationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'show'])->name('dashboard');

        // Jenis Pajak
        Route::resource('tax-types', TaxTypeController::class);

        // Kecamatan
        Route::resource('districts', DistrictController::class)->except([
            'show',
        ]);

        // Pegawai
        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/districts', [EmployeeController::class, 'assignDistricts'])->name('employees.districts.assign');

        // Target Pajak (APBD)
        Route::resource(
            'tax-targets',
            TaxTargetController::class,
        )->except(['show']);

        // Import Realisasi Pajak
        Route::prefix('import')
            ->name('import.')
            ->group(function (): void {
                Route::get('/', [AdminImportController::class, 'index'])->name('index');
                Route::post('/preview', [AdminImportController::class, 'preview'])->name('preview');
                Route::post('/confirm', [AdminImportController::class, 'confirm'])->name('confirm');
            });

        // Download Template
        Route::get('/template/download', [TemplateController::class, 'download'])->name('template.download');
    });

/*
|--------------------------------------------------------------------------
| Employee (Pegawai) Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pegawai'])
    ->prefix('pegawai')
    ->name('pegawai.')
    ->group(function (): void {
        // Dashboard
        Route::get('/dashboard', [EmployeeDashboardController::class, 'show'])->name('dashboard');

        // Realisasi Pajak
        Route::resource(
            'realizations',
            RealizationController::class,
        )->except(['destroy']);

        // Import Realisasi Pajak
        Route::prefix('import')
            ->name('import.')
            ->group(function (): void {
                Route::get('/', [EmployeeImportController::class, 'index'])->name('index');
                Route::post('/preview', [EmployeeImportController::class, 'preview'])->name('preview');
                Route::post('/confirm', [EmployeeImportController::class, 'confirm'])->name('confirm');
            });

        // Download Template
        Route::get('/template/download', [TemplateController::class, 'download'])->name('template.download');
    });
