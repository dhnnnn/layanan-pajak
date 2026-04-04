<?php

namespace App\Console\Commands;

use App\Actions\Simpadu\SyncSimpaduMonthlyRealizationsAction;
use App\Actions\Simpadu\SyncSimpaduReferencesAction;
use App\Actions\Simpadu\SyncSimpaduTargetsAction;
use App\Actions\Simpadu\SyncSimpaduTaxPayersAction;
use Illuminate\Console\Command;

class SyncSimpaduCommand extends Command
{
    protected $signature = 'simpadu:sync
                            {--year= : Year to sync (defaults to current year)}
                            {--skip-wp : Skip WP sync (heavy query, use for lightweight hourly runs)}';

    protected $description = 'Sync data from Simpadunew to local database';

    public function handle(
        SyncSimpaduReferencesAction $syncAction,
        SyncSimpaduTaxPayersAction $syncWpAction,
        SyncSimpaduTargetsAction $syncTargetAction,
        SyncSimpaduMonthlyRealizationsAction $syncMonthlyAction,
    ): void {
        $year = (int) ($this->option('year') ?: date('Y'));
        $skipWp = (bool) $this->option('skip-wp');

        $this->info("Starting Simpadu synchronization for year {$year}".($skipWp ? ' (lightweight)' : '').'...');

        // References & targets are fast — always sync
        $results = $syncAction();
        $this->info("Districts: {$results['districts']['created']} created, {$results['districts']['updated']} updated.");
        $this->info("Tax Types: {$results['tax_types']['created']} created, {$results['tax_types']['updated']} updated.");

        $this->info('Syncing Target data...');
        $targetResult = $syncTargetAction($year);
        $this->info("Target Sync: {$targetResult['count']} records.");

        // Monthly realization is fast (48 records) — always sync
        $this->info('Syncing Monthly Realization data...');
        $monthlyResult = $syncMonthlyAction($year);
        $this->info("Monthly Realization Sync: {$monthlyResult['count']} records in {$monthlyResult['duration']}s");

        // WP sync is heavy (~25s, 13k+ records) — skip when --skip-wp
        if (! $skipWp) {
            $this->info('Syncing WP data...');
            $wpResult = $syncWpAction($year);
            $this->info("WP Sync: {$wpResult['count']} records in {$wpResult['duration']}s");
        } else {
            $this->info('WP sync skipped (--skip-wp).');
        }

        $this->info('Synchronization completed successfully!');
    }
}
