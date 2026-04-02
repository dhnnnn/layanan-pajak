<?php

namespace App\Actions\Simpadu;

use App\Models\SimpaduTaxPayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSimpaduTaxPayersAction
{
    /**
     * Sync Tax Payers from simpadunew to local database.
     */
    public function __invoke(int $year): array
    {
        Log::info("Starting Simpadu WP Sync for year: {$year}");
        $startTime = microtime(true);

        // We use the corrected query from before
        $query = "
            SELECT 
                s.npwpd,
                s.nm_wp,
                o.nop,
                o.name as nm_op,
                o.jalan_op as almt_op,
                o.kd_kecamatan,
                o.JENIS_PAJAK as ayat,
                COALESCE(sums.total_ketetapan, 0) as total_ketetapan,
                COALESCE(sums.total_bayar, 0) as total_bayar,
                (COALESCE(sums.total_ketetapan, 0) - COALESCE(sums.total_bayar, 0)) as total_tunggakan
            FROM dat_objek_pajak o
            JOIN dat_subjek_pajak s ON s.npwpd = o.npwpd
            LEFT JOIN (
                SELECT 
                    r.npwpd, 
                    r.nop,
                    SUM(r.jml_ketetapan) as total_ketetapan,
                    SUM(q.jml_byr_pokok) as total_bayar
                FROM (
                    SELECT npwpd, nop, kohir, jmlsptpd as jml_ketetapan FROM dat_sptpd_at WHERE YEAR(tgldata) = :y1
                    UNION ALL
                    SELECT npwpd, nop, kohir, total as jml_ketetapan FROM dat_sptpd_reklame WHERE YEAR(tgl_ketetapan) = :y2
                    UNION ALL
                    SELECT npwpd, nop, kohir, pajak as jml_ketetapan FROM dat_sptpd_minerba WHERE YEAR(tgldata) = :y3
                    UNION ALL
                    SELECT npwpd, nop, kohir, pajak as jml_ketetapan FROM dat_sptpd_ppj WHERE YEAR(tgldata) = :y4
                    UNION ALL
                    SELECT npwpd, nop, kohir, pajak as jml_ketetapan FROM dat_sptpd_self WHERE YEAR(tgl_data) = :y5
                ) r
                LEFT JOIN pembayaran q ON q.kohir = r.kohir
                GROUP BY r.npwpd, r.nop
            ) sums ON sums.npwpd = o.npwpd AND sums.nop = o.nop
            WHERE 1=1
        ";

        $results = DB::connection('simpadunew')->select($query, [
            'y1' => $year, 'y2' => $year, 'y3' => $year, 'y4' => $year, 'y5' => $year
        ]);

        $count = 0;
        $batchSize = 200;
        $chunks = array_chunk($results, $batchSize);

        foreach ($chunks as $chunk) {
            $dataToUpsert = [];
            foreach ($chunk as $row) {
                $dataToUpsert[] = [
                    'npwpd' => $row->npwpd,
                    'nop' => $row->nop,
                    'year' => $year,
                    'nm_wp' => $row->nm_wp,
                    'nm_op' => $row->nm_op,
                    'almt_op' => $row->almt_op,
                    'kd_kecamatan' => $row->kd_kecamatan,
                    'total_ketetapan' => $row->total_ketetapan,
                    'total_bayar' => $row->total_bayar,
                    'total_tunggakan' => $row->total_tunggakan,
                    'ayat' => $row->ayat,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }

            // Using upsert for performance
            SimpaduTaxPayer::upsert(
                $dataToUpsert, 
                ['npwpd', 'nop', 'year'], 
                ['nm_wp', 'nm_op', 'almt_op', 'kd_kecamatan', 'total_ketetapan', 'total_bayar', 'total_tunggakan', 'ayat', 'updated_at']
            );
            $count += count($chunk);
        }

        $duration = round(microtime(true) - $startTime, 2);
        Log::info("Finished Simpadu WP Sync: {$count} records in {$duration}s");

        return [
            'count' => $count,
            'duration' => $duration
        ];
    }
}
