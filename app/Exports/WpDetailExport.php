<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WpDetailExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private array $rows = [];

    private int $headerRowCount = 0;

    public function __construct(
        private readonly string $npwpd,
        private readonly string $nop,
        private readonly int $year,
        private readonly int $monthFrom,
        private readonly int $monthTo,
        private readonly int $multiYear,
    ) {}

    public function title(): string
    {
        return "WP {$this->npwpd} {$this->year}";
    }

    public function array(): array
    {
        $years = $this->multiYear > 1
            ? array_map(fn ($i) => $this->year - $i, range(0, $this->multiYear - 1))
            : [$this->year];

        $months = range($this->monthFrom, $this->monthTo);

        // WP info
        $wpInfo = DB::table('simpadu_tax_payers as s')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 's.ayat')
            ->select(['s.npwpd', 's.nop', 's.nm_wp', 's.nm_op', 's.almt_op', 's.kd_kecamatan', 'tax_types.name as tax_type_name'])
            ->where('s.npwpd', $this->npwpd)->where('s.nop', $this->nop)
            ->where('s.month', 0)->orderByDesc('s.year')->first();

        $yearLabel = count($years) > 1 ? min($years).' – '.max($years) : (string) $years[0];

        // Info header rows
        $this->rows[] = ['LAPORAN DETAIL WAJIB PAJAK'];
        $this->rows[] = ['Nama WP', $wpInfo?->nm_wp ?? $this->npwpd];
        $this->rows[] = ['NPWPD', $this->npwpd];
        $this->rows[] = ['Jenis Pajak', $wpInfo?->tax_type_name ?? '-'];
        $this->rows[] = ['Kecamatan', $wpInfo?->kd_kecamatan ?? '-'];
        $this->rows[] = ['Periode', 'Bulan '.self::MONTHS[$this->monthFrom].' – '.self::MONTHS[$this->monthTo]." | Tahun {$yearLabel}"];
        $this->rows[] = [];
        $this->headerRowCount = count($this->rows);

        foreach ($years as $y) {
            $rows = DB::table('simpadu_tax_payers')
                ->where('year', $y)->where('npwpd', $this->npwpd)->where('nop', $this->nop)
                ->whereBetween('month', [$this->monthFrom, $this->monthTo])
                ->orderBy('month')->get(['month', 'total_ketetapan', 'total_bayar', 'total_tunggakan'])
                ->keyBy('month');

            $sptpdRows = DB::table('simpadu_sptpd_reports')
                ->where('year', $y)->where('npwpd', $this->npwpd)->where('nop', $this->nop)
                ->whereBetween('month', [$this->monthFrom, $this->monthTo])
                ->get(['month', 'tgl_lapor'])->keyBy('month');

            // Year section header
            $this->rows[] = ["TAHUN {$y}"];
            $this->rows[] = ['BULAN', 'TGL LAPOR', 'KETETAPAN (SPTPD)', 'REALISASI BAYAR', 'TUNGGAKAN', 'STATUS'];

            $sumSptpd = $sumBayar = $sumTunggakan = 0;
            foreach ($months as $m) {
                $r = $rows->get($m);
                $sptpd = $sptpdRows->get($m);
                $ket = (float) ($r?->total_ketetapan ?? 0);
                $byr = (float) ($r?->total_bayar ?? 0);
                $tung = (float) max($r?->total_tunggakan ?? 0, 0);
                $sumSptpd += $ket;
                $sumBayar += $byr;
                $sumTunggakan += $tung;

                $status = $ket <= 0 ? 'Belum Lapor' : ($tung <= 0 ? 'Lunas' : 'Tunggakan');
                $tglLapor = $sptpd?->tgl_lapor ? Carbon::parse($sptpd->tgl_lapor)->format('d/m/Y') : '-';

                $this->rows[] = [self::MONTHS[$m], $tglLapor, $ket ?: null, $byr ?: null, $tung ?: null, $status];
            }

            $this->rows[] = ['TOTAL', '', $sumSptpd, $sumBayar, $sumTunggakan, ''];
            $this->rows[] = [];
        }

        return $this->rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 16, 'C' => 22, 'D' => 22, 'E' => 22, 'F' => 14];
    }

    public function styles(Worksheet $sheet): void
    {
        $thin = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];
        $lastRow = count($this->rows);

        // Title
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Info rows
        for ($i = 2; $i <= $this->headerRowCount; $i++) {
            $sheet->getStyle("A{$i}")->getFont()->setBold(true);
        }

        // Style each data section
        for ($row = $this->headerRowCount + 1; $row <= $lastRow; $row++) {
            $val = $sheet->getCell("A{$row}")->getValue();

            // Year header
            if (is_string($val) && str_starts_with($val, 'TAHUN ')) {
                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // Column header row
            if ($val === 'BULAN') {
                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f1f5f9']],
                    'borders' => ['allBorders' => $thin],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // Total row
            if ($val === 'TOTAL') {
                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'e2e8f0']],
                    'borders' => ['allBorders' => $thin],
                ]);
                $sheet->getStyle("C{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0');
            }

            // Data rows
            if (is_string($val) && in_array($val, array_values(self::MONTHS))) {
                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                    'borders' => ['allBorders' => $thin],
                ]);
                $sheet->getStyle("C{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0');

                // Color status
                $status = $sheet->getCell("F{$row}")->getValue();
                $statusColor = match ($status) {
                    'Lunas' => ['font' => ['color' => ['rgb' => '16a34a']], 'font2' => ['bold' => true]],
                    'Tunggakan' => ['font' => ['color' => ['rgb' => 'dc2626']], 'font2' => ['bold' => true]],
                    default => ['font' => ['color' => ['rgb' => '94a3b8']], 'font2' => []],
                };
                $sheet->getStyle("F{$row}")->getFont()->setColor(
                    (new Color($statusColor['font']['color']['rgb']))
                )->setBold(true);
            }
        }
    }
}
