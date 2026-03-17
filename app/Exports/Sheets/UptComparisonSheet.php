<?php

namespace App\Exports\Sheets;

use App\Models\TaxTarget;
use App\Models\TaxType;
use App\Models\Upt;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UptComparisonSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private int $year;

    private int $uptCount;

    public function __construct(?int $year = null)
    {
        $this->year = $year ?? (int) date('Y');
        $this->uptCount = Upt::query()->count();
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $taxTypes = TaxType::query()->orderBy('code')->get();
        $upts = Upt::query()->orderBy('code')->get();

        $rows = [];

        // Row 1: Headers
        $header = ['NO.', 'JENIS PAJAK', "TARGET {$this->year}"];

        foreach ($upts as $upt) {
            $header[] = strtoupper($upt->name);
        }

        $header[] = "TOTAL {$this->uptCount} UPT";
        $header[] = '% TARGET';
        $header[] = '% SELISIH';
        $header[] = 'SELISIH (RP.)';

        // Hidden metadata
        $header[] = 'kode_jenis_pajak';
        $header[] = 'tahun';

        $rows[] = $header;

        // Data Rows
        $no = 1;
        foreach ($taxTypes as $taxType) {
            $target = TaxTarget::query()
                ->where('tax_type_id', $taxType->id)
                ->where('year', $this->year)
                ->first();

            $dataRow = [$no++, $taxType->name, $target?->target_amount ?? 0];

            // UPT columns (empty for user input)
            foreach ($upts as $upt) {
                $dataRow[] = null;
            }

            // Formula columns (will be calculated)
            $dataRow[] = null; // TOTAL UPT
            $dataRow[] = null; // % TARGET
            $dataRow[] = null; // % SELISIH
            $dataRow[] = null; // SELISIH

            // Hidden metadata
            $dataRow[] = $taxType->code;
            $dataRow[] = $this->year;

            $rows[] = $dataRow;
        }

        return $rows;
    }

    /** @return array<string, int|float> */
    public function columnWidths(): array
    {
        $widths = [
            'A' => 6,   // NO
            'B' => 25,  // JENIS PAJAK
            'C' => 20,  // TARGET
        ];

        // Dynamic UPT columns
        $upts = Upt::query()->orderBy('code')->get();
        $colIndex = 4; // Start from column D
        foreach ($upts as $upt) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $widths[$colLetter] = 18;
            $colIndex++;
        }

        // Summary columns
        $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
        $widths[$colLetter] = 20; // TOTAL UPT

        $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
        $widths[$colLetter] = 12; // % TARGET

        $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
        $widths[$colLetter] = 12; // % SELISIH

        $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
        $widths[$colLetter] = 20; // SELISIH

        return $widths;
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = count($this->array());
        $upts = Upt::query()->orderBy('code')->get();

        // Calculate column positions
        $totalUptCol = 4 + $upts->count(); // Column after all UPT columns
        $percentTargetCol = $totalUptCol + 1;
        $percentSelisihCol = $totalUptCol + 2;
        $selisihCol = $totalUptCol + 3;
        $metadataStartCol = $selisihCol + 1;

        // Header Style
        $lastHeaderCol = Coordinate::stringFromColumnIndex($selisihCol);
        $sheet->getStyle("A1:{$lastHeaderCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ]);

        // Data styles
        $sheet->getStyle("A2:{$lastHeaderCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ]);

        // Number format for currency columns
        $sheet->getStyle("C2:C{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // UPT columns currency format
        for ($col = 4; $col < $totalUptCol; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getStyle("{$colLetter}2:{$colLetter}{$lastRow}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        }

        // Total UPT column
        $totalColLetter = Coordinate::stringFromColumnIndex($totalUptCol);
        $sheet->getStyle("{$totalColLetter}2:{$totalColLetter}{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Selisih column
        $selisihColLetter = Coordinate::stringFromColumnIndex($selisihCol);
        $sheet->getStyle("{$selisihColLetter}2:{$selisihColLetter}{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Percentage columns
        $percentTargetColLetter = Coordinate::stringFromColumnIndex($percentTargetCol);
        $percentSelisihColLetter = Coordinate::stringFromColumnIndex($percentSelisihCol);
        $sheet->getStyle("{$percentTargetColLetter}2:{$percentTargetColLetter}{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        $sheet->getStyle("{$percentSelisihColLetter}2:{$percentSelisihColLetter}{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

        // Add formulas for calculated columns
        for ($row = 2; $row <= $lastRow; $row++) {
            // TOTAL UPT = SUM of all UPT columns
            $firstUptCol = Coordinate::stringFromColumnIndex(4);
            $lastUptCol = Coordinate::stringFromColumnIndex($totalUptCol - 1);
            $totalFormula = "=SUM({$firstUptCol}{$row}:{$lastUptCol}{$row})";
            $sheet->setCellValue("{$totalColLetter}{$row}", $totalFormula);

            // % TARGET = TOTAL UPT / TARGET
            $targetCol = 'C';
            $percentTargetFormula = "=IF({$targetCol}{$row}=0,0,{$totalColLetter}{$row}/{$targetCol}{$row})";
            $sheet->setCellValue("{$percentTargetColLetter}{$row}", $percentTargetFormula);

            // SELISIH = TARGET - TOTAL UPT
            $selisihFormula = "={$targetCol}{$row}-{$totalColLetter}{$row}";
            $sheet->setCellValue("{$selisihColLetter}{$row}", $selisihFormula);

            // % SELISIH = SELISIH / TARGET
            $percentSelisihFormula = "=IF({$targetCol}{$row}=0,0,{$selisihColLetter}{$row}/{$targetCol}{$row})";
            $sheet->setCellValue("{$percentSelisihColLetter}{$row}", $percentSelisihFormula);
        }

        // Conditional formatting for % SELISIH (red if not 0%)
        $conditionalStyles = $sheet->getStyle("{$percentSelisihColLetter}2:{$percentSelisihColLetter}{$lastRow}");
        $conditional = new Conditional;
        $conditional->setConditionType(Conditional::CONDITION_CELLIS);
        $conditional->setOperatorType(Conditional::OPERATOR_NOTEQUAL);
        $conditional->addCondition('0');
        $conditional->getStyle()->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('DC2626');
        $conditional->getStyle()->getFont()->getColor()->setRGB('FFFFFF');
        $conditionalStyles->setConditionalStyles([$conditional]);

        // Hide metadata columns
        for ($i = $metadataStartCol; $i <= $metadataStartCol + 1; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setVisible(false);
        }

        $sheet->freezePane('C2');
    }

    public function title(): string
    {
        return 'Perbandingan Target UPT';
    }
}
