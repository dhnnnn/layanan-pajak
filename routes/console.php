<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bersihkan file log setiap tengah malam
Schedule::call(function () {
    foreach ([
        storage_path('logs/scheduler.log'),
        storage_path('logs/simpadu_sync.log'),
        storage_path('logs/simpadu_payers_sync.log'),
        storage_path('logs/sync_tax_payers.log'),
        storage_path('logs/laravel.log'),
        storage_path('logs/browser.log'),
    ] as $path) {
        if (file_exists($path)) {
            file_put_contents($path, '');
        }
    }
    Log::info('Log files cleared by scheduler.');
})
    ->dailyAt('00:00')
    ->name('clear-log-files')
    ->withoutOverlapping();

// Sync realisasi bulanan dashboard — setiap jam (ringan, hanya 48 records)
// Lewati jam yang sudah disync oleh jadwal 6 jam
Schedule::command('simpadu:sync --skip-wp')
    ->hourly()
    ->when(fn () => (int) now()->hour % 6 !== 0)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/simpadu_sync.log'));

// Sync WP lengkap (berat ~25 detik) — setiap 6 jam (00, 06, 12, 18)
Schedule::command('simpadu:sync')
    ->everySixHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/simpadu_sync.log'));

// Sync payer realizations — dikurangi dari 6 jam ke 12 jam agar tidak membebani SIMPADU
Schedule::command('simpadu:sync-payers')
    ->twiceDaily(6, 18)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/simpadu_payers_sync.log'));

// Sync tax payer data per bulan — dikurangi dari 2 jam ke 6 jam, hanya bulan berjalan
// Bulan lalu tidak perlu di-sync ulang karena data historis tidak berubah
Schedule::call(function () {
    $year = (int) now()->year;
    $currentMonth = (int) now()->month;

    Artisan::call('sync:tax-payers', ['--year' => $year, '--month' => $currentMonth]);
    Log::info("Tax payer sync completed for {$year}-{$currentMonth}.");
})
    ->everySixHours()
    ->name('sync:tax-payers-monthly')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync_tax_payers.log'));
