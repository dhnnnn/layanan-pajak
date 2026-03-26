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

        // Build APBD targets: tax_type_id -> target_amount
        $apbdTargets = TaxTarget::query()
            ->where('year', $this->year)
            ->pluck('target_amount', 'tax_type_id')
            ->toArray();

        // Headers
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

        // Hierarchical Data Rows
        $parents = TaxType::query()->whereNull('parent_id')->with(['children' => fn ($q) => $q->orderBy('code')])->orderBy('code')->get();
        $no = 1;

        foreach ($parents as $parent) {
            $hasChildren = $parent->children->isNotEmpty();

            // Calculate parent totals
            $pTarget = 0;
            $pRealization = 0;
            $pUptAmounts = [];
            $pUptTargets = [];

            foreach ($this->upts as $upt) {
                $pUptAmounts[$upt->id] = 0;
                $pUptTargets[$upt->id] = 0;
            }

            if ($hasChildren) {
                foreach ($parent->children as $child) {
                    $pTarget += (float) ($apbdTargets[$child->id] ?? 0);
                    foreach ($this->upts as $upt) {
                        $childReal = (float) ($uptTotals[$upt->id][$child->id] ?? 0);
                        $pUptAmounts[$upt->id] += $childReal;
                        $pRealization += $childReal;
                        $pUptTargets[$upt->id] += (float) ($uptTargets[$upt->id][$child->id] ?? 0);
                    }
                }
            } else {
                $pTarget = (float) ($apbdTargets[$parent->id] ?? 0);
                foreach ($this->upts as $upt) {
                    $real = (float) ($uptTotals[$upt->id][$parent->id] ?? 0);
                    $pUptAmounts[$upt->id] = $real;
                    $pRealization += $real;
                    $pUptTargets[$upt->id] = (float) ($uptTargets[$upt->id][$parent->id] ?? 0);
                }
            }

            // Parent Row Row Data
            $pPercentTarget = $pTarget > 0 ? round(($pRealization / $pTarget) * 100, 1) : 0;
            $pSelisih = $pTarget - $pRealization;
            $pPercentSelisih = $pTarget > 0 ? round(($pSelisih / $pTarget) * 100, 1) : 0;

            $row = [$no++, $parent->name, $pTarget];
            foreach ($this->upts as $upt) {
                $row[] = $pUptTargets[$upt->id];
                $row[] = $pUptAmounts[$upt->id];
            }
            $row[] = $pRealization;
            $row[] = $pPercentTarget.'%';
            $row[] = $pPercentSelisih.'%';
            $row[] = $pSelisih;

            $rows[] = $row;

            // Children Rows
            if ($hasChildren) {
                foreach ($parent->children as $child) {
                    $cTarget = (float) ($apbdTargets[$child->id] ?? 0);
                    $cRealization = 0;
                    $cRow = ['', '- '.$child->name, $cTarget];

                    foreach ($this->upts as $upt) {
                        $uptT = (float) ($uptTargets[$upt->id][$child->id] ?? 0);
                        $uptR = (float) ($uptTotals[$upt->id][$child->id] ?? 0);
                        $cRow[] = $uptT;
                        $cRow[] = $uptR;
                        $cRealization += $uptR;
                    }

                    $cPercentTarget = $cTarget > 0 ? round(($cRealization / $cTarget) * 100, 1) : 0;
                    $cSelisih = $cTarget - $cRealization;
                    $cPercentSelisih = $cTarget > 0 ? round(($cSelisih / $cTarget) * 100, 1) : 0;

                    $cRow[] = $cRealization;
                    $cRow[] = $cPercentTarget.'%';
                    $cRow[] = $cPercentSelisih.'%';
                    $cRow[] = $cSelisih;

                    $rows[] = $cRow;
                }
            }
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

            // Number format for currency columns
            $summaryStart = 4 + ($uptCount * 2);

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

            // Bold parent rows (where NO column is not empty)
            for ($row = 3; $row <= $lastRow; $row++) {
                $noValue = $sheet->getCell("A{$row}")->getValue();
                if (! empty($noValue)) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
                }
            }
        }

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->freezePane('C3');
    }
}
