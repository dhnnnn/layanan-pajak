<?php

namespace App\Exports\Sheets;

use App\Models\Month;
use App\Models\TaxTarget;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /** @return list<list<string|int|float>> */
    public function array(): array
    {
        $monthNames = Month::names();
        $latestYear = TaxTarget::query()->max('year') ?? (int) date('Y');

        $headingRow = array_merge(
            ['Kode Jenis Pajak', 'Kode Kecamatan', 'Tahun'],
            $monthNames,
        );

        $exampleRow = array_merge(
            ['PBB', '35.14.14', $latestYear],
            array_fill(0, count($monthNames), 0),
        );

        return [$headingRow, $exampleRow];
    }

    /** @return array<string, int|float> */
    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 18,
            'C' => 8,
            'D' => 14,
            'E' => 14,
            'F' => 14,
            'G' => 14,
            'H' => 14,
            'I' => 14,
            'J' => 14,
            'K' => 14,
            'L' => 14,
            'M' => 14,
            'N' => 14,
            'O' => 14,
        ];
    }

    /** @return array<int|string, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $lastCol = 'O';

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
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

        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EFF6FF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BFDBFE'],
                ],
            ],
        ]);

        $sheet->freezePane('A2');

        $sheet
            ->getComment('A1')
            ->getText()
            ->createTextRun(
                'Isi kode sesuai sheet "Referensi Kode". Baris ke-2 adalah contoh — hapus sebelum upload.',
            );

        return [];
    }

    public function title(): string
    {
        return 'Template Import';
    }
}
