<?php

namespace App\Exports;

use App\Models\Upt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeMonitoringExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private array $data = [];

    private const MONTHS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    public function __construct(
        private readonly Upt $upt,
        private readonly User $employee,
        private readonly int $year,
    ) {}

    public function title(): string
    {
        return substr($this->employee->name, 0, 28)." {$this->year}";
    }

    public function array(): array
    {
        $districtCodes = $this->employee->districts->pluck('simpadu_code')->filter()->toArray();

        // WP dengan tunggakan, diurutkan terbesar
        $wpList = DB::table('simpadu_tax_payers as stp')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
            ->where('stp.year', $this->year)
            ->where('stp.month', 0)
            ->where('stp.status', '1')
            ->whereIn('stp.kd_kecamatan', $districtCodes)
            ->where('stp.total_tunggakan', '>', 0)
            ->selectRaw('
                stp.npwpd, stp.nop, stp.nm_wp, stp.nm_op, stp.kd_kecamatan,
                stp.ayat, tax_types.name as jenis_pajak,
                SUM(stp.total_ketetapan) as total_ketetapan,
                SUM(stp.total_bayar) as total_bayar,
                SUM(stp.total_tunggakan) as total_tunggakan
            ')
            ->groupBy('stp.npwpd', 'stp.nop', 'stp.nm_wp', 'stp.nm_op', 'stp.kd_kecamatan', 'stp.ayat', 'tax_types.name')
            ->orderByDesc('total_tunggakan')
            ->get();

        // Data per bulan per WP
        $monthlyData = DB::table('simpadu_tax_payers')
            ->where('year', $this->year)
            ->where('month', '>', 0)
            ->where('status', '1')
            ->whereIn('kd_kecamatan', $districtCodes)
            ->where('total_ketetapan', '>', 0)
            ->selectRaw('npwpd, nop, month, total_ketetapan, total_bayar, total_tunggakan')
            ->get()
            ->groupBy(fn ($r) => $r->npwpd.'|'.$r->nop);

        // Header
        $header = [
            'NO', 'NAMA WP', 'NPWPD', 'JENIS PAJAK', 'KECAMATAN',
            'TOTAL KETETAPAN', 'TOTAL BAYAR', 'TUNGGAKAN',
        ];
        foreach (self::MONTHS as $label) {
            $header[] = "KET {$label}";
            $header[] = "BYR {$label}";
        }
        $this->data[] = $header;

        $no = 1;
        foreach ($wpList as $wp) {
            $key = $wp->npwpd.'|'.$wp->nop;
            $monthly = $monthlyData->get($key, collect())->keyBy('month');

            $row = [
                $no++,
                $wp->nm_wp,
                $wp->npwpd,
                $wp->jenis_pajak ?? $wp->ayat,
                $wp->kd_kecamatan,
                (float) $wp->total_ketetapan,
                (float) $wp->total_bayar,
                (float) $wp->total_tunggakan,
            ];

            foreach (array_keys(self::MONTHS) as $m) {
                $mData = $monthly->get($m);
                $row[] = $mData ? (float) $mData->total_ketetapan : null;
                $row[] = $mData ? (float) $mData->total_bayar : null;
            }

            $this->data[] = $row;
        }

        return $this->data;
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 5, 'B' => 35, 'C' => 18, 'D' => 28,
            'E' => 14, 'F' => 18, 'G' => 18, 'H' => 18,
        ];
        // Kolom bulan (I dst)
        for ($i = 9; $i <= 32; $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = 14;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): void
    {
        $data = $this->data;
        $lastRow = count($data);
        $lastCol = $sheet->getHighestColumn();
        $thin = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => $thin],
        ]);

        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => $thin],
            ]);

            // Format rupiah kolom F-H dan kolom bulan
            $sheet->getStyle("F2:{$lastCol}{$lastRow}")
                ->getNumberFormat()->setFormatCode('"Rp" #,##0');

            $sheet->getStyle('A2:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->freezePane('B2');
    }
}
