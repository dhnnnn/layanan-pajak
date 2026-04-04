<?php

namespace App\Console\Commands;

use App\Actions\Simpadu\SyncSimpaduMonthlyRealizationsAction;
use App\Actions\Simpadu\SyncSimpaduReferencesAction;
use App\Actions\Simpadu\SyncSimpaduTargetsAction;
use App\Actions\Simpadu\SyncSimpaduTaxPayersAction;
use Illuminate\Console\Command;

class SyncSimpaduCommand extends Command
{
    protected $signature = 'simpadu:sync {--year= : Year to sync (defaults to current year)}';

    protected $description = 'Sync data from Simpadunew to local database';

    public function handle(
        SyncSimpaduReferencesAction $syncAction,
        SyncSimpaduTaxPayersAction $syncWpAction,
        SyncSimpaduTargetsAction $syncTargetAction,
        SyncSimpaduMonthlyRealizationsAction $syncMonthlyAction,
    ): void {
        $year = (int) ($this->option('year') ?: date('Y'));
        $this->info("Starting Simpadu synchronization for year {$year}...");

        $results = $syncAction();
        $this->info("Districts: {$results['districts']['created']} created, {$results['districts']['updated']} updated.");
        $this->info("Tax Types: {$results['tax_types']['created']} created, {$results['tax_types']['updated']} updated.");

        $this->info('Syncing WP data...');
        $wpResult = $syncWpAction($year);
        $this->info("WP Sync: {$wpResult['count']} records in {$wpResult['duration']}s");

        $this->info('Syncing Target data...');
        $targetResult = $syncTargetAction($year);
        $this->info("Target Sync: {$targetResult['count']} records.");

        $this->info('Syncing Monthly Realization data...');
        $monthlyResult = $syncMonthlyAction($year);
        $this->info("Monthly Realization Sync: {$monthlyResult['count']} records in {$monthlyResult['duration']}s");

        $this->info('Synchronization completed successfully!');
    }
}
