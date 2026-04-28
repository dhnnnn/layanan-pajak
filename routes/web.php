<?php

use App\Http\Controllers\Admin\AccessMonitoringController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DistrictAdditionalTargetController;
use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ForecastingController;
use App\Http\Controllers\Admin\MapsDiscoveryController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RbacUserController;
use App\Http\Controllers\Admin\RealizationMonitoringController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TaxPayerMonitoringController;
use App\Http\Controllers\Admin\TaxTargetController;
use App\Http\Controllers\Admin\TaxTypeController;
use App\Http\Controllers\Admin\UptAdditionalTargetController;
use App\Http\Controllers\Admin\UptController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FieldOfficer\DashboardController as FieldOfficerDashboardController;
use App\Http\Controllers\FieldOfficer\ExportController as FieldOfficerExportController;
use App\Http\Controllers\FieldOfficer\MonitoringController as FieldOfficerMonitoringController;
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

// GET logout — untuk handle sesi expired (CSRF tidak valid)
Route::get('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout.get');

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

Route::middleware(['auth', 'role:admin|kepala_upt|pemimpin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'show'])->name('dashboard');

        // Forecasting (prediksi)
        Route::get('forecasting', [ForecastingController::class, 'index'])->name('forecasting.index')->middleware('permission:view forecasting');
        Route::get('forecasting/data', [ForecastingController::class, 'data'])->name('forecasting.data')->middleware('permission:view forecasting');

        // Maps WP Discovery
        Route::get('maps-discovery', [MapsDiscoveryController::class, 'index'])->name('maps-discovery.index')->middleware('permission:manage maps-discovery');
        Route::get('maps-discovery/villages', [MapsDiscoveryController::class, 'villages'])->name('maps-discovery.villages')->middleware('permission:manage maps-discovery');
        Route::post('maps-discovery/crawl', [MapsDiscoveryController::class, 'crawl'])->name('maps-discovery.crawl')->middleware('permission:manage maps-discovery');
        Route::post('maps-discovery/sync', [MapsDiscoveryController::class, 'sync'])->name('maps-discovery.sync')->middleware('permission:manage maps-discovery');
        Route::get('maps-discovery/report', [MapsDiscoveryController::class, 'report'])->name('maps-discovery.report')->middleware('permission:view maps-discovery');
        Route::get('maps-discovery/report/{sessionId}', [MapsDiscoveryController::class, 'reportDetail'])->name('maps-discovery.report-detail')->middleware('permission:view maps-discovery');

        // Target Tambahan APBD
        Route::middleware('permission:view additional-targets')->group(function (): void {
            Route::get('upt-additional-targets', [UptAdditionalTargetController::class, 'index'])->name('upt-additional-targets.index');
        });
        Route::middleware('permission:manage additional-targets')->group(function (): void {
            Route::get('upt-additional-targets/create', [UptAdditionalTargetController::class, 'create'])->name('upt-additional-targets.create');
            Route::get('upt-additional-targets/preview', [UptAdditionalTargetController::class, 'preview'])->name('upt-additional-targets.preview');
            Route::get('upt-additional-targets/pct', [UptAdditionalTargetController::class, 'getPct'])->name('upt-additional-targets.pct');
            Route::post('upt-additional-targets', [UptAdditionalTargetController::class, 'store'])->name('upt-additional-targets.store');
            Route::delete('upt-additional-targets/{uptAdditionalTarget}', [UptAdditionalTargetController::class, 'destroy'])->name('upt-additional-targets.destroy');
        });

        // Target Tambahan Kecamatan (Global Index)
        Route::middleware('permission:view additional-targets')->group(function (): void {
            Route::get('district-additional-targets', [DistrictAdditionalTargetController::class, 'index'])->name('district-additional-targets.index');
        });

        Route::middleware('permission:manage additional-targets')->group(function (): void {
            Route::get('district-additional-targets/create', [DistrictAdditionalTargetController::class, 'create'])->name('district-additional-targets.create');
            Route::post('district-additional-targets', [DistrictAdditionalTargetController::class, 'store'])->name('district-additional-targets.store');
            Route::get('district-additional-targets/preview', [DistrictAdditionalTargetController::class, 'preview'])->name('district-additional-targets.preview');
            Route::get('district-additional-targets/pct', [DistrictAdditionalTargetController::class, 'getPct'])->name('district-additional-targets.pct');
        });

        // Laporan Realisasi
        Route::get('tax-targets/report', [TaxTargetController::class, 'report'])->name('tax-targets.report');
        Route::get('tax-targets/export', [TaxTargetController::class, 'export'])->name('tax-targets.export');
        Route::get('tax-targets/daily-realization', [TaxTargetController::class, 'dailyRealization'])->name('tax-targets.daily-realization');
        Route::get('tax-targets/weekly-realization', [TaxTargetController::class, 'weeklyRealization'])->name('tax-targets.weekly-realization');
        Route::get('tax-targets/{taxType}/show', [TaxTargetController::class, 'show'])->name('tax-targets.show');

        // Monitoring Realisasi per UPT
        Route::get('realization-monitoring/export', [RealizationMonitoringController::class, 'exportAll'])->name('realization-monitoring.export-all')->middleware('role:admin|pemimpin');
        Route::get('realization-monitoring', [RealizationMonitoringController::class, 'index'])->name('realization-monitoring.index');
        Route::get('realization-monitoring/{upt}', [RealizationMonitoringController::class, 'show'])->name('realization-monitoring.show');
        Route::get('realization-monitoring/{upt}/export', [RealizationMonitoringController::class, 'export'])->name('realization-monitoring.export');
        Route::get('realization-monitoring/{upt}/export-pdf', [RealizationMonitoringController::class, 'exportUptPdf'])->name('realization-monitoring.export-pdf');
        Route::get('realization-monitoring/{upt}/employee/{employee}', [RealizationMonitoringController::class, 'employeeDetail'])->name('realization-monitoring.employee');
        Route::get('realization-monitoring/{upt}/employee/{employee}/wp-tunggakan', [RealizationMonitoringController::class, 'wpTunggakan'])->name('realization-monitoring.wp-tunggakan');
        Route::get('realization-monitoring/{upt}/employee/{employee}/export-excel', [RealizationMonitoringController::class, 'exportEmployee'])->name('realization-monitoring.employee.export-excel');
        Route::get('realization-monitoring/{upt}/employee/{employee}/export-pdf', [RealizationMonitoringController::class, 'exportEmployeePdf'])->name('realization-monitoring.employee.export-pdf');
        Route::get('realization-monitoring/{upt}/district-forecast', [RealizationMonitoringController::class, 'districtForecast'])->name('realization-monitoring.district-forecast');

        // Pemantauan WP (read-only)
        Route::prefix('monitoring')
            ->name('monitoring.')
            ->group(function (): void {
                Route::get('/', [TaxPayerMonitoringController::class, 'index'])->name('index');
                Route::get('/export-excel', [TaxPayerMonitoringController::class, 'exportExcel'])->name('export-excel');
                Route::get('/wp-chart', [TaxPayerMonitoringController::class, 'wpChart'])->name('wp-chart');
                Route::get('/wp/{npwpd}/{nop}', [TaxPayerMonitoringController::class, 'wpDetail'])->name('wp-detail');
                Route::get('/wp/{npwpd}/{nop}/export-excel', [TaxPayerMonitoringController::class, 'wpDetailExportExcel'])->name('wp-detail.export-excel');
                Route::get('/wp/{npwpd}/{nop}/export-pdf', [TaxPayerMonitoringController::class, 'wpDetailExportPdf'])->name('wp-detail.export-pdf');
            });
    });

