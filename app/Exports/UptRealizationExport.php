<?php

namespace App\Exports;

use App\Models\Upt;
use Illuminate\Support\Facades\DB;
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

class UptRealizationExport implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    private Upt $upt;

    private array $data = [];

    // Jenis pajak yang bisa dipecah per kecamatan (punya kd_kecamatan di simpadu_tax_payers)
    private const TAX_ITEMS = [
        ['label' => 'Pajak Reklame',                    'ayat' => '41104', 'indent' => false],
        ['label' => 'Pajak Mineral Bukan Logam dan Batuan', 'ayat' => '41111', 'indent' => false],
        ['label' => 'Pajak Air Tanah',                  'ayat' => '41108', 'indent' => false],
        ['label' => 'Pajak Barang dan Jasa Tertentu (PBJT)', 'ayat' => null, 'indent' => false, 'group' => true],
        ['label' => 'PBJT Makanan dan/atau Minuman',    'ayat' => '41102', 'indent' => true],
        ['label' => 'PBJT Tenaga Listrik',              'ayat' => '41105', 'indent' => true],
        ['label' => 'PBJT Jasa Perhotelan',             'ayat' => '41101', 'indent' => true],
        ['label' => 'PBJT Jasa Parkir',                 'ayat' => '41107', 'indent' => true],
        ['label' => 'PBJT Jasa Kesenian dan Hiburan',   'ayat' => '41103', 'indent' => true],
    ];

    public function __construct(
        private readonly string $uptId,
        private readonly int $year,
        private readonly int $month,
    ) {
        $this->upt = Upt::query()
            ->with(['districts', 'users' => fn ($q) => $q->role('pegawai')->with('districts')])
            ->findOrFail($uptId);
    }

    public function title(): string
    {
        return "Realisasi {$this->upt->name} {$this->year}";
    }

    public function array(): array
    {
        $rows = [];
        $employees = $this->upt->users;

        // Kumpulkan semua kecamatan yang ditangani per pegawai
        // columns = [{employee, district}]
        $columns = [];
        foreach ($employees as $employee) {
            foreach ($employee->districts as $district) {
                $columns[] = ['employee' => $employee, 'district' => $district];
            }
        }

        // Ambil data ketetapan & realisasi per ayat per kecamatan dari lokal
        $allDistrictCodes = collect($columns)->pluck('district.simpadu_code')->filter()->unique()->values()->toArray();

        $stats = DB::table('simpadu_tax_payers')
            ->where('year', $this->year)
            ->where('status', '1')
            ->where('month', 0)
            ->whereIn('kd_kecamatan', $allDistrictCodes)
            ->selectRaw('ayat, kd_kecamatan, SUM(total_ketetapan) as ket, SUM(total_bayar) as byr')
            ->groupBy('ayat', 'kd_kecamatan')
            ->get()
            ->groupBy('ayat')
            ->map(fn ($g) => $g->keyBy('kd_kecamatan'));

        // Header baris 1: URAIAN | [nama pegawai spanning kecamatannya x2 (target+real)] | TOTAL
        $header1 = ['URAIAN'];
        $header2 = [''];

        foreach ($employees as $employee) {
            $distCount = $employee->districts->count();
            if ($distCount === 0) {
                continue;
            }
            // Setiap kecamatan punya 2 kolom: TARGET & REALISASI
            for ($i = 0; $i < $distCount * 2; $i++) {
                $header1[] = strtoupper($employee->name);
            }
            foreach ($employee->districts as $district) {
                $header2[] = strtoupper($district->name);
                $header2[] = '';
            }
        }

        $header1[] = 'TOTAL';
        $header1[] = '';
        $header2[] = 'TARGET';
        $header2[] = 'REALISASI';

        $rows[] = $header1;
        $rows[] = $header2;

        // Sub-header baris 3: TARGET / REALISASI per kolom
        $subHeader = [''];
        foreach ($columns as $col) {
            $subHeader[] = 'TARGET';
            $subHeader[] = 'REALISASI';
        }
        $subHeader[] = 'TARGET';
        $subHeader[] = 'REALISASI';
        $rows[] = $subHeader;

        // Data rows
        $grandTotKet = 0;
        $grandTotByr = 0;
        $colTotKet = array_fill(0, count($columns), 0.0);
        $colTotByr = array_fill(0, count($columns), 0.0);

        foreach (self::TAX_ITEMS as $item) {
            $row = [($item['indent'] ? '  - ' : '').$item['label']];
            $rowKet = 0;
            $rowByr = 0;

            if ($item['ayat'] === null) {
                // Baris grup PBJT — kosong, akan dihitung dari children
                foreach ($columns as $i => $col) {
                    $row[] = null;
                    $row[] = null;
                }
                $row[] = null;
                $row[] = null;
                $rows[] = $row;

                continue;
            }

            $ayatStats = $stats->get($item['ayat'], collect());

            foreach ($columns as $i => $col) {
                $code = $col['district']->simpadu_code;
                $distData = $ayatStats->get($code);
                $ket = $distData ? (float) $distData->ket : 0;
                $byr = $distData ? (float) $distData->byr : 0;

                $row[] = $ket > 0 ? $ket : null;
                $row[] = $byr > 0 ? $byr : null;

                $rowKet += $ket;
                $rowByr += $byr;
                $colTotKet[$i] += $ket;
                $colTotByr[$i] += $byr;
            }

            $row[] = $rowKet > 0 ? $rowKet : null;
            $row[] = $rowByr > 0 ? $rowByr : null;

            $grandTotKet += $rowKet;
            $grandTotByr += $rowByr;

            $rows[] = $row;
        }

        // Total row — query langsung per kecamatan unik agar tidak double count
        // jika satu kecamatan ditangani lebih dari satu pegawai
        $totalRow = ['TOTAL'];
        foreach ($columns as $i => $col) {
            $totalRow[] = $colTotKet[$i];
            $totalRow[] = $colTotByr[$i];
        }

        // Grand total dari DB langsung (kecamatan unik, tidak double count)
        $grandStats = DB::table('simpadu_tax_payers')
            ->where('year', $this->year)
            ->where('status', '1')
            ->where('month', 0)
            ->whereIn('kd_kecamatan', $allDistrictCodes)
            ->selectRaw('SUM(total_ketetapan) as ket, SUM(total_bayar) as byr')
            ->first();

        $totalRow[] = (float) ($grandStats->ket ?? 0);
        $totalRow[] = (float) ($grandStats->byr ?? 0);
        $rows[] = $totalRow;

        // Filter baris yang semua nilai numeriknya null/0 (kecuali header dan total)
        $dataRows = array_slice($rows, 3, -1); // baris data saja
        $filteredData = array_filter($dataRows, function ($row) {
            $values = array_slice($row, 1); // skip kolom uraian

            return collect($values)->filter(fn ($v) => $v !== null && $v > 0)->isNotEmpty();
        });

        $this->data = array_merge(
            array_slice($rows, 0, 3),
            array_values($filteredData),
            [end($rows)]
        );

        return $this->data;
    }

    public function columnWidths(): array
    {
        $employees = $this->upt->users;
        $colCount = 0;
        foreach ($employees as $e) {
            $colCount += $e->districts->count() * 2;
        }

        $widths = ['A' => 40];
        for ($i = 2; $i <= $colCount + 3; $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = 18;
        }

        return $widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $employees = $this->upt->users;

                // Merge URAIAN across 3 header rows
                $sheet->mergeCells('A1:A3');
                $sheet->getStyle('A1')->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Merge employee name cells across their district*2 columns in row 1
                $colIndex = 2;
                foreach ($employees as $employee) {
                    $distCount = $employee->districts->count();
                    if ($distCount === 0) {
                        continue;
                    }
                    $span = $distCount * 2;
                    $startCol = Coordinate::stringFromColumnIndex($colIndex);
                    $endCol = Coordinate::stringFromColumnIndex($colIndex + $span - 1);
                    if ($span > 1) {
                        $sheet->mergeCells("{$startCol}1:{$endCol}1");
                    }
                    // Merge district name across TARGET+REALISASI in row 2
                    for ($d = 0; $d < $distCount; $d++) {
                        $dc = Coordinate::stringFromColumnIndex($colIndex + $d * 2);
                        $dc2 = Coordinate::stringFromColumnIndex($colIndex + $d * 2 + 1);
                        $sheet->mergeCells("{$dc}2:{$dc2}2");
                    }
                    $colIndex += $span;
                }

                // Merge TOTAL header
                $totalStart = Coordinate::stringFromColumnIndex($colIndex);
                $totalEnd = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->mergeCells("{$totalStart}1:{$totalEnd}1");
                $sheet->mergeCells("{$totalStart}2:{$totalEnd}2");
            },
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $data = $this->data ?: $this->array();
        $totalRows = count($data);
        $lastCol = $sheet->getHighestColumn();

        $thin = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];

        // Header rows 1-3
        $sheet->getStyle("A1:{$lastCol}3")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => $thin],
        ]);

        // Data rows
        $sheet->getStyle("A4:{$lastCol}{$totalRows}")->applyFromArray([
            'borders' => ['allBorders' => $thin],
        ]);

        // Number format for data + total row
        $sheet->getStyle("B4:{$lastCol}{$totalRows}")
            ->getNumberFormat()->setFormatCode('"Rp" #,##0');

        $sheet->getStyle("A4:A{$totalRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Total row bold
        $sheet->getStyle("A{$totalRows}:{$lastCol}{$totalRows}")->applyFromArray([
            'font' => ['bold' => true],
        ]);

        foreach ([1, 2, 3] as $r) {
            $sheet->getRowDimension($r)->setRowHeight(22);
        }

        $sheet->freezePane('B4');
    }
}
