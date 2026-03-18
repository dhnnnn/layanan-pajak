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

        // Pre-load APBD targets keyed by tax_type_id
        $apbdTargets = TaxTarget::query()
            ->where('year', $this->year)
            ->pluck('target_amount', 'tax_type_id');

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
            $dataRow = [$no++, $taxType->name, (float) ($apbdTargets[$taxType->id] ?? 0)];

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

        $totalUptCol = 4 + $upts->count();
        $percentTargetCol = $totalUptCol + 1;
        $percentSelisihCol = $totalUptCol + 2;
        $selisihCol = $totalUptCol + 3;
        $metadataStartCol = $selisihCol + 1;

        $lastHeaderCol = Coordinate::stringFromColumnIndex($selisihCol);
        $totalColLetter = Coordinate::stringFromColumnIndex($totalUptCol);
        $percentTargetColLetter = Coordinate::stringFromColumnIndex($percentTargetCol);
        $percentSelisihColLetter = Coordinate::stringFromColumnIndex($percentSelisihCol);
        $selisihColLetter = Coordinate::stringFromColumnIndex($selisihCol);

        $thinBlack = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];

        // Header
        $sheet->getStyle("A1:{$lastHeaderCol}1")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => $thinBlack],
        ]);

        // Data rows
        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastHeaderCol}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => $thinBlack],
            ]);
        }

        // Number formats
        $sheet->getStyle("C2:C{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        for ($col = 4; $col < $totalUptCol; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getStyle("{$colLetter}2:{$colLetter}{$lastRow}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        }

        $sheet->getStyle("{$totalColLetter}2:{$totalColLetter}{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle("{$selisihColLetter}2:{$selisihColLetter}{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle("{$percentTargetColLetter}2:{$percentTargetColLetter}{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        $sheet->getStyle("{$percentSelisihColLetter}2:{$percentSelisihColLetter}{$lastRow}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

        // Formulas
        for ($row = 2; $row <= $lastRow; $row++) {
            $firstUptCol = Coordinate::stringFromColumnIndex(4);
            $lastUptCol = Coordinate::stringFromColumnIndex($totalUptCol - 1);

            $sheet->setCellValue("{$totalColLetter}{$row}", "=SUM({$firstUptCol}{$row}:{$lastUptCol}{$row})");
            $sheet->setCellValue("{$percentTargetColLetter}{$row}", "=IF(C{$row}=0,0,{$totalColLetter}{$row}/C{$row})");
            $sheet->setCellValue("{$selisihColLetter}{$row}", "=C{$row}-{$totalColLetter}{$row}");
            $sheet->setCellValue("{$percentSelisihColLetter}{$row}", "=IF(C{$row}=0,0,{$selisihColLetter}{$row}/C{$row})");
        }

        // Hide metadata columns
        for ($i = $metadataStartCol; $i <= $metadataStartCol + 1; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setVisible(false);
        }

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->freezePane('C2');
    }

    public function title(): string
    {
        return 'Perbandingan Target UPT';
    }
}
