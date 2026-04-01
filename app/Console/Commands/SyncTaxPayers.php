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
    protected $signature = 'sync:tax-payers {--year= : The year to sync (defaults to current year)}';

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
        $year = $this->option('year') ?: now()->year;
        $this->info("Memulai sinkronisasi data pajaknya untuk tahun: {$year}");

        // OFFICIAL QUERY FROM DOKUMENTASI_QUERY_TAX_PAYERS.md
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
                COALESCE(sptpd.total_ketetapan, 0) as total_ketetapan,
                COALESCE(byr.total_bayar, 0) as total_bayar,
                (COALESCE(sptpd.total_ketetapan, 0) - COALESCE(byr.total_bayar, 0)) as total_tunggakan
            FROM dat_objek_pajak s
            LEFT JOIN dat_subjek_pajak sj ON sj.npwpd = s.npwpd
            
            -- KETETAPAN: gabung semua tabel SPTPD (dengan filter tahun sesuai permintaan user)
            LEFT JOIN (
                SELECT nop, npwpd, SUM(jml_lapor) AS total_ketetapan FROM (
                    SELECT nop, npwpd, pajak    AS jml_lapor FROM dat_sptpd_self    WHERE YEAR(tgl_data) = :y1
                    UNION ALL
                    SELECT nop, npwpd, jmlsptpd AS jml_lapor FROM dat_sptpd_at      WHERE YEAR(tgldata)  = :y2
                    UNION ALL
                    SELECT nop, npwpd, total    AS jml_lapor FROM dat_sptpd_reklame WHERE YEAR(tgl_ketetapan) = :y3
                    UNION ALL
                    SELECT nop, npwpd, pajak    AS jml_lapor FROM dat_sptpd_ppj     WHERE YEAR(tgldata)  = :y4
                    UNION ALL
                    SELECT nop, npwpd, pajak    AS jml_lapor FROM dat_sptpd_minerba WHERE YEAR(tgldata) = :y5
                ) x GROUP BY nop, npwpd
            ) sptpd ON TRIM(sptpd.nop) = TRIM(s.nop) AND TRIM(sptpd.npwpd) = TRIM(s.npwpd)
            
            -- PEMBAYARAN: yang sudah masuk kas
            LEFT JOIN (
                SELECT nop, npwpd, SUM(jml_byr_pokok + lainlain) AS total_bayar
                FROM pembayaran WHERE YEAR(tgl_bayar) = :y6
                GROUP BY nop, npwpd
            ) byr ON TRIM(byr.nop) = TRIM(s.nop) AND TRIM(byr.npwpd) = TRIM(s.npwpd)
            
            -- Ambil hanya yang aktif atau yang punya data realisasi/ketetapan
            WHERE s.STATUS = '1' 
               OR COALESCE(sptpd.total_ketetapan, 0) > 0 
               OR COALESCE(byr.total_bayar, 0) > 0
        ";

        $params = [
            'y1' => $year,
            'y2' => $year,
            'y3' => $year,
            'y4' => $year,
            'y5' => $year,
            'y6' => $year,
        ];

        $this->info("Menjalankan query ke SIMPADU...");
        $results = DB::connection('simpadunew')->select($sql, $params);
        $total = count($results);
        $this->info("Berhasil mengambil {$total} data dari SIMPADU.");

        $chunks = array_chunk($results, 500);
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($chunks as $chunk) {
            $rows = array_map(fn($row) => [
                'npwpd'           => $row->npwpd,
                'nop'             => $row->nop,
                'ayat'            => $row->ayat,
                'year'            => (int) $year,
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
            ], $chunk);

            DB::table('simpadu_tax_payers')->upsert(
                $rows,
                ['npwpd', 'nop', 'year', 'ayat'], // Key unik baru
                [ // Kolom yang diperbaharui jika ada duplikat
                    'nm_wp', 
                    'nm_op', 
                    'almt_op', 
                    'kd_kecamatan',
                    'status',
                    'total_ketetapan', 
                    'total_bayar', 
                    'total_tunggakan', 
                    'updated_at'
                ]
            );

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Sinkronisasi selesai.");
    }
}
