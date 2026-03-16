<?php

namespace App\Imports;

use App\Actions\Tax\StoreTaxRealizationAction;
use App\Models\District;
use App\Models\ImportLog;
use App\Models\Month;
use App\Models\TaxTarget;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TaxRealizationImport implements SkipsEmptyRows, ToCollection, WithCalculatedFormulas, WithHeadingRow
{
    public function headingRow(): int
    {
        return 2;
    }

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
        private readonly ?int $year = null,
    ) {}

    private function cleanUraian(string $uraian): string
    {
        // Remove leading/trailing spaces
        $uraian = trim($uraian);
        // Remove numbering like "1. ", "a. ", "  - "
        $uraian = preg_replace('/^([a-z\d]\.?\s+|-+\s+)/i', '', $uraian);

        return mb_strtolower(trim($uraian));
    }

    public function collection(Collection $rows): void
    {
        // No need to skip - headingRow is set to 2, so rows start from row 3
        $this->totalRows = $rows->count();

        /** @var Collection<string, TaxType> $taxTypes */
        $taxTypes = TaxType::query()->get()->keyBy('code');

        /** @var Collection<int, District> $districts */
        $districts = District::query()->get();

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
                $rowNumber = $index + 2; // +2 because 0-based and +1 for heading row
                $rowArray = $row->toArray();

                // Support multiple column names: "uraian" or numeric key 0
                $uraian = trim((string) ($rowArray['uraian'] ?? $rowArray[0] ?? ''));
                if ($uraian === '') {
                    continue;
                }

                Log::info("Import row {$rowNumber}: uraian='{$uraian}'");

                // 1. Resolve Tax Type - auto-create if not exists
                $taxTypeCode = trim((string) ($rowArray['kode_jenis_pajak'] ?? ''));
                $taxType = $taxTypes->get($taxTypeCode);

                // If no code match, try matching by name
                if ($taxType === null) {
                    $cleanUraian = $this->cleanUraian($uraian);
                    foreach ($taxTypes as $type) {
                        if ($type && $type->name && $this->cleanUraian($type->name) === $cleanUraian) {
                            $taxType = $type;
                            $taxTypeCode = $type->code;
                            break;
                        }
                    }
                }

                // If still not found, auto-create the tax type
                if ($taxType === null) {
                    // Skip if it's a known grouping header
                    if (str_contains(strtoupper($uraian), 'PAJAK DAERAH')) {
                        continue;
                    }

                    // Auto-create tax type - use unique code with microtime
                    $newCode = 'TAX-'.strtoupper(substr($uraian, 0, 3)).'-'.str_replace('.', '', microtime(true));
                    $taxType = TaxType::create([
                        'code' => $newCode,
                        'name' => $uraian,
                        'description' => 'Dibuat otomatis dari import',
                    ]);
                    $taxTypeCode = $taxType->code;

                    // Refresh taxTypes collection
                    $taxTypes = TaxType::query()->get()->keyBy('code');
                }

                // 2. Resolve Year
                $year = (int) ($rowArray['tahun'] ?? 0);
                if ($year === 0 && $this->year) {
                    $year = $this->year;
                }

                if ($year === 0) {
                    $this->failedRows++;
                    $this->rowErrors[] = [
                        'row' => $rowNumber,
                        'message' => 'Tahun tidak teridentifikasi. Silakan pilih tahun saat mengunggah.',
                    ];

                    continue;
                }

                // 3. Extract monthly data
                try {
                    $monthlyData = $this->extractMonthlyData(
                        $rowArray,
                        $headingToColumn,
                    );

                    // Get target value
                    $targetValue = (float) (
                        $rowArray['target'] ??
                        $rowArray['target_apbd_2026'] ??
                        $rowArray[1] ??
                        0
                    );

                    // Create realization for ALL districts
                    foreach ($districts as $district) {
                        $storeTaxRealization(
                            data: array_merge(
                                [
                                    'tax_type_id' => $taxType->id,
                                    'district_id' => $district->id,
                                    'year' => $year,
                                    'target' => $targetValue,
                                ],
                                $monthlyData,
                            ),
                            user: $this->user,
                        );
                    }

                    // Also save target to tax_targets table
                    if ($targetValue > 0) {
                        TaxTarget::query()->updateOrCreate(
                            [
                                'tax_type_id' => $taxType->id,
                                'year' => $year,
                            ],
                            [
                                'target_amount' => $targetValue,
                                'q1_target' => $targetValue * 0.25,
                                'q2_target' => $targetValue * 0.50,
                                'q3_target' => $targetValue * 0.75,
                                'q4_target' => $targetValue,
                            ],
                        );
                    }

                    $this->successRows++;
                    Log::info("Import: Row {$rowNumber} saved successfully for uraian='{$uraian}', districts=".$districts->count());
                } catch (\Throwable $e) {
                    $this->failedRows++;
                    $this->rowErrors[] = [
                        'row' => $rowNumber,
                        'message' => "Gagal menyimpan data: {$e->getMessage()}",
                    ];
                    Log::error("Import: Row {$rowNumber} failed: {$e->getMessage()}");
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
     * @param  Collection<int, District>  $districts
     * @param  array<string, string>  $headingToColumn
     */
    private function buildPreviewData(
        Collection $rows,
        Collection $taxTypes,
        Collection $districts,
        array $headingToColumn,
    ): void {
        Log::info('buildPreviewData: row count = '.$rows->count());

        // Debug: log first row keys
        if ($rows->isNotEmpty()) {
            $firstRow = $rows->first();
            $firstArray = $firstRow?->toArray();
            Log::info('First row keys: '.json_encode(array_keys($firstArray ?? [])));
            Log::info('First row values sample: '.json_encode(array_slice($firstArray ?? [], 0, 8, true)));
        }

        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $rowNumber = $index + 3;

            // Support multiple column names: "uraian" or numeric key 0 for first column
            $uraian = trim((string) ($rowArray['uraian'] ?? $rowArray[0] ?? ''));

            Log::info("Row {$rowNumber}: uraian = '{$uraian}'");

            if ($uraian === '') {
                Log::info("Row {$rowNumber} skipped: empty uraian");

                continue;
            }

            // Support multiple column names for various fields
            $taxTypeCode = trim((string) (
                $rowArray['kode_jenis_pajak'] ??
                $rowArray['kode'] ??
                ''
            ));

            // Support target column - either "target" or numeric index 1
            $targetValue = (float) (
                $rowArray['target'] ??
                $rowArray['target_apbd_2026'] ??
                $rowArray[1] ??
                0
            );

            // Support realization columns - various formats
            $q1_realisasi = (float) (
                $rowArray['q1_realisasi'] ??
                $rowArray['realisasi'] ??
                $rowArray['sd_tribulan_i'] ??
                $rowArray[4] ??  // Column E (index 4)
                0
            );

            $q2_realisasi = (float) (
                $rowArray['q2_realisasi'] ??
                $rowArray['sd_tribulan_ii'] ??
                $rowArray[8] ??  // Column I (index 8)
                0
            );

            $q3_realisasi = (float) (
                $rowArray['q3_realisasi'] ??
                $rowArray['sd_tribulan_iii'] ??
                $rowArray[12] ??  // Column M (index 12)
                0
            );

            $q4_realisasi = (float) (
                $rowArray['q4_realisasi'] ??
                $rowArray['sd_tribulan_iv'] ??
                $rowArray[16] ??  // Column Q (index 16)
                0
            );

            // 1. Resolve Tax Type - check kode_jenis_pajak first
            $taxTypeCode = trim((string) ($rowArray['kode_jenis_pajak'] ?? ''));
            $taxType = $taxTypes->get($taxTypeCode);

            // If not found by code, try matching by name (uraian)
            if ($taxType === null && $uraian !== '') {
                $cleanUraian = $this->cleanUraian($uraian);
                foreach ($taxTypes as $type) {
                    if ($type && $type->name && $this->cleanUraian($type->name) === $cleanUraian) {
                        $taxType = $type;
                        $taxTypeCode = $type->code;
                        break;
                    }
                }
            }

            // 2. Resolve Year
            $year = (int) ($rowArray['tahun'] ?? 0);
            if ($year === 0 && $this->year) {
                $year = $this->year;
            }

            $errors = [];

            // For preview, we don't auto-create tax types, just show warning
            if ($taxType === null) {
                $errors[] = 'Jenis pajak "'.$uraian.'" tidak ditemukan dalam database.';
            }

            if ($year === 0) {
                $errors[] = 'Tahun tidak teridentifikasi.';
            }

            $districtCount = $districts->count();

            $this->previewData[] = [
                'row' => $rowNumber,
                'uraian' => $uraian,
                'kode_jenis_pajak' => $taxTypeCode,
                'jenis_pajak' => $taxType?->name ?? $uraian,
                'jumlah_kecamatan' => $districtCount,
                'keterangan' => $taxType ? "Data akan dibuat untuk {$districtCount} kecamatan" : 'Jenis pajak akan dibuat otomatis',
                'tahun' => $year ?: null,
                'target' => $targetValue,
                'q1_realisasi' => $q1_realisasi,
                'q2_realisasi' => $q2_realisasi,
                'q3_realisasi' => $q3_realisasi,
                'q4_realisasi' => $q4_realisasi,
                'is_valid' => count($errors) === 0,
                'errors' => $errors,
            ];
        }
    }

    /**
     * Extract monthly realization values from quarterly data.
     *
     * @param  array<string, mixed>  $rowArray
     * @param  array<string, string>  $headingToColumn  e.g. ['januari' => 'january']
     * @return array<string, float>
     */
    private function extractMonthlyData(
        array $rowArray,
        array $headingToColumn,
    ): array {
        // Get quarterly realization values (cumulative), convert null to 0
        // Support multiple column name formats
        $q1_real = (float) (
            $rowArray['q1_realisasi'] ??
            $rowArray['realisasi'] ??
            $rowArray['sd_tribulan_i'] ??
            $rowArray[4] ??
            0
        );

        $q2_real = (float) (
            $rowArray['q2_realisasi'] ??
            $rowArray['sd_tribulan_ii'] ??
            $rowArray[8] ??
            0
        );

        $q3_real = (float) (
            $rowArray['q3_realisasi'] ??
            $rowArray['sd_tribulan_iii'] ??
            $rowArray[12] ??
            0
        );

        $q4_real = (float) (
            $rowArray['q4_realisasi'] ??
            $rowArray['sd_tribulan_iv'] ??
            $rowArray[16] ??
            0
        );

        // Calculate per-quarter amounts (not cumulative)
        $q1_amount = $q1_real;
        $q2_amount = $q2_real - $q1_real;
        $q3_amount = $q3_real - $q2_real;
        $q4_amount = $q4_real - $q3_real;

        // Distribute evenly across months in each quarter
        return [
            'january' => $q1_amount / 3,
            'february' => $q1_amount / 3,
            'march' => $q1_amount / 3,
            'april' => $q2_amount / 3,
            'may' => $q2_amount / 3,
            'june' => $q2_amount / 3,
            'july' => $q3_amount / 3,
            'august' => $q3_amount / 3,
            'september' => $q3_amount / 3,
            'october' => $q4_amount / 3,
            'november' => $q4_amount / 3,
            'december' => $q4_amount / 3,
        ];
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
