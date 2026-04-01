<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Synchronization Schedule - every 5 hours (0 */5 * * *)
Schedule::command('simpadu:sync')
    ->cron('0 */5 * * *')
    ->appendOutputTo(storage_path('logs/simpadu_sync.log'));

Schedule::command('simpadu:sync-payers')
    ->cron('0 */5 * * *')
    ->appendOutputTo(storage_path('logs/simpadu_payers_sync.log'));
