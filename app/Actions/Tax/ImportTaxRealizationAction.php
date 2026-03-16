<?php

namespace App\Actions\Tax;

use App\Enums\ImportStatus;
use App\Imports\TaxRealizationImport;
use App\Models\ImportLog;
use App\Models\User;
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
            );

            Excel::import($import, $storedPath, 'local');

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
