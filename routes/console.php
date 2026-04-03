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

// Monthly tax payer sync - runs daily at 03:00
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
