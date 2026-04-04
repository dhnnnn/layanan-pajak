<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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

// Clean up log files daily at midnight to prevent storage bloat
Schedule::call(function () {
    $logs = [
        storage_path('logs/scheduler.log'),
        storage_path('logs/simpadu_sync.log'),
        storage_path('logs/simpadu_payers_sync.log'),
    ];

    foreach ($logs as $path) {
        if (file_exists($path)) {
            file_put_contents($path, '');
        }
    }

    \Illuminate\Support\Facades\Log::info('Log files cleared by scheduler.');
})
    ->dailyAt('00:00')
    ->name('clear-log-files')
    ->withoutOverlapping();
// Syncs current month data so accordion tunggakan per bulan is always fresh
Schedule::call(function () {
    Artisan::call('sync:tax-payers', [
        '--year'  => (int) now()->year,
        '--month' => (int) now()->month,
    ]);
})
    ->dailyAt('03:00')
    ->appendOutputTo(storage_path('logs/sync_tax_payers.log'))
    ->name('sync:tax-payers-monthly')
    ->withoutOverlapping();
