<?php

namespace App\Imports;

use App\Models\TaxType;
use App\Models\Upt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UptComparisonImport implements SkipsEmptyRows, ToCollection, WithCalculatedFormulas, WithHeadingRow
{
    public function headingRow(): int
    {
        return 1;
    }

    private int $totalRows = 0;

    private int $successRows = 0;

    private int $failedRows = 0;

    /** @var list<array{row: int, message: string}> */
    private array $rowErrors = [];

    /** @var list<array<string, mixed>> */
    private array $previewData = [];

    public function __construct(
        private readonly bool $previewOnly = false,
        private readonly ?int $year = null,
    ) {}

    public function collection(Collection $rows): void
    {
        $this->totalRows = $rows->count();

        /** @var Collection<string, TaxType> $taxTypes */
        $taxTypes = TaxType::query()->get()->keyBy('code');

        /** @var Collection<int, Upt> $upts */
        $upts = Upt::query()->orderBy('code')->get();

        // Always build preview data for processing
        $this->buildPreviewData($rows, $taxTypes, $upts);
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     * @param  Collection<string, TaxType>  $taxTypes
     * @param  Collection<int, Upt>  $upts
     */
    private function buildPreviewData(
        Collection $rows,
        Collection $taxTypes,
        Collection $upts,
    ): void {
        Log::info('buildPreviewData UPT: row count = '.$rows->count());

        if ($rows->isNotEmpty()) {
            $firstRow = $rows->first();
            $firstArray = $firstRow?->toArray();
            Log::info('First row keys: '.json_encode(array_keys($firstArray ?? [])));
        }

        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $rowNumber = $index + 2;

            // Column 0: NO, Column 1: JENIS PAJAK
            $jenisPajak = trim((string) ($rowArray['jenis_pajak'] ?? $rowArray[1] ?? ''));

            if ($jenisPajak === '') {
                Log::info("Row {$rowNumber} skipped: empty jenis pajak");

                continue;
            }

            // Skip header-like rows
            if (mb_strtolower($jenisPajak) === 'nama jenis pajak' ||
                mb_strtolower($jenisPajak) === 'jenis pajak' ||
                mb_strtolower($jenisPajak) === 'uraian') {
                Log::info("Row {$rowNumber} skipped: header row '{$jenisPajak}'");

                continue;
            }

            // Skip if this is a duplicate row (check if we already have this jenis_pajak)
            $alreadyExists = false;
            foreach ($this->previewData as $existingRow) {
                if (mb_strtolower($existingRow['jenis_pajak']) === mb_strtolower($jenisPajak)) {
                    Log::info("Row {$rowNumber} skipped: duplicate jenis pajak '{$jenisPajak}'");
                    $alreadyExists = true;
                    break;
                }
            }

            if ($alreadyExists) {
                continue;
            }

            // Get tax type code from hidden column
            $taxTypeCode = trim((string) ($rowArray['kode_jenis_pajak'] ?? ''));

            // Find tax type
            $taxType = $taxTypes->get($taxTypeCode);

            if ($taxType === null) {
                // Try matching by name
                foreach ($taxTypes as $type) {
                    if ($type && $type->name && mb_strtolower(trim($type->name)) === mb_strtolower($jenisPajak)) {
                        $taxType = $type;
                        $taxTypeCode = $type->code;
                        break;
                    }
                }
            }

            // Get year
            $year = (int) ($rowArray['tahun'] ?? 0);
            if ($year === 0 && $this->year) {
                $year = $this->year;
            }

            // Get target
            $target = (float) ($rowArray['target_'.$year] ?? $rowArray[2] ?? 0);

            $errors = [];

            if ($taxType === null) {
                $errors[] = 'Jenis pajak "'.$jenisPajak.'" tidak ditemukan dalam database.';
            }

            if ($year === 0) {
                $errors[] = 'Tahun tidak teridentifikasi.';
            }

            // Extract UPT values
            $uptValues = [];
            $totalUpt = 0;

            foreach ($upts as $upt) {
                // Laravel Excel converts headers to slug format
                // e.g., "UPT I" becomes "upt_i"
                $uptNameKey = Str::slug($upt->name, '_');
                $value = (float) ($rowArray[$uptNameKey] ?? 0);

                $uptValues[$upt->id] = $value;
                $totalUpt += $value;
            }

            // Calculate percentages
            $percentTarget = $target > 0 ? ($totalUpt / $target) * 100 : 0;
            $selisih = $target - $totalUpt;
            $percentSelisih = $target > 0 ? ($selisih / $target) * 100 : 0;

            $this->previewData[] = [
                'row' => $rowNumber,
                'jenis_pajak' => $jenisPajak,
                'kode_jenis_pajak' => $taxTypeCode,
                'tahun' => $year ?: null,
                'target' => $target,
                'upt_values' => $uptValues,
                'total_upt' => $totalUpt,
                'percent_target' => $percentTarget,
                'selisih' => $selisih,
                'percent_selisih' => $percentSelisih,
                'is_valid' => count($errors) === 0,
                'errors' => $errors,
            ];
        }
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function getSuccessRows(): int
    {
        return $this->successRows;
    }

    public function getFailedRows(): int
    {
        return $this->failedRows;
    }

    /** @return list<array<string, mixed>> */
    public function getPreviewData(): array
    {
        return $this->previewData;
    }

    /** @return list<array{row: int, message: string}> */
    public function getRowErrors(): array
    {
        return $this->rowErrors;
    }

    public function setSuccessRows(int $count): void
    {
        $this->successRows = $count;
    }

    public function setFailedRows(int $count): void
    {
        $this->failedRows = $count;
    }

    public function addRowError(int $row, string $message): void
    {
        $this->rowErrors[] = ['row' => $row, 'message' => $message];
    }
}
