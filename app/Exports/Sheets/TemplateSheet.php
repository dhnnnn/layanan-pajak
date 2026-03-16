<?php

namespace App\Exports\Sheets;

use App\Models\TaxType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private int $year;

    private ?string $districtCode;

    public function __construct(?int $year = null, ?string $districtCode = null)
    {
        $this->year = $year ?? (int) date('Y');
        $this->districtCode = $districtCode;
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $taxTypes = TaxType::query()->orderBy('code')->get();

        $rows = [];

        // Row 1: Main Headers
        $header1 = array_fill(0, 50, null);
        $header1[0] = 'URAIAN';
        $header1[1] = "TARGET APBD {$this->year}";
        $header1[2] = 'S.D. TRIWULAN I';
        $header1[6] = 'S.D. TRIWULAN II';
        $header1[10] = 'S.D. TRIWULAN III';
        $header1[14] = 'S.D. TRIWULAN IV';
        $header1[18] = 'LEBIH/(KURANG)';

        // Hidden metadata
        $header1[19] = 'kode_jenis_pajak';
        $header1[20] = 'tahun';

        $rows[] = $header1;

        // Row 2: Sub Headers (this is the heading row for import)
        $header2 = array_fill(0, 50, null);
        $header2[0] = 'uraian'; // Column name for import
        $header2[1] = 'target_apbd_2026'; // Column name for import
        // Triwulan I (cols 2-5)
        $header2[2] = 'q1_target';
        $header2[3] = 'q1_target_pct';
        $header2[4] = 'q1_realisasi';
        $header2[5] = 'q1_realisasi_pct';
        // Triwulan II (cols 6-9)
        $header2[6] = 'q2_target';
        $header2[7] = 'q2_target_pct';
        $header2[8] = 'q2_realisasi';
        $header2[9] = 'q2_realisasi_pct';
        // Triwulan III (cols 10-13)
        $header2[10] = 'q3_target';
        $header2[11] = 'q3_target_pct';
        $header2[12] = 'q3_realisasi';
        $header2[13] = 'q3_realisasi_pct';
        // Triwulan IV (cols 14-17)
        $header2[14] = 'q4_target';
        $header2[15] = 'q4_target_pct';
        $header2[16] = 'q4_realisasi';
        $header2[17] = 'q4_realisasi_pct';
        // Lebih/Kurang
        $header2[18] = 'lebih_kurang';
        // Hidden metadata
        $header2[19] = 'kode_jenis_pajak';
        $header2[20] = 'tahun';

        $rows[] = $header2;

        // Data Rows - Pre-fill with existing tax types
        foreach ($taxTypes as $taxType) {
            $dataRow = array_fill(0, 50, null);
            $dataRow[0] = $taxType->name;

            // Pre-fill hidden metadata
            $dataRow[19] = $taxType->code;
            $dataRow[20] = $this->year;

            $rows[] = $dataRow;
        }

        return $rows;
    }

    /** @return array<string, int|float> */
    public function columnWidths(): array
    {
        return [
            'A' => 35,  // URAIAN
            'B' => 18,  // TARGET APBD
            // Triwulan I
            'C' => 12,  // Target
            'D' => 8,   // %
            'E' => 12,  // Realisasi
            'F' => 8,   // %
            // Triwulan II
            'G' => 12,  // Target
            'H' => 8,   // %
            'I' => 12,  // Realisasi
            'J' => 8,   // %
            // Triwulan III
            'K' => 12,  // Target
            'L' => 8,   // %
            'M' => 12,  // Realisasi
            'N' => 8,   // %
            // Triwulan IV
            'O' => 12,  // Target
            'P' => 8,   // %
            'Q' => 12,  // Realisasi
            'R' => 8,   // %
            // Lebih/Kurang
            'S' => 14,  // Lebih/Kurang (single column)
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = count($this->array());
        $taxTypes = TaxType::query()->orderBy('code')->get();

        // Merge headers in row 1
        $sheet->mergeCells('C1:F1'); // Triwulan I
        $sheet->mergeCells('G1:J1'); // Triwulan II
        $sheet->mergeCells('K1:N1'); // Triwulan III
        $sheet->mergeCells('O1:R1'); // Triwulan IV

        // Merge cells that span both rows
        $sheet->mergeCells('A1:A2'); // URAIAN
        $sheet->mergeCells('B1:B2'); // TARGET APBD
        $sheet->mergeCells('S1:S2'); // LEBIH/KURANG

        // Set display values in row 2 for sub-headers (will be visible)
        $sheet->setCellValue('C2', 'TARGET');
        $sheet->setCellValue('D2', '%');
        $sheet->setCellValue('E2', 'REALISASI');
        $sheet->setCellValue('F2', '%');
        $sheet->setCellValue('G2', 'TARGET');
        $sheet->setCellValue('H2', '%');
        $sheet->setCellValue('I2', 'REALISASI');
        $sheet->setCellValue('J2', '%');
        $sheet->setCellValue('K2', 'TARGET');
        $sheet->setCellValue('L2', '%');
        $sheet->setCellValue('M2', 'REALISASI');
        $sheet->setCellValue('N2', '%');
        $sheet->setCellValue('O2', 'TARGET');
        $sheet->setCellValue('P2', '%');
        $sheet->setCellValue('Q2', 'REALISASI');
        $sheet->setCellValue('R2', '%');

        // Header Style for both rows
        $sheet->getStyle('A1:S2')->applyFromArray([
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
        $sheet->getStyle("A3:S{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ]);

        // Add dropdown validation for URAIAN column
        $taxTypeNames = $taxTypes->pluck('name')->toArray();
        $taxTypeList = '"'.implode(',', $taxTypeNames).'"';

        for ($row = 3; $row <= $lastRow; $row++) {
            $validation = $sheet->getCell("A{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Input Tidak Valid');
            $validation->setError('Silakan pilih jenis pajak dari daftar yang tersedia.');
            $validation->setPromptTitle('Pilih Jenis Pajak');
            $validation->setPrompt('Pilih jenis pajak dari dropdown atau ketik nama yang sesuai dengan daftar.');
            $validation->setFormula1($taxTypeList);
        }

        // Hide metadata columns (19-20)
        for ($i = 19; $i <= 20; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($colLetter)->setVisible(false);
        }

        $sheet->freezePane('B3');
    }

    public function title(): string
    {
        return 'Import Realisasi';
    }
}
