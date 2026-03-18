<?php

namespace App\Exports\Sheets;

use App\Models\District;
use App\Models\TaxType;
use App\Models\UptComparison;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeRealizationTemplateSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /** @var list<District> */
    private array $districtList;

    /** @var array<string, float> target per tax_type_id from UptComparison */
    private array $uptTargets;

    public function __construct(
        private readonly int $year,
        private readonly string $uptId,
        /** @var list<string> */
        private readonly array $districtIds,
    ) {
        $this->districtList = District::query()
            ->whereIn('id', $this->districtIds)
            ->orderBy('name')
            ->get()
            ->all();

        // Load UPT targets keyed by tax_type_id, sum in case of duplicates
        $this->uptTargets = UptComparison::query()
            ->where('upt_id', $this->uptId)
            ->where('year', $this->year)
            ->selectRaw('tax_type_id, SUM(target_amount) as total')
            ->groupBy('tax_type_id')
            ->get()
            ->pluck('total', 'tax_type_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $taxTypes = TaxType::query()->orderBy('code')->get();
        $districtCount = count($this->districtList);

        $rows = [];

        // Row 1: Title header — URAIAN + district names (each spans 8 cols: Q1 target, Q1 real, Q2 target, Q2 real, Q3 target, Q3 real, Q4 target, Q4 real)
        $row1 = ['URAIAN', 'KODE_PAJAK', 'TAHUN'];
        foreach ($this->districtList as $district) {
            $row1[] = strtoupper($district->name);
            for ($i = 1; $i < 8; $i++) {
                $row1[] = null;
            }
        }
        $rows[] = $row1;

        // Row 2: Triwulan headers
        $row2 = [null, null, null];
        foreach ($this->districtList as $district) {
            $row2[] = 'TRIWULAN 1';
            $row2[] = null;
            $row2[] = 'TRIWULAN 2';
            $row2[] = null;
            $row2[] = 'TRIWULAN 3';
            $row2[] = null;
            $row2[] = 'TRIWULAN 4';
            $row2[] = null;
        }
        $rows[] = $row2;

        // Row 3: Sub-headers (target / realisasi)
        $row3 = [null, null, null];
        foreach ($this->districtList as $district) {
            $row3[] = 'TARGET';
            $row3[] = 'REALISASI';
            $row3[] = 'TARGET';
            $row3[] = 'REALISASI';
            $row3[] = 'TARGET';
            $row3[] = 'REALISASI';
            $row3[] = 'TARGET';
            $row3[] = 'REALISASI';
        }
        $rows[] = $row3;

        // Data rows
        foreach ($taxTypes as $taxType) {
            $totalTarget = $this->uptTargets[$taxType->id] ?? 0.0;
            $qTarget = $totalTarget > 0 ? round($totalTarget / 4, 2) : 0;

            $dataRow = [$taxType->name, $taxType->code, $this->year];

            foreach ($this->districtList as $district) {
                $dataRow[] = $qTarget;   // Q1 target (locked)
                $dataRow[] = null;       // Q1 realisasi (editable)
                $dataRow[] = $qTarget;   // Q2 target (locked)
                $dataRow[] = null;       // Q2 realisasi (editable)
                $dataRow[] = $qTarget;   // Q3 target (locked)
                $dataRow[] = null;       // Q3 realisasi (editable)
                $dataRow[] = $qTarget;   // Q4 target (locked)
                $dataRow[] = null;       // Q4 realisasi (editable)
            }

            $rows[] = $dataRow;
        }

        return $rows;
    }

    /** @return array<string, int|float> */
    public function columnWidths(): array
    {
        $widths = ['A' => 38, 'B' => 0.1, 'C' => 0.1];

        $col = 4; // D onwards
        foreach ($this->districtList as $district) {
            for ($i = 0; $i < 8; $i++) {
                $widths[Coordinate::stringFromColumnIndex($col)] = 14;
                $col++;
            }
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): void
    {
        $taxTypeCount = TaxType::query()->count();
        $lastDataRow = 3 + $taxTypeCount;
        $districtCount = count($this->districtList);
        $totalCols = 3 + ($districtCount * 8);
        $lastCol = Coordinate::stringFromColumnIndex($totalCols);

        $thinBlack = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];

        // Hide metadata columns B and C
        $sheet->getColumnDimension('B')->setVisible(false)->setWidth(0.1);
        $sheet->getColumnDimension('C')->setVisible(false)->setWidth(0.1);

        // Merge URAIAN across 3 rows
        $sheet->mergeCells('A1:A3');

        // Merge district headers (row 1) and triwulan headers (row 2)
        $col = 4;
        foreach ($this->districtList as $district) {
            $startCol = Coordinate::stringFromColumnIndex($col);
            $endCol = Coordinate::stringFromColumnIndex($col + 7);

            // Merge district name across 8 cols in row 1
            $sheet->mergeCells("{$startCol}1:{$endCol}1");

            // Merge each triwulan pair in row 2
            for ($q = 0; $q < 4; $q++) {
                $qStart = Coordinate::stringFromColumnIndex($col + ($q * 2));
                $qEnd = Coordinate::stringFromColumnIndex($col + ($q * 2) + 1);
                $sheet->mergeCells("{$qStart}2:{$qEnd}2");
            }

            $col += 8;
        }

        // Row 1 style (district names)
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => $thinBlack],
        ]);

        // Row 2 style (triwulan headers)
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => $thinBlack],
        ]);

        // Row 3 style (target/realisasi sub-headers)
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => $thinBlack],
        ]);

        // Data rows borders and alignment
        $col = 4;
        foreach ($this->districtList as $district) {
            for ($q = 0; $q < 4; $q++) {
                $targetCol = Coordinate::stringFromColumnIndex($col + ($q * 2));
                $realCol = Coordinate::stringFromColumnIndex($col + ($q * 2) + 1);

                if ($lastDataRow >= 4) {
                    $sheet->getStyle("{$targetCol}4:{$targetCol}{$lastDataRow}")->applyFromArray([
                        'borders' => ['allBorders' => $thinBlack],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    ]);
                    $sheet->getStyle("{$realCol}4:{$realCol}{$lastDataRow}")->applyFromArray([
                        'borders' => ['allBorders' => $thinBlack],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    ]);
                }
            }
            $col += 8;
        }

        // URAIAN column data rows
        if ($lastDataRow >= 4) {
            $sheet->getStyle("A4:A{$lastDataRow}")->applyFromArray([
                'borders' => ['allBorders' => $thinBlack],
            ]);
        }

        // Row heights
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(16);

        $sheet->freezePane('B4');

        // Sheet protection — lock target columns, allow editing realisasi columns
        $sheet->getProtection()->setSheet(true)->setPassword('layananpajak');

        // Unlock all cells first (default locked = false for data area)
        if ($lastDataRow >= 4) {
            $sheet->getStyle("A4:{$lastCol}{$lastDataRow}")
                ->getProtection()
                ->setLocked(Protection::PROTECTION_UNPROTECTED);
        }

        // Re-lock target columns
        $col = 4;
        foreach ($this->districtList as $district) {
            for ($q = 0; $q < 4; $q++) {
                $targetCol = Coordinate::stringFromColumnIndex($col + ($q * 2));
                if ($lastDataRow >= 4) {
                    $sheet->getStyle("{$targetCol}4:{$targetCol}{$lastDataRow}")
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_PROTECTED);
                }
            }
            $col += 8;
        }

        // Lock header rows
        $sheet->getStyle("A1:{$lastCol}3")
            ->getProtection()
            ->setLocked(Protection::PROTECTION_PROTECTED);
    }

    public function title(): string
    {
        return 'Template Realisasi';
    }
}
