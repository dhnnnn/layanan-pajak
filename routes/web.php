<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ImportController as AdminImportController;
use App\Http\Controllers\Admin\TaxTargetController;
use App\Http\Controllers\Admin\TaxTypeController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\UptComparisonController;
use App\Http\Controllers\Admin\UptController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\DailyEntryController;
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

        // UPT
        Route::resource('upts', UptController::class);
        Route::get('upts/{upt}/districts', [UptController::class, 'assignDistricts'])->name('upts.districts');
        Route::post('upts/{upt}/districts', [UptController::class, 'storeDistricts'])->name('upts.districts.store');
        Route::get('upts/{upt}/employees', [UptController::class, 'manageEmployees'])->name('upts.employees.manage');
        Route::post('upts/{upt}/employees', [UptController::class, 'storeEmployees'])->name('upts.employees.store');
        Route::get('upts/{upt}/employees/{employee}/districts', [UptController::class, 'assignEmployeeDistricts'])->name('upts.employees.districts');

        // Perbandingan Target UPT
        Route::prefix('upt-comparisons')
            ->name('upt-comparisons.')
            ->group(function (): void {
                Route::get('/', [UptComparisonController::class, 'index'])->name('index');
                Route::post('/preview', [UptComparisonController::class, 'preview'])->name('preview');
                Route::post('/import', [UptComparisonController::class, 'import'])->name('import');
                Route::get('/report', [UptComparisonController::class, 'report'])->name('report');
            });

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
        Route::get('/template', [TemplateController::class, 'index'])->name('template.index');
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

        // Realisasi Pajak - Daily Entries
        Route::get('districts/{districtId}/entries', [DailyEntryController::class, 'show'])->name('daily-entries.show');
        Route::get('daily-entries', [DailyEntryController::class, 'index'])->name('daily-entries.index');
        Route::post('daily-entries', [DailyEntryController::class, 'store'])->name('daily-entries.store');
        Route::delete('daily-entries/{dailyEntry}', [DailyEntryController::class, 'destroy'])->name('daily-entries.destroy');

        // Realisasi Pajak
        Route::get('realizations/district/{districtId}/tax-types', [RealizationController::class, 'getTaxTypesByDistrict'])->name('realizations.district.tax-types');
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
        Route::get('/template', [TemplateController::class, 'index'])->name('template.index');
        Route::get('/template/download', [TemplateController::class, 'download'])->name('template.download');
    });
