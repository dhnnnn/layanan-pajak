<?php

namespace App\Exports;

use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxType;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UptRealizationExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private Upt $upt;

    private string $monthName;

    /** @var array<int, string> */
    private array $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** @var Collection<int, User> */
    private Collection $employees;

    /** @var Collection<int, TaxType> */
    private Collection $taxTypes;

    /**
     * districtAmounts[employeeId][districtId][taxTypeId] = total amount
     *
     * @var array<string, array<string, array<string, float>>>
     */
    private array $districtAmounts = [];

    private array $data = [];

    public function __construct(
        private readonly string $uptId,
        private readonly int $year,
        private readonly int $month,
    ) {
        $this->upt = Upt::query()
            ->with(['users' => fn ($q) => $q->role('pegawai')->with('districts')])
            ->findOrFail($uptId);

        $this->monthName = $this->monthNames[$month];
        $this->employees = $this->upt->users;
        $this->taxTypes = TaxType::query()->orderBy('code')->get();

        $this->loadAmounts();
    }

    private function loadAmounts(): void
    {
        $userIds = $this->employees->pluck('id');

        $entries = TaxRealizationDailyEntry::query()
            ->whereIn('user_id', $userIds)
            ->whereYear('entry_date', $this->year)
            ->whereMonth('entry_date', $this->month)
            ->selectRaw('user_id, district_id, tax_type_id, SUM(amount) as total')
            ->groupBy('user_id', 'district_id', 'tax_type_id')
            ->get();

        foreach ($entries as $entry) {
            $this->districtAmounts[$entry->user_id][$entry->district_id][$entry->tax_type_id]
                = (float) $entry->total;
        }
    }

    public function title(): string
    {
        return "{$this->upt->name} {$this->monthName} {$this->year}";
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $rows = [];

        // Build header columns:
        // Col 0: URAIAN
        // Then for each employee: one col per district
        // Last col: TOTAL

        // Row 1: title + employee names (spanning their districts)
        // Row 2: sub-header (district names + TOTAL)

        $headerRow1 = ['URAIAN'];
        $headerRow2 = [''];

        /** @var array<int, array{employee: User, district_id: string, district_name: string}> $columns */
        $columns = [];

        foreach ($this->employees as $employee) {
            $districts = $employee->districts;
            if ($districts->isEmpty()) {
                continue;
            }
            foreach ($districts as $district) {
                $headerRow1[] = strtoupper($employee->name);
                $headerRow2[] = strtoupper($district->name);
                $columns[] = ['employee' => $employee, 'district_id' => $district->id, 'district_name' => $district->name];
            }
        }

        $headerRow1[] = 'TOTAL';
        $headerRow2[] = '';

        $rows[] = $headerRow1;
        $rows[] = $headerRow2;

        // Column totals (for grand total row at bottom)
        $colTotals = array_fill(0, count($columns), 0.0);

        // Data rows: one per tax type
        foreach ($this->taxTypes as $taxType) {
            $row = [$taxType->name];
            $rowTotal = 0.0;

            foreach ($columns as $i => $col) {
                $amount = $this->districtAmounts[$col['employee']->id][$col['district_id']][$taxType->id] ?? 0.0;
                $row[] = $amount > 0 ? $amount : null;
                $rowTotal += $amount;
                $colTotals[$i] += $amount;
            }

            $row[] = $rowTotal > 0 ? $rowTotal : null;
            $rows[] = $row;
        }

        // Grand total row
        $totalRow = ['TOTAL'];
        $grandTotal = 0.0;
        foreach ($colTotals as $t) {
            $totalRow[] = $t;
            $grandTotal += $t;
        }
        $totalRow[] = $grandTotal;
        $rows[] = $totalRow;

        $this->data = $rows;

        return $rows;
    }

    /** @return array<string, int|float> */
    public function columnWidths(): array
    {
        $widths = ['A' => 42];
        $cols = range('B', 'Z');

        $colCount = 0;
        foreach ($this->employees as $employee) {
            $colCount += max(1, $employee->districts->count());
        }

        for ($i = 0; $i <= $colCount; $i++) {
            $widths[$cols[$i]] = 22;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): void
    {
        $data = $this->data;
        if (empty($data)) {
            $data = $this->array();
        }
        $totalRows = count($data);
        $lastCol = $sheet->getHighestColumn();

        $borderThin = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];

        // --- Header row 1: employee names ---
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => $borderThin],
        ]);

        // --- Header row 2: district names ---
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => $borderThin],
        ]);

        // Merge employee name cells across their districts in row 1
        $colIndex = 2; // B = 2
        foreach ($this->employees as $employee) {
            $distCount = $employee->districts->count();
            if ($distCount <= 0) {
                continue;
            }
            if ($distCount > 1) {
                $startCol = Coordinate::stringFromColumnIndex($colIndex);
                $endCol = Coordinate::stringFromColumnIndex($colIndex + $distCount - 1);
                $sheet->mergeCells("{$startCol}1:{$endCol}1");
            }
            $colIndex += $distCount;
        }

        // Merge URAIAN cell across both header rows
        $sheet->mergeCells('A1:A2');
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Merge TOTAL cell across both header rows
        $totalColLetter = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->mergeCells("{$totalColLetter}1:{$totalColLetter}2");

        // --- Data rows ---
        if ($totalRows > 2) {
            $sheet->getStyle("A3:{$lastCol}{$totalRows}")->applyFromArray([
                'borders' => ['allBorders' => $borderThin],
            ]);

            $sheet->getStyle("B3:{$lastCol}{$totalRows}")
                ->getNumberFormat()
                ->setFormatCode('"Rp" #,##0.00');

            $sheet->getStyle("A3:A{$totalRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        // --- Grand total row: bold only ---
        $sheet->getStyle("A{$totalRows}:{$lastCol}{$totalRows}")->getFont()->setBold(true);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->freezePane('B3');
    }
}
