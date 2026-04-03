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

        /**
         * Query notes (Kohir/Accrual Basis — for WP compliance reporting):
         * - total_ketetapan = SUM of SPTPD whose tax period (masa pajak) falls in the given year.
         * - total_bayar     = SUM of payments for kohir whose masa pajak is in the given year,
         *                     regardless of when the payment was actually made (tgl_bayar).
         *                     This avoids inflating total_bayar with payments for old-year arrears.
         * - total_bayar is capped at total_ketetapan (LEAST) so it never exceeds the assessment.
         *   Overpayment is not meaningful for compliance reporting and causes confusing negative tunggakan.
         * - total_tunggakan = total_ketetapan - total_bayar (always >= 0).
         * - All amount columns are decimal(16,2) — no comma-format handling needed.
         * - Date fields used are masa pajak (not tgl_input):
         *   - dat_sptpd_minerba: masa_awal (not tgldata)
         *   - dat_sptpd_ppj:     masa_awal (not tgldata)
         *   - dat_sptpd_self:    masa      (not tgl_data)
         * - Group by kohir to deduplicate revised assessments (only latest value per kohir counted).
         */
        $query = "
            SELECT 
                s.npwpd,
                s.nm_wp,
                o.nop,
                o.nm_op,
                o.almt_op,
                o.kd_kecamatan,
                o.ayat,
                o.status,
                COALESCE(sptpd.total_ketetapan, 0) as total_ketetapan,
                -- Cap total_bayar at total_ketetapan: overpayment is not meaningful here
                LEAST(COALESCE(pay.total_bayar, 0), COALESCE(sptpd.total_ketetapan, 0)) as total_bayar,
                GREATEST(COALESCE(sptpd.total_ketetapan, 0) - COALESCE(pay.total_bayar, 0), 0) as total_tunggakan
            FROM (
                SELECT 
                    npwpd, nop, 
                    MAX(name) as nm_op, 
                    MAX(jalan_op) as almt_op, 
                    MAX(kd_kecamatan) as kd_kecamatan,
                    MAX(JENIS_PAJAK) as ayat,
                    MAX(status) as status
                FROM dat_objek_pajak 
                GROUP BY npwpd, nop
            ) o
            JOIN dat_subjek_pajak s ON s.npwpd = o.npwpd
            LEFT JOIN (
                SELECT 
                    r_all.npwpd, 
                    r_all.nop,
                    SUM(r_all.jml_lapor) as total_ketetapan,
                    -- Collect all kohir for this npwpd+nop so we can match payments
                    GROUP_CONCAT(r_all.kohir) as kohir_list
                FROM (
                    -- Group by kohir: one assessment only counts once (avoids double-counting revisions)
                    SELECT npwpd, nop, kohir, MAX(jmlsptpd) as jml_lapor FROM dat_sptpd_at      WHERE YEAR(masa_awal) = :y1 GROUP BY npwpd, nop, kohir
                    UNION ALL
                    SELECT npwpd, nop, kohir, MAX(total)    as jml_lapor FROM dat_sptpd_reklame WHERE YEAR(tgl_awal)  = :y2 GROUP BY npwpd, nop, kohir
                    UNION ALL
                    SELECT npwpd, nop, kohir, MAX(pajak)    as jml_lapor FROM dat_sptpd_minerba WHERE YEAR(masa_awal) = :y3 GROUP BY npwpd, nop, kohir
                    UNION ALL
                    SELECT npwpd, nop, kohir, MAX(pajak)    as jml_lapor FROM dat_sptpd_ppj     WHERE YEAR(masa_awal) = :y4 GROUP BY npwpd, nop, kohir
                    UNION ALL
                    SELECT npwpd, nop, kohir, MAX(pajak)    as jml_lapor FROM dat_sptpd_self    WHERE YEAR(masa)      = :y5 GROUP BY npwpd, nop, kohir
                ) r_all
                GROUP BY r_all.npwpd, r_all.nop
            ) sptpd ON sptpd.npwpd = o.npwpd AND sptpd.nop = o.nop
            LEFT JOIN (
                -- Payments matched by kohir to masa pajak year (not by tgl_bayar year)
                -- This ensures we only count payments for THIS year's assessments,
                -- regardless of when the WP actually paid (could be next year).
                SELECT 
                    p.npwpd,
                    p.nop,
                    SUM(p.jml_byr_pokok) as total_bayar
                FROM pembayaran p
                INNER JOIN (
                    SELECT kohir FROM dat_sptpd_at      WHERE YEAR(masa_awal) = :yp1
                    UNION
                    SELECT kohir FROM dat_sptpd_reklame WHERE YEAR(tgl_awal)  = :yp2
                    UNION
                    SELECT kohir FROM dat_sptpd_minerba WHERE YEAR(masa_awal) = :yp3
                    UNION
                    SELECT kohir FROM dat_sptpd_ppj     WHERE YEAR(masa_awal) = :yp4
                    UNION
                    SELECT kohir FROM dat_sptpd_self    WHERE YEAR(masa)      = :yp5
                ) valid_kohir ON valid_kohir.kohir = p.kohir
                GROUP BY p.npwpd, p.nop
            ) pay ON pay.npwpd = o.npwpd AND pay.nop = o.nop
        ";
 
        $results = DB::connection('simpadunew')->select($query, [
            'y1' => $year, 'y2' => $year, 'y3' => $year, 'y4' => $year, 'y5' => $year,
            'yp1' => $year, 'yp2' => $year, 'yp3' => $year, 'yp4' => $year, 'yp5' => $year,
        ]);

        $count = 0;
        $batchSize = 250;
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
                    'total_ketetapan' => floor($row->total_ketetapan), // Ignore decimals/cents as requested
                    'total_bayar' => floor($row->total_bayar),         // Ignore decimals/cents as requested
                    'total_tunggakan' => floor($row->total_tunggakan),
                    'ayat' => $row->ayat,
                    'month' => 0,
                    'status' => $row->status,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }

            // Using upsert for performance
            SimpaduTaxPayer::upsert(
                $dataToUpsert, 
                ['npwpd', 'nop', 'year'], 
                ['nm_wp', 'nm_op', 'almt_op', 'kd_kecamatan', 'total_ketetapan', 'total_bayar', 'total_tunggakan', 'ayat', 'month', 'status', 'updated_at']
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
