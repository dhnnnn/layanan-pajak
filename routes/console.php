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
