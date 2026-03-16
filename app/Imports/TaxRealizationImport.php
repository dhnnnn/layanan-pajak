<?php

namespace App\Imports;

use App\Actions\Tax\StoreTaxRealizationAction;
use App\Models\District;
use App\Models\ImportLog;
use App\Models\Month;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TaxRealizationImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    private int $totalRows = 0;

    private int $successRows = 0;

    private int $failedRows = 0;

    /** @var list<array{row: int, message: string}> */
    private array $rowErrors = [];

    /** @var list<array<string, mixed>> */
    private array $previewData = [];

    public function __construct(
        private readonly ?ImportLog $importLog = null,
        private readonly ?User $user = null,
        private readonly bool $previewOnly = false,
    ) {}

    public function collection(Collection $rows): void
    {
        $this->totalRows = $rows->count();

        /** @var Collection<string, TaxType> $taxTypes */
        $taxTypes = TaxType::query()->get()->keyBy('code');

        /** @var Collection<string, District> $districts */
        $districts = District::query()->get()->keyBy('code');

        // e.g. ['januari' => 'january', 'februari' => 'february', ...]
        $headingToColumn = Month::headingToColumnMap();

        if ($this->previewOnly) {
            $this->buildPreviewData(
                $rows,
                $taxTypes,
                $districts,
                $headingToColumn,
            );

            return;
        }

        $storeTaxRealization = app(StoreTaxRealizationAction::class);

        DB::transaction(function () use (
            $rows,
            $taxTypes,
            $districts,
            $headingToColumn,
            $storeTaxRealization,
        ): void {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $rowArray = $row->toArray();

                $taxTypeCode = trim(
                    (string) ($rowArray['kode_jenis_pajak'] ?? ''),
                );
                $districtCode = trim(
                    (string) ($rowArray['kode_kecamatan'] ?? ''),
                );

                $taxType = $taxTypes->get($taxTypeCode);
                $district = $districts->get($districtCode);

                if ($taxType === null) {
                    $this->failedRows++;
                    $this->rowErrors[] = [
                        'row' => $rowNumber,
                        'message' => "Kode jenis pajak '{$taxTypeCode}' tidak ditemukan.",
                    ];

                    continue;
                }

                if ($district === null) {
                    $this->failedRows++;
                    $this->rowErrors[] = [
                        'row' => $rowNumber,
                        'message' => "Kode kecamatan '{$districtCode}' tidak ditemukan.",
                    ];

                    continue;
                }

                try {
                    $monthlyData = $this->extractMonthlyData(
                        $rowArray,
                        $headingToColumn,
                    );

                    $storeTaxRealization(
                        data: array_merge(
                            [
                                'tax_type_id' => $taxType->id,
                                'district_id' => $district->id,
                                'year' => (int) ($rowArray['tahun'] ?? 0),
                            ],
                            $monthlyData,
                        ),
                        user: $this->user,
                    );

                    $this->successRows++;
                } catch (\Throwable $e) {
                    $this->failedRows++;
                    $this->rowErrors[] = [
                        'row' => $rowNumber,
                        'message' => "Gagal menyimpan data: {$e->getMessage()}",
                    ];
                }
            }
        });

        if ($this->importLog !== null && count($this->rowErrors) > 0) {
            $notes = collect($this->rowErrors)
                ->map(
                    fn (
                        array $e,
                    ): string => "Baris {$e['row']}: {$e['message']}",
                )
                ->join(' | ');

            $this->importLog->update(['notes' => $notes]);
        }
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     * @param  Collection<string, TaxType>  $taxTypes
     * @param  Collection<string, District>  $districts
     * @param  array<string, string>  $headingToColumn
     */
    private function buildPreviewData(
        Collection $rows,
        Collection $taxTypes,
        Collection $districts,
        array $headingToColumn,
    ): void {
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $rowNumber = $index + 2;

            $taxTypeCode = trim((string) ($rowArray['kode_jenis_pajak'] ?? ''));
            $districtCode = trim((string) ($rowArray['kode_kecamatan'] ?? ''));

            $taxType = $taxTypes->get($taxTypeCode);
            $district = $districts->get($districtCode);

            $errors = [];

            if ($taxType === null) {
                $errors[] = "Kode jenis pajak '{$taxTypeCode}' tidak ditemukan.";
            }

            if ($district === null) {
                $errors[] = "Kode kecamatan '{$districtCode}' tidak ditemukan.";
            }

            // Build monthly values keyed by Indonesian name (e.g. 'januari' => 12500000.0)
            $monthlyValues = [];
            foreach ($headingToColumn as $headingName => $columnName) {
                $monthlyValues[$headingName] =
                    (float) ($rowArray[$headingName] ?? 0);
            }

            $this->previewData[] = array_merge(
                [
                    'row' => $rowNumber,
                    'kode_jenis_pajak' => $taxTypeCode,
                    'nama_jenis_pajak' => $taxType?->name,
                    'kode_kecamatan' => $districtCode,
                    'nama_kecamatan' => $district?->name,
                    'tahun' => $rowArray['tahun'] ?? null,
                ],
                $monthlyValues,
                [
                    'is_valid' => count($errors) === 0,
                    'errors' => $errors,
                ],
            );
        }
    }

    /**
     * Extract monthly realization values from a row, keyed by English column name.
     *
     * @param  array<string, mixed>  $rowArray
     * @param  array<string, string>  $headingToColumn  e.g. ['januari' => 'january']
     * @return array<string, float>
     */
    private function extractMonthlyData(
        array $rowArray,
        array $headingToColumn,
    ): array {
        $data = [];

        foreach ($headingToColumn as $headingName => $columnName) {
            $data[$columnName] = (float) ($rowArray[$headingName] ?? 0);
        }

        return $data;
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
}
