<?php

namespace App\Console\Commands;

use App\Actions\Simpadu\SyncSimpaduPayerRealizationsAction;
use Illuminate\Console\Command;

class SyncSimpaduPayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simpadu:sync-payers {year? : The year to sync data for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Wajib Pajak realizations from Simpadu to local database';

    /**
     * Execute the console command.
     */
    public function handle(SyncSimpaduPayerRealizationsAction $action): void
    {
        $year = $this->argument('year') ?: (int) date('Y');

        $this->info("Syncing Wajib Pajak realizations for year {$year}...");

        try {
            $action($year);
            $this->info("Successfully synced realizations from Simpadu!");
        } catch (\Exception $e) {
            $this->error("Failed to sync: " . $e->getMessage());
        }
    }
}
