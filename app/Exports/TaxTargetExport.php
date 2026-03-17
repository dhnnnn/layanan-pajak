<?php

namespace App\Exports;

use App\Models\TaxTarget;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaxTargetExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function __construct(private readonly ?int $year = null) {}

    public function title(): string
    {
        return 'Target APBD';
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $year = $this->year ?? (int) date('Y');

        $targets = TaxTarget::query()
            ->with('taxType')
            ->when($this->year, fn ($q) => $q->where('year', $this->year))
            ->orderByDesc('year')
            ->orderBy('tax_type_id')
            ->get();

        $rows = [];

        // Row 1: Main Headers
        $header1 = array_fill(0, 18, null);
        $header1[0] = 'URAIAN';
        $header1[1] = "TARGET APBD {$year}";
        $header1[2] = 'S.D. TRIWULAN I';
        $header1[6] = 'S.D. TRIWULAN II';
        $header1[10] = 'S.D. TRIWULAN III';
        $header1[14] = 'S.D. TRIWULAN IV';
        $header1[18] = 'LEBIH/(KURANG)';
        $rows[] = $header1;

        // Row 2: Sub Headers
        $header2 = array_fill(0, 18, null);
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

        // Data Rows
        foreach ($targets as $target) {
            $amount = (float) $target->target_amount;
            $q1 = (float) ($target->q1_target ?? $amount * 0.25);
            $q2 = (float) ($target->q2_target ?? $amount * 0.50);
            $q3 = (float) ($target->q3_target ?? $amount * 0.75);
            $q4 = (float) ($target->q4_target ?? $amount);

            $rows[] = [
                $target->taxType->name,
                $amount,
                $q1,
                round($target->getQ1Percentage(), 1).'%',
                null, // Q1 Realisasi (kosong)
                null,
                $q2,
                round($target->getQ2Percentage(), 1).'%',
                null, // Q2 Realisasi
                null,
                $q3,
                round($target->getQ3Percentage(), 1).'%',
                null, // Q3 Realisasi
                null,
                $q4,
                round($target->getQ4Percentage(), 1).'%',
                null, // Q4 Realisasi
                null,
                null, // Lebih/Kurang
            ];
        }

        return $rows;
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

        $sheet->getStyle('A1:S2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);

        if ($lastRow > 2) {
            $sheet->getStyle("A3:S{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);

            foreach (['B', 'C', 'E', 'G', 'I', 'K', 'M', 'O', 'Q', 'S'] as $col) {
                $sheet->getStyle("{$col}3:{$col}{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }

            $sheet->getStyle("A3:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("B3:S{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->freezePane('B3');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(20);
    }
}