Route::middleware(['auth', 'role:admin|kepala_upt'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        // Jenis Pajak
        Route::resource('tax-types', TaxTypeController::class);
        Route::post('tax-types/{tax_type}/subtypes', [TaxTypeController::class, 'storeSubtype'])->name('tax-types.subtypes.store');
        Route::patch('tax-types/{tax_type}/subtypes/{subtype}', [TaxTypeController::class, 'updateSubtype'])->name('tax-types.subtypes.update');

        // Kecamatan
        Route::resource('districts', DistrictController::class)->except(['show']);

        // Target Tambahan per Kecamatan (Per District)
        Route::middleware('permission:manage additional-targets')->prefix('districts/{district}/additional-targets')->name('district-additional-targets.')->group(function (): void {
            Route::get('create', [DistrictAdditionalTargetController::class, 'create'])->name('create-specific');
            Route::post('/', [DistrictAdditionalTargetController::class, 'store'])->name('store-specific');
            Route::get('preview', [DistrictAdditionalTargetController::class, 'preview'])->name('preview-specific');
            Route::get('pct', [DistrictAdditionalTargetController::class, 'getPct'])->name('pct-specific');
            Route::delete('{districtAdditionalTarget}', [DistrictAdditionalTargetController::class, 'destroy'])->name('destroy');
        });

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

        // Monitoring WP — aksi assign hanya untuk admin & kepala_upt
        Route::post('monitoring/assign', [TaxPayerMonitoringController::class, 'storeTask'])->name('monitoring.assign');
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
        // Dashboard
        Route::get('/dashboard', [FieldOfficerDashboardController::class, 'index'])->name('dashboard');

        // Monitoring Specialized
        Route::prefix('monitoring')
            ->name('monitoring.')
            ->group(function (): void {
                Route::get('/', [FieldOfficerDashboardController::class, 'index'])->name('index');
                Route::get('/assigned-districts', [FieldOfficerMonitoringController::class, 'assignedDistricts'])->name('assigned-districts');
                Route::get('/target-achievement', [FieldOfficerMonitoringController::class, 'targetAchievement'])->name('target-achievement');
                Route::get('/monthly-realization', [FieldOfficerMonitoringController::class, 'monthlyRealization'])->name('monthly-realization');
                Route::get('/arrears', [FieldOfficerMonitoringController::class, 'arrears'])->name('arrears');
                Route::get('/search', [FieldOfficerMonitoringController::class, 'search'])->name('search');
                Route::get('/wp/{npwpd}', [FieldOfficerMonitoringController::class, 'taxpayerDetail'])->name('taxpayer-detail');
                Route::get('/tax-payers', [FieldOfficerMonitoringController::class, 'taxpayers'])->name('tax-payers');
                Route::get('/wp-tunggakan', [FieldOfficerMonitoringController::class, 'wpTunggakan'])->name('wp-tunggakan');

                // Exports
                Route::get('/target-achievement/export-pdf', [FieldOfficerExportController::class, 'exportPdf'])->name('export-pdf');
                Route::get('/target-achievement/export-excel', [FieldOfficerExportController::class, 'exportExcel'])->name('export-excel');
                Route::get('/tax-payers/export-excel', [TaxPayerMonitoringController::class, 'exportExcel'])->name('tax-payers.export-excel');
                Route::get('/wp-detail/{npwpd}/{nop}', [TaxPayerMonitoringController::class, 'wpDetail'])->name('wp-detail');
                Route::get('/wp-detail/{npwpd}/{nop}/export-excel', [TaxPayerMonitoringController::class, 'wpDetailExportExcel'])->name('wp-detail.export-excel');
                Route::get('/wp-detail/{npwpd}/{nop}/export-pdf', [TaxPayerMonitoringController::class, 'wpDetailExportPdf'])->name('wp-detail.export-pdf');
            });
    });

/*
|--------------------------------------------------------------------------
| RBAC Management Routes (Admin only)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        // Role Management
        Route::resource('roles', RoleController::class);
        Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->name('roles.permissions.sync');

        // Permission Management
        Route::resource('permissions', PermissionController::class)
            ->except(['show', 'edit', 'update']);

        // User RBAC Management
        Route::resource('rbac-users', RbacUserController::class)
            ->except(['show']);

        // Access Monitoring
        Route::get('access-monitoring', [AccessMonitoringController::class, 'index'])
            ->name('access-monitoring.index');
        Route::get('access-monitoring/{role}', [AccessMonitoringController::class, 'show'])
            ->name('access-monitoring.show');
    });
