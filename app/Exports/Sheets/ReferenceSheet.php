<?php

namespace App\Exports\Sheets;

use App\Models\District;
use App\Models\TaxType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReferenceSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Kode Referensi';
    }

    /** @return array<int, array<int, string>> */
    public function array(): array
    {
        $rows = [];

        $rows[] = [
            'DAFTAR KODE JENIS PAJAK',
            '',
            '',
            'DAFTAR KODE KECAMATAN',
            '',
        ];
        $rows[] = ['Kode', 'Nama Jenis Pajak', '', 'Kode', 'Nama Kecamatan'];

        $taxTypes = TaxType::query()->orderBy('code')->get();
        $districts = District::query()->orderBy('code')->get();

        $maxCount = max($taxTypes->count(), $districts->count());

        for ($i = 0; $i < $maxCount; $i++) {
            $taxType = $taxTypes->get($i);
            $district = $districts->get($i);

            $rows[] = [
                $taxType?->code ?? '',
                $taxType?->name ?? '',
                '',
                $district?->code ?? '',
                $district?->name ?? '',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = count($this->array());

        // Title row
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('D1:E1');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
        ]);

        // Header row
        $sheet->getStyle('A2:E2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'BDD7EE'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Separator column C
        $sheet->getStyle("C1:C{$lastRow}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F2F2'],
            ],
        ]);

        // Data rows alternating
        for ($row = 3; $row <= $lastRow; $row++) {
            $color = $row % 2 === 0 ? 'DDEBF7' : 'FFFFFF';
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color],
                ],
            ]);
            $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color],
                ],
            ]);
        }

        // Borders for data area
        $sheet->getStyle("A2:B{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDD7EE'],
                ],
            ],
        ]);

        $sheet->getStyle("D2:E{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDD7EE'],
                ],
            ],
        ]);
    }
}
