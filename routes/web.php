<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\RealizationMonitoringController;
use App\Http\Controllers\Admin\TaxPayerMonitoringController;
use App\Http\Controllers\Admin\TaxTargetController;
use App\Http\Controllers\Admin\TaxTypeController;
use App\Http\Controllers\Admin\UptController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\DailyEntryController;
use App\Http\Controllers\Employee\RealizationController;
use App\Http\Controllers\FieldOfficer\DashboardController as FieldOfficerController;
use App\Http\Controllers\ProfileController;
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

// Profile — semua role yang sudah login
Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|kepala_upt'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'show'])->name('dashboard');

        // Jenis Pajak
        Route::resource('tax-types', TaxTypeController::class);
        Route::post('tax-types/{tax_type}/subtypes', [TaxTypeController::class, 'storeSubtype'])->name('tax-types.subtypes.store');
        Route::patch('tax-types/{tax_type}/subtypes/{subtype}', [TaxTypeController::class, 'updateSubtype'])->name('tax-types.subtypes.update');

        // Kecamatan
        Route::resource('districts', DistrictController::class)->except([
            'show',
        ]);

        // Pegawai
        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/districts', [EmployeeController::class, 'assignDistricts'])->name('employees.districts.assign');
        Route::get('employees/{employee}/wp-tunggakan', [EmployeeController::class, 'wpTunggakan'])->name('employees.wp-tunggakan');

        // UPT
        Route::resource('upts', UptController::class);
        Route::get('upts/{upt}/districts', [UptController::class, 'assignDistricts'])->name('upts.districts');
        Route::post('upts/{upt}/districts', [UptController::class, 'storeDistricts'])->name('upts.districts.store');
        Route::get('upts/{upt}/employees', [UptController::class, 'manageEmployees'])->name('upts.employees.manage');
        Route::post('upts/{upt}/employees', [UptController::class, 'storeEmployees'])->name('upts.employees.store');
        Route::get('upts/{upt}/employees/{employee}/districts', [UptController::class, 'assignEmployeeDistricts'])->name('upts.employees.districts');

        // Monitoring Realisasi
        Route::get('realization-monitoring/export', [RealizationMonitoringController::class, 'exportAll'])->name('realization-monitoring.export-all')->middleware('role:admin');
        Route::get('realization-monitoring', [RealizationMonitoringController::class, 'index'])->name('realization-monitoring.index');
        Route::get('realization-monitoring/{upt}', [RealizationMonitoringController::class, 'show'])->name('realization-monitoring.show');
        Route::get('realization-monitoring/{upt}/export', [RealizationMonitoringController::class, 'export'])->name('realization-monitoring.export');
        Route::get('realization-monitoring/{upt}/export-pdf', [RealizationMonitoringController::class, 'exportUptPdf'])->name('realization-monitoring.export-pdf');
        Route::get('realization-monitoring/{upt}/employee/{employee}', [RealizationMonitoringController::class, 'employeeDetail'])->name('realization-monitoring.employee');
        Route::get('realization-monitoring/{upt}/employee/{employee}/wp-tunggakan', [RealizationMonitoringController::class, 'wpTunggakan'])->name('realization-monitoring.wp-tunggakan');
        Route::get('realization-monitoring/{upt}/employee/{employee}/export-excel', [RealizationMonitoringController::class, 'exportEmployee'])->name('realization-monitoring.employee.export-excel');
        Route::get('realization-monitoring/{upt}/employee/{employee}/export-pdf', [RealizationMonitoringController::class, 'exportEmployeePdf'])->name('realization-monitoring.employee.export-pdf');

        // Target Pajak (APBD)
        Route::get('tax-targets/report', [TaxTargetController::class, 'report'])->name('tax-targets.report');
        Route::get('tax-targets/export', [TaxTargetController::class, 'export'])->name('tax-targets.export');
        Route::get('tax-targets/{taxType}/show', [TaxTargetController::class, 'show'])->name('tax-targets.show');

        // Monitoring WP & Penugasan
        Route::prefix('monitoring')
            ->name('monitoring.')
            ->group(function (): void {
                Route::get('/', [TaxPayerMonitoringController::class, 'index'])->name('index');
                Route::post('/assign', [TaxPayerMonitoringController::class, 'storeTask'])->name('assign');
            });
    });

/*
|--------------------------------------------------------------------------
| Employee (Pegawai) Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pegawai'])
    ->prefix('field-officer')
    ->name('field-officer.')
    ->group(function (): void {
        // Dashboard — field officer monitoring
        Route::get('/dashboard', [FieldOfficerController::class, 'index'])->name('dashboard');

        // Realisasi Pajak - Daily Entries
        Route::get('districts/{districtId}/entries', [DailyEntryController::class, 'show'])->name('daily-entries.show');
        Route::get('daily-entries', [DailyEntryController::class, 'index'])->name('daily-entries.index');
        Route::post('daily-entries', [DailyEntryController::class, 'store'])->name('daily-entries.store');
        Route::post('daily-entries/batch', [DailyEntryController::class, 'storeBatch'])->name('daily-entries.batch');
        Route::delete('daily-entries/{dailyEntry}', [DailyEntryController::class, 'destroy'])->name('daily-entries.destroy');

        // Realisasi Pajak
        Route::get('realizations/district/{districtId}/tax-types', [RealizationController::class, 'getTaxTypesByDistrict'])->name('realizations.district.tax-types');
        Route::resource(
            'realizations',
            RealizationController::class,
        )->except(['destroy']);

        // Monitoring Field Officer
        Route::get('monitoring', [FieldOfficerController::class, 'index'])->name('monitoring.index');
        Route::get('monitoring/assigned-districts', [FieldOfficerController::class, 'wpPerKecamatan'])->name('monitoring.assigned-districts');
        Route::get('monitoring/target-achievement', [FieldOfficerController::class, 'pencapaianTarget'])->name('monitoring.target-achievement');
        Route::get('monitoring/target-achievement/export-pdf', [FieldOfficerController::class, 'exportPdf'])->name('monitoring.export-pdf');
        Route::get('monitoring/target-achievement/export-excel', [FieldOfficerController::class, 'exportExcel'])->name('monitoring.export-excel');
        Route::get('monitoring/wp-tunggakan', [FieldOfficerController::class, 'wpTunggakan'])->name('monitoring.wp-tunggakan');

        // Pemantau WP — reuse admin controller, filtered by assigned districts
        Route::get('monitoring/tax-payers', [TaxPayerMonitoringController::class, 'fieldOfficerIndex'])->name('monitoring.tax-payers');
    });
