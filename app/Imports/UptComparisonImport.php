<?php

namespace App\Imports;

use App\Models\TaxType;
use App\Models\Upt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class UptComparisonImport implements SkipsEmptyRows, ToCollection, WithCalculatedFormulas
{
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
        // Skip header row (row index 0)
        $dataRows = $rows->slice(1)->values();
        $this->totalRows = $dataRows->count();

        /** @var Collection<string, TaxType> $taxTypes */
        $taxTypes = TaxType::query()->get()->keyBy('code');

        /** @var Collection<int, Upt> $upts */
        $upts = Upt::query()->orderBy('code')->get();

        $this->buildPreviewData($dataRows, $taxTypes, $upts);
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
        $uptCount = $upts->count();
        // Column layout: 0=NO, 1=JENIS PAJAK, 2=TARGET, 3..3+uptCount-1=UPT cols,
        // then TOTAL, %TARGET, %SELISIH, SELISIH, kode_jenis_pajak, tahun
        $kodeCol = 3 + $uptCount + 4;  // hidden kode_jenis_pajak
        $tahunCol = $kodeCol + 1;       // hidden tahun

        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $rowNumber = $index + 2; // +2 because header is row 1

            $jenisPajak = trim((string) ($rowArray[1] ?? ''));

            if ($jenisPajak === '') {
                continue;
            }

            if (in_array(mb_strtolower($jenisPajak), ['nama jenis pajak', 'jenis pajak', 'uraian', 'no.', 'no'])) {
                continue;
            }

            // Duplicate check
            foreach ($this->previewData as $existingRow) {
                if (mb_strtolower($existingRow['jenis_pajak']) === mb_strtolower($jenisPajak)) {
                    continue 2;
                }
            }

            $taxTypeCode = trim((string) ($rowArray[$kodeCol] ?? ''));
            $taxType = $taxTypes->get($taxTypeCode);

            if ($taxType === null) {
                foreach ($taxTypes as $type) {
                    if ($type && $type->name && mb_strtolower(trim($type->name)) === mb_strtolower($jenisPajak)) {
                        $taxType = $type;
                        $taxTypeCode = $type->code;
                        break;
                    }
                }
            }

            $year = (int) ($rowArray[$tahunCol] ?? 0);
            if ($year === 0 && $this->year) {
                $year = $this->year;
            }

            $rawTarget = $rowArray[2] ?? 0;
            if (is_string($rawTarget)) {
                $rawTarget = str_replace(['.', ',', ' '], '', $rawTarget);
            }
            $target = (float) $rawTarget;

            $errors = [];
            if ($taxType === null) {
                $errors[] = 'Jenis pajak "'.$jenisPajak.'" tidak ditemukan dalam database.';
            }
            if ($year === 0) {
                $errors[] = 'Tahun tidak teridentifikasi.';
            }

            // Extract UPT values by column index (col 3, 4, 5, ...)
            $uptValues = [];
            $totalUpt = 0;
            foreach ($upts as $i => $upt) {
                $raw = $rowArray[3 + $i] ?? 0;
                // Handle string numbers with thousand separators (e.g. "6.153.613" or "6,153,613")
                if (is_string($raw)) {
                    $raw = str_replace(['.', ',', ' '], '', $raw);
                }
                $value = (float) $raw;
                $uptValues[$upt->id] = $value;
                $totalUpt += $value;
            }

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
