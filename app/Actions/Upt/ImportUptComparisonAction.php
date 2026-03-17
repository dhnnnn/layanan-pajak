<?php

namespace App\Actions\Upt;

use App\Enums\ImportStatus;
use App\Imports\UptComparisonImport;
use App\Models\ImportLog;
use App\Models\TaxType;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportUptComparisonAction
{
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
            $import = new UptComparisonImport(previewOnly: false, year: $year);

            Log::info('ImportUptComparisonAction: Starting import for storedPath='.$storedPath.', year='.$year);

            Excel::import($import, $storedPath, 'local', null, 'Perbandingan Target UPT');

            // Process the imported data
            $this->processImportedData($import, $year, $user);

            Log::info('ImportUptComparisonAction: Finished. Total='.$import->getTotalRows().', Success='.$import->getSuccessRows().', Failed='.$import->getFailedRows());

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

    private function processImportedData(UptComparisonImport $import, ?int $year, User $user): void
    {
        $previewData = $import->getPreviewData();
        $storeAction = app(StoreUptComparisonAction::class);

        $successCount = 0;
        $failedCount = 0;

        DB::transaction(function () use ($previewData, $storeAction, &$successCount, &$failedCount, $import): void {
            foreach ($previewData as $data) {
                if (! $data['is_valid']) {
                    $failedCount++;
                    $import->addRowError($data['row'], implode(', ', $data['errors']));

                    continue;
                }

                try {
                    $taxType = TaxType::query()->where('code', $data['kode_jenis_pajak'])->first();

                    if ($taxType === null) {
                        $failedCount++;
                        $import->addRowError($data['row'], 'Jenis pajak tidak ditemukan');

                        continue;
                    }

                    // Store each UPT comparison
                    foreach ($data['upt_values'] as $uptId => $targetAmount) {
                        $storeAction([
                            'tax_type_id' => $taxType->id,
                            'upt_id' => $uptId,
                            'year' => $data['tahun'],
                            'target_amount' => $targetAmount,
                        ]);
                    }

                    $successCount++;
                } catch (Throwable $e) {
                    $failedCount++;
                    $import->addRowError($data['row'], $e->getMessage());
                }
            }
        });

        $import->setSuccessRows($successCount);
        $import->setFailedRows($failedCount);
    }
}
