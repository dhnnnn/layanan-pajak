<?php

namespace App\Exports;

use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxTarget;
use App\Models\TaxType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaxTargetExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private array $parentRows = [];

    public function __construct(private readonly ?int $year = null) {}

    public function title(): string
    {
        return 'Target APBD';
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $year = $this->year ?? (int) date('Y');

        $taxTypes = TaxType::query()
            ->with(['children' => fn ($q) => $q->orderBy('code')])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $allTargets = TaxTarget::query()
            ->where('year', $year)
            ->get()
            ->keyBy('tax_type_id');

        $realizations = TaxRealizationDailyEntry::query()
            ->whereYear('entry_date', $year)
            ->selectRaw('tax_type_id, MONTH(entry_date) as month, SUM(amount) as total')
            ->groupBy('tax_type_id', 'month')
            ->get()
            ->groupBy('tax_type_id')
            ->map(fn ($rows) => $rows->pluck('total', 'month')->map(fn ($v) => (float) $v));

        $rows = [];

        // Row 1: Main Headers
        $header1 = array_fill(0, 19, null);
        $header1[0] = 'URAIAN';
        $header1[1] = "TARGET APBD {$year}";
        $header1[2] = 'S.D. TRIWULAN I';
        $header1[6] = 'S.D. TRIWULAN II';
        $header1[10] = 'S.D. TRIWULAN III';
        $header1[14] = 'S.D. TRIWULAN IV';
        $header1[18] = 'LEBIH/(KURANG)';
        $rows[] = $header1;

        // Row 2: Sub Headers
        $header2 = array_fill(0, 19, null);
        $header2[2] = 'TARGET';
        $header2[3] = '%';
        $header2[4] = 'REALISASI';
        $header2[5] = '%';
        $header2[6] = 'TARGET';
        $header2[7] = '%';
        $header2[8] = 'REALISASI';
        $header2[9] = '%';
        $header2[10] = 'TARGET';
        $header2[11] = '%';
        $header2[12] = 'REALISASI';
        $header2[13] = '%';
        $header2[14] = 'TARGET';
        $header2[15] = '%';
        $header2[16] = 'REALISASI';
        $header2[17] = '%';
        $rows[] = $header2;

        foreach ($taxTypes as $taxType) {
            $childData = [];
            $rootAmount = 0.0;
            $rootQ1 = 0.0;
            $rootQ2 = 0.0;
            $rootQ3 = 0.0;
            $rootQ4 = 0.0;
            $rootRealizations = collect();

            if ($taxType->children->isNotEmpty()) {
                foreach ($taxType->children as $child) {
                    $target = $allTargets->get($child->id) ?? new TaxTarget([
                        'target_amount' => 0.0,
                        'q1_target' => 0.0,
                        'q2_target' => 0.0,
                        'q3_target' => 0.0,
                        'q4_target' => 0.0,
                    ]);

                    $amount = (float) $target->target_amount;
                    $rootAmount += $amount;
                    $rootQ1 += (float) ($target->q1_target ?? $amount * 0.25);
                    $rootQ2 += (float) ($target->q2_target ?? $amount * 0.50);
                    $rootQ3 += (float) ($target->q3_target ?? $amount * 0.75);
                    $rootQ4 += (float) ($target->q4_target ?? $amount);

                    $monthTotals = $realizations->get($child->id, collect());
                    foreach ($monthTotals as $m => $total) {
                        $rootRealizations[$m] = ($rootRealizations[$m] ?? 0.0) + $total;
                    }

                    $childData[] = $this->formatRow(" - {$child->name}", $target, $monthTotals);
                }

                // Parent row as total
                $pseudoTarget = new TaxTarget([
                    'target_amount' => $rootAmount,
                    'q1_target' => $rootQ1,
                    'q2_target' => $rootQ2,
                    'q3_target' => $rootQ3,
                    'q4_target' => $rootQ4,
                ]);

                $this->parentRows[] = count($rows) + 1; // +1 to convert 0-indexed count to 1-indexed next row
                $rows[] = $this->formatRow($taxType->name, $pseudoTarget, $rootRealizations);

                // Add children
                foreach ($childData as $cRow) {
                    $rows[] = $cRow;
                }
            } else {
                // Regular root row without children
                $target = $allTargets->get($taxType->id) ?? new TaxTarget([
                    'target_amount' => 0.0,
                    'q1_target' => 0.0,
                    'q2_target' => 0.0,
                    'q3_target' => 0.0,
                    'q4_target' => 0.0,
                ]);

                $monthTotals = $realizations->get($taxType->id, collect());
                $rows[] = $this->formatRow($taxType->name, $target, $monthTotals);
            }
        }

        return $rows;
    }

    private function formatRow(string $name, TaxTarget $target, $monthTotals): array
    {
        $amount = (float) $target->target_amount;
        $q1 = (float) ($target->q1_target ?? $amount * 0.25);
        $q2 = (float) ($target->q2_target ?? $amount * 0.50);
        $q3 = (float) ($target->q3_target ?? $amount * 0.75);
        $q4 = (float) ($target->q4_target ?? $amount);

        // Cumulative realization per quarter
        $r1 = 0.0;
        for ($m = 1; $m <= 3; $m++) {
            $r1 += $monthTotals->get($m, 0.0);
        }
        $r2 = $r1;
        for ($m = 4; $m <= 6; $m++) {
            $r2 += $monthTotals->get($m, 0.0);
        }
        $r3 = $r2;
        for ($m = 7; $m <= 9; $m++) {
            $r3 += $monthTotals->get($m, 0.0);
        }
        $r4 = $r3;
        for ($m = 10; $m <= 12; $m++) {
            $r4 += $monthTotals->get($m, 0.0);
        }

        $pR1 = $amount > 0 ? round($r1 / $amount * 100, 1) : 0;
        $pR2 = $amount > 0 ? round($r2 / $amount * 100, 1) : 0;
        $pR3 = $amount > 0 ? round($r3 / $amount * 100, 1) : 0;
        $pR4 = $amount > 0 ? round($r4 / $amount * 100, 1) : 0;

        $lebihKurang = $r4 - $amount;

        return [
            $name,
            $amount,
            $q1,
            round($target->getQ1Percentage(), 1).'%',
            $r1,
            $pR1.'%',
            $q2,
            round($target->getQ2Percentage(), 1).'%',
            $r2,
            $pR2.'%',
            $q3,
            round($target->getQ3Percentage(), 1).'%',
            $r3,
            $pR3.'%',
            $q4,
            round($target->getQ4Percentage(), 1).'%',
            $r4,
            $pR4.'%',
            $lebihKurang,
        ];
    }

    /** @return array<string, int|float> */
    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 18,
            'C' => 14, 'D' => 8, 'E' => 14, 'F' => 8,
            'G' => 14, 'H' => 8, 'I' => 14, 'J' => 8,
            'K' => 14, 'L' => 8, 'M' => 14, 'N' => 8,
            'O' => 14, 'P' => 8, 'Q' => 14, 'R' => 8,
            'S' => 16,
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = count($this->array());

        $sheet->mergeCells('C1:F1');
        $sheet->mergeCells('G1:J1');
        $sheet->mergeCells('K1:N1');
        $sheet->mergeCells('O1:R1');
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('S1:S2');

        $borderThin = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];

        $sheet->getStyle('A1:S2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => $borderThin],
        ]);

        if ($lastRow > 2) {
            $sheet->getStyle("A3:S{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => $borderThin],
            ]);

            foreach (['B', 'C', 'E', 'G', 'I', 'K', 'M', 'O', 'Q', 'S'] as $col) {
                $sheet->getStyle("{$col}3:{$col}{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }

            $sheet->getStyle("A3:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("B3:S{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Format entire Column A as Text explicitly using the '@' format code
            $sheet->getStyle('A')->getNumberFormat()->setFormatCode('@');
        }

        $sheet->freezePane('B3');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(20);
    }
}
