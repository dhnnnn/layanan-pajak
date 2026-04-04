<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Synchronization Schedule - every 6 hours
Schedule::command('simpadu:sync')
    ->everySixHours()
    ->appendOutputTo(storage_path('logs/simpadu_sync.log'));

Schedule::command('simpadu:sync-payers')
    ->everySixHours()
    ->appendOutputTo(storage_path('logs/simpadu_payers_sync.log'));

// Sync tax payer data per bulan (untuk accordion tunggakan per bulan)
// Jalankan setiap hari: sync bulan berjalan + bulan sebelumnya (untuk WP yang telat bayar)
Schedule::call(function () {
    $year = (int) now()->year;
    $currentMonth = (int) now()->month;

    Artisan::call('sync:tax-payers', ['--year' => $year, '--month' => $currentMonth]);

    // Sync bulan sebelumnya juga agar WP yang telat bayar ter-update
    if ($currentMonth > 1) {
        Artisan::call('sync:tax-payers', ['--year' => $year, '--month' => $currentMonth - 1]);
    }
})
    ->dailyAt('03:00')
    ->name('sync:tax-payers-monthly')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync_tax_payers.log'));

// Bersihkan file log setiap tengah malam agar tidak membengkak
Schedule::call(function () {
    foreach ([
        storage_path('logs/scheduler.log'),
        storage_path('logs/simpadu_sync.log'),
        storage_path('logs/simpadu_payers_sync.log'),
        storage_path('logs/sync_tax_payers.log'),
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
