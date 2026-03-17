<?php

namespace App\Exports;

use App\Models\TaxTarget;
use App\Models\TaxType;
use App\Models\Upt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UptComparisonReportExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private Collection $upts;

    public function __construct(private readonly int $year)
    {
        $this->upts = Upt::query()
            ->with(['comparisons' => fn ($q) => $q->where('year', $year)])
            ->orderBy('code')
            ->get();
    }

    public function title(): string
    {
        return "Perbandingan UPT {$this->year}";
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $rows = [];

        // Header row
        $header = ['NO.', 'JENIS PAJAK', "TARGET {$this->year}"];
        foreach ($this->upts as $upt) {
            $header[] = strtoupper($upt->name);
        }
        $header[] = 'TOTAL UPT';
        $header[] = '% TARGET';
        $header[] = '% SELISIH';
        $header[] = 'SELISIH (RP.)';
        $rows[] = $header;

        $taxTypes = TaxType::query()->orderBy('code')->get();
        $no = 1;

        foreach ($taxTypes as $taxType) {
            $target = TaxTarget::query()
                ->where('tax_type_id', $taxType->id)
                ->where('year', $this->year)
                ->first();
            $targetAmount = (float) ($target?->target_amount ?? 0);

            $totalUpt = 0;
            $row = [$no++, $taxType->name, $targetAmount];

            foreach ($this->upts as $upt) {
                $comparison = $upt->comparisons->where('tax_type_id', $taxType->id)->first();
                $amount = (float) ($comparison?->target_amount ?? 0);
                $row[] = $amount;
                $totalUpt += $amount;
            }

            $percentTarget = $targetAmount > 0 ? round(($totalUpt / $targetAmount) * 100, 1) : 0;
            $selisih = $targetAmount - $totalUpt;
            $percentSelisih = $targetAmount > 0 ? round(($selisih / $targetAmount) * 100, 1) : 0;

            $row[] = $totalUpt;
            $row[] = $percentTarget.'%';
            $row[] = $percentSelisih.'%';
            $row[] = $selisih;

            $rows[] = $row;
        }

        return $rows;
    }

    /** @return array<string, int|float> */
    public function columnWidths(): array
    {
        $widths = ['A' => 6, 'B' => 35, 'C' => 18];
        $cols = range('D', 'Z');
        $uptCount = $this->upts->count();

        for ($i = 0; $i < $uptCount; $i++) {
            $widths[$cols[$i]] = 16;
        }

        $offset = $uptCount;
        $widths[$cols[$offset]] = 16;     // Total UPT
        $widths[$cols[$offset + 1]] = 10; // % Target
        $widths[$cols[$offset + 2]] = 10; // % Selisih
        $widths[$cols[$offset + 3]] = 18; // Selisih Rp

        return $widths;
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = count($this->array());
        $lastCol = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);

        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);

            // Number format for currency columns (C onwards except % columns)
            $uptCount = $this->upts->count();
            $cols = range('C', 'Z');
            $currencyCols = array_merge(
                [$cols[0]], // Target APBD
                array_slice($cols, 1, $uptCount), // UPT columns
                [$cols[$uptCount + 1 - 1]], // Total UPT — offset fix
            );

            // Simpler: format all numeric cols
            $sheet->getStyle("C2:{$lastCol}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');

            $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->freezePane('C2');
    }
}
