<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TaxPayerMonitoringExport implements WithEvents, WithTitle
{
    private readonly array $months;

    public function __construct(
        private readonly Collection $taxPayers,
        private readonly int $year,
        private readonly int $monthFrom,
        private readonly int $monthTo,
    ) {
        $this->months = range($monthFrom, $monthTo);
    }

    public function title(): string
    {
        return "Pemantau WP {$this->year}";
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $months = $this->months;
                $totalMonths = count($months);

                // Column layout:
                // A=No, B=Nama WP, C=NPWPD, D=Nama Objek, E=Jenis Pajak, F=Alamat, G=Kecamatan
                // then per month: Tgl SPTPD, Jml SPTPD, Jml Bayar (3 cols each)
                // last col = Status WP
                $fixedCols = 7;
                $lastColIdx = $fixedCols + ($totalMonths * 3) + 1;
                $lastColLtr = Coordinate::stringFromColumnIndex($lastColIdx);

                // ── ROW 1: Title ──────────────────────────────────────────
                $sheet->setCellValue('A1', "Pemantau Wajib Pajak — Tahun {$this->year}");
                $sheet->mergeCells("A1:{$lastColLtr}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getRowDimension(1)->setRowHeight(22);

                // ── ROW 2: blank ──────────────────────────────────────────
                $sheet->getRowDimension(2)->setRowHeight(6);

                // ── ROW 3: Header baris 1 ────────────────────────────────
                // Kolom tetap (merge row 3-4)
                $fixedHeaders = ['No', 'Nama WP', 'NPWPD', 'Nama Objek', 'Jenis Pajak', 'Alamat', 'Kecamatan'];
                foreach ($fixedHeaders as $i => $label) {
                    $col = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$col}3", $label);
                    $sheet->mergeCells("{$col}3:{$col}4");
                }

                // Nama bulan (merge 3 kolom di row 3)
                $colIdx = $fixedCols + 1;
                foreach ($months as $m) {
                    $startLtr = Coordinate::stringFromColumnIndex($colIdx);
                    $endLtr = Coordinate::stringFromColumnIndex($colIdx + 2);
                    $monthName = Carbon::create()->month($m)->translatedFormat('F');
                    $sheet->setCellValue("{$startLtr}3", $monthName);
                    $sheet->mergeCells("{$startLtr}3:{$endLtr}3");
                    $colIdx += 3;
                }

                // Status WP (merge row 3-4)
                $sheet->setCellValue("{$lastColLtr}3", 'Status WP');
                $sheet->mergeCells("{$lastColLtr}3:{$lastColLtr}4");

                // ── ROW 4: Sub-header bulan ───────────────────────────────
                $colIdx = $fixedCols + 1;
                foreach ($months as $m) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx).'4', 'Tgl SPTPD');
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx + 1).'4', 'Jml SPTPD');
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIdx + 2).'4', 'Jml Bayar');
                    $colIdx += 3;
                }

                // Style header rows 3-4
                $headerRange = "A3:{$lastColLtr}4";
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle($headerRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getRowDimension(3)->setRowHeight(22);
                $sheet->getRowDimension(4)->setRowHeight(18);

                // ── ROWS 5+: Data ─────────────────────────────────────────
                $rowNum = 5;
                $no = 1;
                foreach ($this->taxPayers as $wp) {
                    $sheet->setCellValue("A{$rowNum}", $no++);
                    $sheet->setCellValue("B{$rowNum}", $wp->nm_wp);
                    $sheet->setCellValue("C{$rowNum}", $wp->npwpd);
                    $sheet->setCellValue("D{$rowNum}", $wp->nm_op ?? '-');
                    $sheet->setCellValue("E{$rowNum}", $wp->tax_type_name ?? '-');
                    $sheet->setCellValue("F{$rowNum}", $wp->almt_op ?? '-');
                    $sheet->setCellValue("G{$rowNum}", $wp->nm_kecamatan ?? '-');

                    $colIdx = $fixedCols + 1;
                    foreach ($months as $m) {
                        $data = $wp->monthly_data[$m] ?? ['tgl_lapor' => '-', 'jml_lapor' => 0, 'total_bayar' => 0];

                        $tglLtr = Coordinate::stringFromColumnIndex($colIdx);
                        $jmlLtr = Coordinate::stringFromColumnIndex($colIdx + 1);
                        $bayLtr = Coordinate::stringFromColumnIndex($colIdx + 2);

                        $sheet->setCellValue("{$tglLtr}{$rowNum}", $data['tgl_lapor'] !== '-' ? $data['tgl_lapor'] : '');
                        $sheet->setCellValue("{$jmlLtr}{$rowNum}", $data['jml_lapor'] > 0 ? $data['jml_lapor'] : '');
                        $sheet->setCellValue("{$bayLtr}{$rowNum}", $data['total_bayar'] > 0 ? $data['total_bayar'] : '');

                        // Number format
                        if ($data['jml_lapor'] > 0) {
                            $sheet->getStyle("{$jmlLtr}{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
                        }
                        if ($data['total_bayar'] > 0) {
                            $sheet->getStyle("{$bayLtr}{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
                        }

                        $colIdx += 3;
                    }

                    $sheet->setCellValue("{$lastColLtr}{$rowNum}", $wp->status == '1' ? 'Aktif' : 'Non Aktif');
                    $rowNum++;
                }

                // Border data rows
                $lastDataRow = $rowNum - 1;
                if ($lastDataRow >= 5) {
                    $sheet->getStyle("A5:{$lastColLtr}{$lastDataRow}")
                        ->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                }

                // Auto size columns
                foreach (range(1, $lastColIdx) as $col) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
                }
            },
        ];
    }
}
