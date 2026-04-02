<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTaxPayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:tax-payers {--year= : The year to sync (defaults to current year)} {--month= : The specific month to sync (1-12)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync tax payer realization data from SIMPADU to local database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $targetMonth = $this->option('month');

        if ($targetMonth) {
            $this->syncMonth($year, (int) $targetMonth);
        } else {
            $this->info("Memulai sinkronisasi data pajaknya untuk seluruh bulan di tahun: {$year}");
            for ($m = 1; $m <= 12; $m++) {
                $this->syncMonth($year, $m);
            }
        }

        $this->info("Sinkronisasi selesai.");
    }

    private function syncMonth(int $year, int $month): void
    {
        $monthStr = $month < 10 ? "0$month" : (string) $month;
        $period = "{$year}{$monthStr}";
        $this->info("--- Sinkronisasi Bulan: {$month} Tahun: {$year} (Period: {$period}) ---");

        // 1. Detailed SPTPD Reporting Query (Mapping from documentation)
        $sptpdSql = "
            SELECT 
                TRIM(npwpd) as npwpd, TRIM(nop) as nop, 
                MAX(tgl_lapor) as tgl_lapor, 
                MIN(masa) as masa_pajak, 
                SUM(jml_lapor) as total_ketetapan 
            FROM (
                SELECT npwpd, nop, tgl_penetapan as tgl_lapor, DATE_FORMAT(masa_awal, '%Y-%m') as masa, jmlsptpd as jml_lapor FROM dat_sptpd_at      WHERE DATE_FORMAT(masa_awal, '%Y%m') = :p1
                UNION ALL
                SELECT npwpd, nop, tgl_ketetapan as tgl_lapor, DATE_FORMAT(tgl_awal, '%Y-%m')  as masa, total    as jml_lapor FROM dat_sptpd_reklame WHERE DATE_FORMAT(tgl_awal, '%Y%m')  = :p2
                UNION ALL
                SELECT npwpd, nop, tgldata        as tgl_lapor, DATE_FORMAT(masa_awal, '%Y-%m') as masa, pajak    as jml_lapor FROM dat_sptpd_minerba WHERE DATE_FORMAT(masa_awal, '%Y%m') = :p3
                UNION ALL
                SELECT npwpd, nop, tgldata        as tgl_lapor, DATE_FORMAT(masa_awal, '%Y-%m') as masa, pajak    as jml_lapor FROM dat_sptpd_ppj     WHERE DATE_FORMAT(masa_awal, '%Y%m') = :p4
                UNION ALL
                SELECT npwpd, nop, tgl_data       as tgl_lapor, DATE_FORMAT(masa, '%Y-%m')      as masa, pajak    as jml_lapor FROM dat_sptpd_self    WHERE DATE_FORMAT(masa, '%Y%m')      = :p5
            ) x GROUP BY npwpd, nop
        ";

        // 2. Summary Query (Objek Pajak + SPTPD + Payments)
        $sql = "
            SELECT
                TRIM(s.npwpd) as npwpd,
                TRIM(s.nop) as nop,
                s.name as nm_op,
                COALESCE(sj.nm_wp, s.name) as nm_wp,
                s.jalan_op as almt_op,
                s.kd_kecamatan,
                s.status as raw_status,
                s.JENIS_PAJAK as ayat,
                COALESCE(sptpd.tgl_lapor, NULL) as tgl_lapor,
                COALESCE(sptpd.masa_pajak, NULL) as masa_pajak,
                COALESCE(sptpd.total_ketetapan, 0) as total_ketetapan,
                COALESCE(byr.total_bayar, 0) as total_bayar,
                (COALESCE(sptpd.total_ketetapan, 0) - COALESCE(byr.total_bayar, 0)) as total_tunggakan
            FROM dat_objek_pajak s
            LEFT JOIN dat_subjek_pajak sj ON sj.npwpd = s.npwpd
            LEFT JOIN ($sptpdSql) sptpd ON TRIM(sptpd.nop) = TRIM(s.nop) AND TRIM(sptpd.npwpd) = TRIM(s.npwpd)
            LEFT JOIN (
                SELECT nop, npwpd, SUM(jml_byr_pokok + lainlain) AS total_bayar
                FROM pembayaran WHERE YEAR(tgl_bayar) = :y AND MONTH(tgl_bayar) = :m
                GROUP BY nop, npwpd
            ) byr ON TRIM(byr.nop) = TRIM(s.nop) AND TRIM(byr.npwpd) = TRIM(s.npwpd)
            WHERE s.STATUS = '1' 
               OR COALESCE(sptpd.total_ketetapan, 0) > 0 
               OR COALESCE(byr.total_bayar, 0) > 0
        ";

        $results = DB::connection('simpadunew')->select($sql, [
            'p1' => $period, 'p2' => $period, 'p3' => $period, 'p4' => $period, 'p5' => $period,
            'y' => $year, 'm' => $month
        ]);

        $total = count($results);
        $this->info("Berhasil mengambil {$total} data.");

        $chunks = array_chunk($results, 500);
        foreach ($chunks as $chunk) {
            $summaryRows = [];
            $reportRows = [];

            foreach ($chunk as $row) {
                $summaryRows[] = [
                    'npwpd'           => $row->npwpd,
                    'nop'             => $row->nop,
                    'ayat'            => $row->ayat,
                    'year'            => $year,
                    'month'           => $month,
                    'nm_wp'           => $row->nm_wp ?: 'WP TANPA NAMA',
                    'nm_op'           => $row->nm_op ?: 'OP TANPA NAMA',
                    'almt_op'         => $row->almt_op,
                    'kd_kecamatan'    => $row->kd_kecamatan,
                    'status'          => $row->raw_status,
                    'total_ketetapan' => (float) $row->total_ketetapan,
                    'total_bayar'     => (float) $row->total_bayar,
                    'total_tunggakan' => (float) $row->total_tunggakan,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];

                if ($row->total_ketetapan > 0) {
                    $reportRows[] = [
                        'npwpd'      => $row->npwpd,
                        'nop'        => $row->nop,
                        'year'       => $year,
                        'month'      => $month,
                        'tgl_lapor'  => $row->tgl_lapor,
                        'masa_pajak' => $row->masa_pajak,
                        'jml_lapor'  => (float) $row->total_ketetapan,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('simpadu_tax_payers')->upsert($summaryRows, ['npwpd', 'nop', 'year', 'month', 'ayat'], [
                'nm_wp', 'nm_op', 'almt_op', 'kd_kecamatan', 'status', 'total_ketetapan', 'total_bayar', 'total_tunggakan', 'updated_at'
            ]);

            if (!empty($reportRows)) {
                DB::table('simpadu_sptpd_reports')->upsert($reportRows, ['npwpd', 'nop', 'year', 'month'], [
                    'tgl_lapor', 'masa_pajak', 'jml_lapor', 'updated_at'
                ]);
            }
        }
    }
}
