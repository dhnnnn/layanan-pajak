<?php

namespace App\Actions\Tax;

use App\Enums\ImportStatus;
use App\Imports\TaxRealizationImport;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportTaxRealizationAction
{
    /**
     * Process a previously stored Excel file and persist the tax realization rows.
     *
     * The file should have already been stored (e.g. via PreviewTaxRealizationAction)
     * at the given $storedPath on the local disk before calling this action.
     */
    public function __invoke(
        string $storedPath,
        string $originalFileName,
        User $user,
        ?int $year = null,
    ): ImportLog {
        /** @var ImportLog $importLog */
        $importLog = ImportLog::query()->create([
            'user_id' => $user->id,
            'file_name' => $originalFileName,
            'status' => ImportStatus::Processing,
        ]);

        try {
            $import = new TaxRealizationImport(
                importLog: $importLog,
                user: $user,
                year: $year,
            );

            Log::info('ImportTaxRealizationAction: Starting import for storedPath='.$storedPath.', year='.$year);

            Excel::import($import, $storedPath, 'local', null, 'Import Realisasi');

            Log::info('ImportTaxRealizationAction: Finished. Total='.$import->getTotalRows().', Success='.$import->getSuccessRows().', Failed='.$import->getFailedRows());

            $importLog->update([
                'status' => $import->getFailedRows() > 0
                        ? ImportStatus::Completed
                        : ImportStatus::Completed,
                'total_rows' => $import->getTotalRows(),
                'success_rows' => $import->getSuccessRows(),
                'failed_rows' => $import->getFailedRows(),
            ]);
        } catch (Throwable $e) {
            $importLog->update([
                'status' => ImportStatus::Failed,
                'notes' => $e->getMessage(),
            ]);
        }

        return $importLog->refresh();
    }
}
