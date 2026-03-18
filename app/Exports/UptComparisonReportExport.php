<?php

namespace App\Exports;

use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxTarget;
use App\Models\TaxType;
use App\Models\Upt;
use App\Models\UptComparison;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UptComparisonReportExport implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    private Collection $upts;

    public function __construct(private readonly int $year)
    {
        $this->upts = Upt::query()->orderBy('code')->get();
    }

    public function title(): string
    {
        return "Perbandingan UPT {$this->year}";
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $rows = [];

        // Build realization totals: upt_id -> tax_type_id -> total
        $uptTotals = [];
        foreach ($this->upts as $upt) {
            $userIds = $upt->users()->role('pegawai')->pluck('users.id');
            $totals = TaxRealizationDailyEntry::query()
                ->whereIn('user_id', $userIds)
                ->whereYear('entry_date', $this->year)
                ->selectRaw('tax_type_id, SUM(amount) as total')
                ->groupBy('tax_type_id')
                ->pluck('total', 'tax_type_id');
            $uptTotals[$upt->id] = $totals->map(fn ($v) => (float) $v)->toArray();
        }

        // Build UPT targets: upt_id -> tax_type_id -> target_amount
        $uptTargets = UptComparison::query()
            ->where('year', $this->year)
            ->get()
            ->groupBy('upt_id')
            ->map(fn ($rows) => $rows->pluck('target_amount', 'tax_type_id')->map(fn ($v) => (float) $v)->toArray())
            ->toArray();

        // Row 1: main header (UPT names will be merged via events)
        // Col layout: NO | JENIS PAJAK | TARGET APBD | [UPT1 TARGET | UPT1 REALISASI] x N | TOTAL REALISASI | % TARGET | % SELISIH | SELISIH
        $header1 = ['NO.', 'JENIS PAJAK', "TARGET APBD {$this->year}"];
        foreach ($this->upts as $upt) {
            $header1[] = strtoupper($upt->name);
            $header1[] = '';
        }
        $header1[] = 'TOTAL REALISASI';
        $header1[] = '% TARGET';
        $header1[] = '% SELISIH';
        $header1[] = 'SELISIH (RP.)';
        $rows[] = $header1;

        // Row 2: sub-header
        $header2 = ['', '', ''];
        foreach ($this->upts as $upt) {
            $header2[] = 'TARGET';
            $header2[] = 'REALISASI';
        }
        $header2[] = '';
        $header2[] = '';
        $header2[] = '';
        $header2[] = '';
        $rows[] = $header2;

        // Data rows
        $taxTypes = TaxType::query()->orderBy('name')->get();
        $no = 1;

        foreach ($taxTypes as $taxType) {
            $apbdTarget = (float) (TaxTarget::query()
                ->where('tax_type_id', $taxType->id)
                ->where('year', $this->year)
                ->value('target_amount') ?? 0);

            $totalRealisasi = 0;
            $row = [$no++, $taxType->name, $apbdTarget];

            foreach ($this->upts as $upt) {
                $uptTarget = (float) ($uptTargets[$upt->id][$taxType->id] ?? 0);
                $realisasi = (float) ($uptTotals[$upt->id][$taxType->id] ?? 0);
                $row[] = $uptTarget;
                $row[] = $realisasi;
                $totalRealisasi += $realisasi;
            }

            $percentTarget = $apbdTarget > 0 ? round(($totalRealisasi / $apbdTarget) * 100, 1) : 0;
            $selisih = $apbdTarget - $totalRealisasi;
            $percentSelisih = $apbdTarget > 0 ? round(($selisih / $apbdTarget) * 100, 1) : 0;

            $row[] = $totalRealisasi;
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
        $widths = ['A' => 6, 'B' => 35, 'C' => 20];
        $uptCount = $this->upts->count();

        // 2 cols per UPT starting at col D (index 4)
        for ($i = 0; $i < $uptCount * 2; $i++) {
            $col = Coordinate::stringFromColumnIndex(4 + $i);
            $widths[$col] = 18;
        }

        $offset = 4 + ($uptCount * 2);
        $widths[Coordinate::stringFromColumnIndex($offset)] = 18;     // Total Realisasi
        $widths[Coordinate::stringFromColumnIndex($offset + 1)] = 10; // % Target
        $widths[Coordinate::stringFromColumnIndex($offset + 2)] = 10; // % Selisih
        $widths[Coordinate::stringFromColumnIndex($offset + 3)] = 18; // Selisih

        return $widths;
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $uptCount = $this->upts->count();

                // Merge NO, JENIS PAJAK, TARGET APBD vertically (rows 1-2)
                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');

                // Merge each UPT header horizontally across its 2 sub-columns
                for ($i = 0; $i < $uptCount; $i++) {
                    $startCol = Coordinate::stringFromColumnIndex(4 + ($i * 2));
                    $endCol = Coordinate::stringFromColumnIndex(4 + ($i * 2) + 1);
                    $sheet->mergeCells("{$startCol}1:{$endCol}1");
                }

                // Merge summary columns vertically (rows 1-2)
                $summaryStart = 4 + ($uptCount * 2);
                for ($i = 0; $i < 4; $i++) {
                    $col = Coordinate::stringFromColumnIndex($summaryStart + $i);
                    $sheet->mergeCells("{$col}1:{$col}2");
                }
            },
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = count($this->array());
        $uptCount = $this->upts->count();
        $lastColIndex = 4 + ($uptCount * 2) + 4;
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex - 1);

        $thinBlack = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];

        // Header rows 1-2
        $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => $thinBlack],
        ]);

        // Data rows
        if ($lastRow > 2) {
            $sheet->getStyle("A3:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => $thinBlack],
            ]);

            // Number format for currency columns (C onwards except % cols)
            $summaryStart = 4 + ($uptCount * 2);
            $percentTargetCol = Coordinate::stringFromColumnIndex($summaryStart + 1);
            $percentSelisihCol = Coordinate::stringFromColumnIndex($summaryStart + 2);

            $sheet->getStyle("C3:C{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

            for ($i = 0; $i < $uptCount * 2; $i++) {
                $col = Coordinate::stringFromColumnIndex(4 + $i);
                $sheet->getStyle("{$col}3:{$col}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            }

            $totalCol = Coordinate::stringFromColumnIndex($summaryStart);
            $selisihCol = Coordinate::stringFromColumnIndex($summaryStart + 3);
            $sheet->getStyle("{$totalCol}3:{$totalCol}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("{$selisihCol}3:{$selisihCol}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

            $sheet->getStyle("B3:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->freezePane('C3');
    }
}
