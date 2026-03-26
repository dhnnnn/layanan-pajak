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

        $taxTypes = TaxType::query()
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('code')
            ->get()
            ->map(fn (TaxType $taxType): TaxType => tap($taxType, function (TaxType $taxType): void {
                if ($taxType->parent !== null) {
                    $taxType->name = $taxType->parent->name.' - '.$taxType->name;
                }
            }));

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
        $thinBlack = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];

        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('D1:E1');

        // Title row
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => $thinBlack],
        ]);

        // Header row
        $sheet->getStyle('A2:E2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => $thinBlack],
        ]);

        // Data rows
        if ($lastRow > 2) {
            $sheet->getStyle("A3:B{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => $thinBlack],
            ]);
            $sheet->getStyle("D3:E{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => $thinBlack],
            ]);
        }
    }
}
