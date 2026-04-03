<?php

namespace App\Exports;

use App\Models\TaxType;
use App\Models\Upt;
use Illuminate\Support\Collection;
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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RealizationMonitoringExport implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    private Collection $upts;

    private array $data = [];

    public function __construct(private readonly int $year)
    {
        $this->upts = Upt::query()->orderBy('code')->get();
    }

    public function title(): string
    {
        return "Monitoring Realisasi {$this->year}";
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $rows = [];

        // 1. Fetch ALL data from LOCAL simpadu_tax_payers table (Mainly for Ketetapan/Summary consistency)
        $districtCodesByUpt = $this->upts->mapWithKeys(
            fn (Upt $upt) => [$upt->id => $upt->districts->pluck('simpadu_code')->filter()->toArray()]
        );
        $districtIdsByUpt = $this->upts->mapWithKeys(
            fn (Upt $upt) => [$upt->id => $upt->districts->pluck('id')->toArray()]
        );

        $simpaduTaxPayers = DB::table('simpadu_tax_payers')
            ->where('year', $this->year)
            ->where('month', 0)
            ->selectRaw('ayat, kd_kecamatan, SUM(total_ketetapan) as t_ket, SUM(total_bayar) as t_byr')
            ->groupBy('ayat', 'kd_kecamatan')
            ->get();

        // 2. Fetch Multi-Source REALISASI (Same as GenerateTaxDashboardAction)
        
        // A. SIMPADU LIVE (connection: simpadunew)
        $simpaduLive = DB::connection('simpadunew')->table('pembayaran as q')
            ->leftJoin('dat_objek_pajak as s', 's.nop', '=', 'q.nop')
            ->whereBetween('q.tgl_bayar', ["{$this->year}-01-01", "{$this->year}-12-31"])
            ->selectRaw('q.ayat, s.kd_kecamatan, SUM(q.jml_byr_pokok + q.lainlain) as total')
            ->groupBy('q.ayat', 's.kd_kecamatan')
            ->get();

        // B. LOCAL MONTHLY (TaxRealization)
        $localMonthly = DB::table('tax_realizations')
            ->where('year', $this->year)
            ->selectRaw('tax_type_id, district_id, SUM(COALESCE(january,0) + COALESCE(february,0) + COALESCE(march,0) + COALESCE(april,0) + COALESCE(may,0) + COALESCE(june,0) + COALESCE(july,0) + COALESCE(august,0) + COALESCE(september,0) + COALESCE(october,0) + COALESCE(november,0) + COALESCE(december,0)) as total')
            ->groupBy('tax_type_id', 'district_id')
            ->get();

        // C. LOCAL DAILY (TaxRealizationDailyEntry)
        $localDaily = DB::table('tax_realization_daily_entries')
            ->whereYear('entry_date', $this->year)
            ->selectRaw('tax_type_id, district_id, SUM(amount) as total')
            ->groupBy('tax_type_id', 'district_id')
            ->get();

        // 2. Build Headers
        $header1 = ['NO.', 'JENIS PAJAK'];
        $header2 = ['', ''];
        
        foreach ($this->upts as $upt) {
            $header1[] = strtoupper($upt->name);
            $header1[] = ''; // spanned
            $header2[] = 'KETETAPAN';
            $header2[] = 'REALISASI';
        }
        
        $header1[] = 'TOTAL KETETAPAN';
        $header1[] = 'TOTAL REALISASI';
        $header1[] = '% CAPAIAN';
        $header1[] = 'SELISIH/TUNGGAKAN';
        
        $header2[] = '';
        $header2[] = '';
        $header2[] = '';
        $header2[] = '';
        
        $rows[] = $header1;
        $rows[] = $header2;

        // 3. Define EXACT 13 items as requested by user
        $items = [
            ['name' => 'PAJAK REKLAME', 'indent' => 0],
            ['name' => 'PAJAK BPHTB', 'indent' => 1],
            ['name' => 'PAJAK MINERAL BUKAN LOGAM & BATUAN', 'indent' => 0],
            ['name' => 'PAJAK BUMI DAN BANGUNAN', 'indent' => 1],
            ['name' => 'Opsen Pajak Kendaraan Bermotor (PKB)', 'indent' => 1],
            ['name' => 'Opsen Bea Balik Nama Kendaraan Bermotor (BBNKB)', 'indent' => 1],
            ['name' => 'PAJAK (PBJT)', 'indent' => 0],
            ['name' => 'PAJAK HOTEL', 'indent' => 1],
            ['name' => 'PAJAK RESTORAN', 'indent' => 1],
            ['name' => 'PAJAK HIBURAN', 'indent' => 1],
            ['name' => 'PAJAK PENERANGAN JALAN', 'indent' => 1],
            ['name' => 'PAJAK PARKIR', 'indent' => 1],
            ['name' => 'PAJAK AIR TANAH', 'indent' => 1],
        ];
            
        $no = 1;
        $grandTotals = [
            'total_ketetapan' => 0,
            'total_bayar' => 0,
        ];

        foreach ($items as $item) {
            $taxType = TaxType::where('name', $item['name'])->first();
            if (!$taxType) continue;

            $prefix = $item['indent'] > 0 ? '- ' : '';
            
            // Collect all relevant tax codes for this item (recursive)
            $allAyatCodes = $this->getAllAyatCodes($taxType);
            
            $rowNo = $item['indent'] == 0 ? $no++ : '';
            $row = [$rowNo, $prefix . $taxType->name];
            
            $totalTypeKet = 0;
            $totalTypeByr = 0;
            
            foreach ($this->upts as $upt) {
                // 1. Ketetapan (from Local Simpadu Snapshot)
                $uptDistrictCodes = $districtCodesByUpt->get($upt->id) ?: [];
                $uptKet = (float) $simpaduTaxPayers->whereIn('kd_kecamatan', $uptDistrictCodes)
                    ->whereIn('ayat', $allAyatCodes)->sum('t_ket');

                // 2. Realisasi (Multi-Source Summation)
                
                // From Simpadu Live
                $uptByrSimpadu = (float) $simpaduLive->whereIn('kd_kecamatan', $uptDistrictCodes)
                    ->whereIn('ayat', $allAyatCodes)->sum('total');

                // From Local Multi-Source (Monthly + Daily)
                // We need to find all descendants IDs for local tables
                $allTypeIds = $this->getAllTaxTypeIds($taxType); // Helper
                $uptDistrictIds = $districtIdsByUpt->get($upt->id) ?: [];
                
                $uptByrLocal = (float) $localMonthly->whereIn('district_id', $uptDistrictIds)
                    ->whereIn('tax_type_id', $allTypeIds)->sum('total');
                
                $uptByrDaily = (float) $localDaily->whereIn('district_id', $uptDistrictIds)
                    ->whereIn('tax_type_id', $allTypeIds)->sum('total');

                $realByr = $uptByrSimpadu + $uptByrLocal + $uptByrDaily;

                // Fallback to local snapshot if all of the above are 0 (Ensures PBB etc stay 0 if no live data found)
                if ($realByr <= 0) {
                    $realByr = (float) $simpaduTaxPayers->whereIn('kd_kecamatan', $uptDistrictCodes)
                        ->whereIn('ayat', $allAyatCodes)->sum('t_byr');
                }
                
                $row[] = $uptKet;
                $row[] = $realByr;
                
                $totalTypeKet += $uptKet;
                $totalTypeByr += $realByr;
            }
            
            $pct = $totalTypeKet > 0 ? round(($totalTypeByr / $totalTypeKet) * 100, 1) : 0;
            $diff = $totalTypeKet - $totalTypeByr;
            
            $row[] = $totalTypeKet;
            $row[] = $totalTypeByr;
            $row[] = $pct . '%';
            $row[] = $diff;
            
            $rows[] = $row;
        }

        // 4. Final Total Row (Global logic to match website's 34.9B total)
        $totalRow = ['', 'TOTAL'];
        foreach ($this->upts as $upt) {
            $uptDistrictCodes = $districtCodesByUpt->get($upt->id) ?: [];
            $uptGlobalStats = $simpaduTaxPayers->whereIn('kd_kecamatan', $uptDistrictCodes);
            
            $uptKet = (float) $uptGlobalStats->sum('t_ket');
            
            // For Total Realization, we sum EVERYTHING from multi-source to match Dashboard's 34.9B Realisasi
            $uptByrSimLive = (float) $simpaduLive->whereIn('kd_kecamatan', $uptDistrictCodes)->sum('total');
            $uptDistrictIds = $districtIdsByUpt->get($upt->id) ?: [];
            $uptByrMonthly = (float) $localMonthly->whereIn('district_id', $uptDistrictIds)->sum('total');
            $uptByrDailyInput = (float) $localDaily->whereIn('district_id', $uptDistrictIds)->sum('total');
            
            $uptByrTotal = $uptByrSimLive + $uptByrMonthly + $uptByrDailyInput;
            
            // Fallback to local snapshot total if live sum is 0 (unlikely given your 34B total exists)
            if ($uptByrTotal <= 0) {
                $uptByrTotal = (float) $uptGlobalStats->sum('t_byr');
            }
            
            $totalRow[] = $uptKet;
            $totalRow[] = $uptByrTotal;
            
            $grandTotals['total_ketetapan'] += $uptKet;
            $grandTotals['total_bayar'] += $uptByrTotal;
        }
        
        $totalPct = $grandTotals['total_ketetapan'] > 0 ? round(($grandTotals['total_bayar'] / $grandTotals['total_ketetapan']) * 100, 1) : 0;
        $totalDiff = $grandTotals['total_ketetapan'] - $grandTotals['total_bayar'];
        
        $totalRow[] = $grandTotals['total_ketetapan'];
        $totalRow[] = $grandTotals['total_bayar'];
        $totalRow[] = $totalPct . '%';
        $totalRow[] = $totalDiff;
        
        $rows[] = $totalRow;

        $this->data = $rows;
        return $rows;
    }

    /**
     * Recursively collect all tax type IDs (including children).
     */
    private function getAllTaxTypeIds(TaxType $taxType): array
    {
        $ids = [$taxType->id];
        foreach ($taxType->children as $child) {
            $ids = array_merge($ids, $this->getAllTaxTypeIds($child));
        }
        return array_unique($ids);
    }

    /**
     * Recursively collect all ayat codes for a tax type.
     */
    private function getAllAyatCodes(TaxType $taxType): array
    {
        $codes = [];
        
        // Prioritize simpadu_code, then fall back to parsing code
        $simpaduCode = $taxType->simpadu_code ?: $taxType->code;
        $code = str_replace('TAX-', '', $simpaduCode);
        $ayat = explode('-', $code)[0];
        
        if ($ayat && (is_numeric($ayat) || $ayat === 'PBJT')) {
            $codes[] = $ayat;
        }
        
        foreach ($taxType->children as $child) {
            $codes = array_merge($codes, $this->getAllAyatCodes($child));
        }
        
        return array_unique($codes);
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 6, 'B' => 45];
        $uptCount = $this->upts->count();
        
        for ($i = 0; $i < $uptCount * 2; $i++) {
            $col = Coordinate::stringFromColumnIndex(3 + $i);
            $widths[$col] = 18;
        }
        
        $offset = 3 + ($uptCount * 2);
        $widths[Coordinate::stringFromColumnIndex($offset)] = 20;
        $widths[Coordinate::stringFromColumnIndex($offset + 1)] = 20;
        $widths[Coordinate::stringFromColumnIndex($offset + 2)] = 12;
        $widths[Coordinate::stringFromColumnIndex($offset + 3)] = 20;
        
        return $widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $uptCount = $this->upts->count();

                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');

                for ($i = 0; $i < $uptCount; $i++) {
                    $startCol = Coordinate::stringFromColumnIndex(3 + ($i * 2));
                    $endCol = Coordinate::stringFromColumnIndex(3 + ($i * 2) + 1);
                    $sheet->mergeCells("{$startCol}1:{$endCol}1");
                }

                $summaryStart = 3 + ($uptCount * 2);
                for ($i = 0; $i < 4; $i++) {
                    $col = Coordinate::stringFromColumnIndex($summaryStart + $i);
                    $sheet->mergeCells("{$col}1:{$col}2");
                }
            },
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $rows = $this->data;
        if (empty($rows)) {
            $rows = $this->array();
        }
        $lastRow = count($rows);
        $lastColIndex = count($rows[0]);
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

        $thinBlack = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']];

        $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => $thinBlack],
        ]);

        $sheet->getStyle("A3:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => $thinBlack],
        ]);

        $currencyFormat = '#,##0';
        $sheet->getStyle("C3:{$lastCol}{$lastRow}")->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle("B3:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        for ($i = 3; $i <= $lastRow; $i++) {
            $uraianVal = $sheet->getCell("B{$i}")->getValue();
            
            // Bold if top-level (not starting with -)
            if (!str_starts_with($uraianVal, '- ') || $uraianVal === 'TOTAL') {
                $sheet->getStyle("A{$i}:{$lastCol}{$i}")->getFont()->setBold(true);
                
                if ($uraianVal === 'TOTAL') {
                    $sheet->getStyle("A{$i}:{$lastCol}{$i}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F1F5F9');
                }
            }
        }

        $sheet->freezePane('C3');
    }
}
