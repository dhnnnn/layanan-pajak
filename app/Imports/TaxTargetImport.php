<?php

namespace App\Imports;

use App\Models\TaxType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TaxTargetImport implements SkipsEmptyRows, ToCollection, WithCalculatedFormulas, WithHeadingRow
{
    public function headingRow(): int
    {
        return 2;
    }

    private int $totalRows = 0;

    private int $successRows = 0;

    private int $failedRows = 0;

    private array $rowErrors = [];

    private array $previewData = [];

    public function __construct(
        private readonly bool $previewOnly = false,
        private readonly ?int $year = null,
    ) {}

    public function collection(Collection $rows): void
    {
        $this->totalRows = $rows->count();
        $taxTypes = TaxType::query()->get()->keyBy('code');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 3;
            $rowArray = $row->toArray();

            $uraian = trim((string) ($rowArray['uraian'] ?? $rowArray[0] ?? ''));
            if ($uraian === '') {
                continue;
            }

            $taxTypeCode = trim((string) ($rowArray['kode_jenis_pajak'] ?? ''));
            $taxType = $taxTypes->get($taxTypeCode);

            if ($taxType === null) {
                foreach ($taxTypes as $type) {
                    if ($type->name && mb_strtolower(trim($type->name)) === mb_strtolower(trim($uraian))) {
                        $taxType = $type;
                        break;
                    }
                }
            }

            $year = (int) ($rowArray['tahun'] ?? $this->year);
            $targetValue = (float) ($rowArray['target_apbd_'.$year] ?? $rowArray['target'] ?? $rowArray[1] ?? 0);

            $q1_target = (float) ($rowArray['q1_target'] ?? 0);
            $q2_target = (float) ($rowArray['q2_target'] ?? 0);
            $q3_target = (float) ($rowArray['q3_target'] ?? 0);
            $q4_target = (float) ($rowArray['q4_target'] ?? 0);

            if ($q1_target == 0 && $q2_target == 0 && $q3_target == 0 && $q4_target == 0 && $targetValue > 0) {
                $q1_target = $targetValue * 0.25;
                $q2_target = $targetValue * 0.50;
                $q3_target = $targetValue * 0.75;
                $q4_target = $targetValue;
            }

            $errors = [];
            if (! $taxType) {
                $errors[] = 'Jenis pajak tidak ditemukan.';
            }

            $this->previewData[] = [
                'row' => $rowNumber,
                'uraian' => $uraian,
                'tax_type_id' => $taxType?->id,
                'year' => $year,
                'target_amount' => $targetValue,
                'q1_target' => $q1_target,
                'q2_target' => $q2_target,
                'q3_target' => $q3_target,
                'q4_target' => $q4_target,
                'is_valid' => empty($errors),
                'errors' => $errors,
            ];
        }
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function getPreviewData(): array
    {
        return $this->previewData;
    }
}
