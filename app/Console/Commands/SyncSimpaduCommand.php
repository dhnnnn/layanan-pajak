<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Actions\Simpadu\SyncSimpaduReferencesAction;
use App\Actions\Simpadu\SyncSimpaduTaxPayersAction;
use App\Actions\Simpadu\SyncSimpaduTargetsAction;

class SyncSimpaduCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simpadu:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync districts and tax types from Simpadunew';

    /**
     * Execute the console command.
     */
    public function handle(
        SyncSimpaduReferencesAction $syncAction,
        SyncSimpaduTaxPayersAction $syncWpAction,
        SyncSimpaduTargetsAction $syncTargetAction
    ) {
        $this->info('Starting Simpadu synchronization...');

        // 1. Sync References (Districts, Tax Types)
        $results = $syncAction();
        $this->info("Districts: {$results['districts']['created']} created, {$results['districts']['updated']} updated.");
        $this->info("Tax Types: {$results['tax_types']['created']} created, {$results['tax_types']['updated']} updated.");

        // 2. Sync WP data for current year (Materialized view pattern)
        $year = (int) date('Y');
        $this->info("Syncing WP data for year {$year}...");
        $wpResult = $syncWpAction($year);
        $this->info("WP Sync: {$wpResult['count']} records processed in {$wpResult['duration']}s");

        // 3. Sync Target data for current year
        $this->info("Syncing Target data for year {$year}...");
        $targetResult = $syncTargetAction($year);
        $this->info("Target Sync: {$targetResult['count']} records processed.");

        $this->info('Synchronization completed successfully!');
    }
}
