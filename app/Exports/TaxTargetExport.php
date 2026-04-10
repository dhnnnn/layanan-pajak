<?php

namespace App\Exports;

use App\Models\TaxTarget;
use App\Models\TaxType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaxTargetExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private array $data = [];

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
        
        // Use the same action as the dashboard for consistency
        $generateDashboard = app(\App\Actions\Tax\GenerateTaxDashboardAction::class);
        $result = $generateDashboard(year: $year);
        $dashboardData = $result['data'];

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

        foreach ($dashboardData as $item) {
            $name = ($item['is_child'] ?? false) ? " - " . $item['tax_type_name'] : $item['tax_type_name'];
            
            if ($item['is_parent']) {
                $this->parentRows[] = count($rows) + 1;
            }

            $rows[] = [
                $name,
                $item['target_total'],
                // Q1
                $item['targets']['q1'],
                round(($item['targets']['q1'] / ($item['target_total'] ?: 1)) * 100, 1) . '%',
                $item['realizations']['q1'],
                $item['percentages']['q1'] . '%',
                // Q2
                $item['targets']['q2'],
                round(($item['targets']['q2'] / ($item['target_total'] ?: 1)) * 100, 1) . '%',
                $item['realizations']['q2'],
                $item['percentages']['q2'] . '%',
                // Q3
                $item['targets']['q3'],
                round(($item['targets']['q3'] / ($item['target_total'] ?: 1)) * 100, 1) . '%',
                $item['realizations']['q3'],
                $item['percentages']['q3'] . '%',
                // Q4
                $item['targets']['q4'],
                round(($item['targets']['q4'] / ($item['target_total'] ?: 1)) * 100, 1) . '%',
                $item['realizations']['q4'],
                $item['percentages']['q4'] . '%',
                // Lebih Kurang
                $item['more_less']
            ];
        }

        $this->data = $rows;
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
        $lastRow = count($this->data);
        if ($lastRow === 0) {
            // Re-run if called before array() for some reason
            $lastRow = count($this->array());
        }

        $sheet->mergeCells('C1:F1');
        $sheet->mergeCells('G1:J1');
        $sheet->mergeCells('K1:N1');
        $sheet->mergeCells('O1:R1');
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('S1:S2');

        $borderThin = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];
        $borderMedium = ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']];

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
