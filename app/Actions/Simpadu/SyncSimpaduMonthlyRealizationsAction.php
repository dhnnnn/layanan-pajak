<?php

namespace App\Actions\Simpadu;

use App\Models\SimpaduMonthlyRealization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSimpaduMonthlyRealizationsAction
{
    /**
     * Sync realization data per ayat per kecamatan per month from simpadunew.
     * Uses direct cash-basis query from pembayaran table (covers ALL tax types
     * including PBB, BPHTB, PKB, BBNKB that are not in dat_sptpd_* tables).
     */
    public function __invoke(int $year): array
    {
        Log::info("Starting Simpadu Monthly Realization Sync for year: {$year}");
        $startTime = microtime(true);

        $results = DB::connection('simpadunew')->select("
            SELECT
                p.ayat,
                MONTH(p.tgl_bayar) AS bulan,
                SUM(
                    CASE
                        WHEN p.ayat = '41111'
                            THEN (p.jml_byr_pokok + p.lainlain) * 0.8
                        ELSE p.jml_byr_pokok + p.lainlain
                    END
                ) AS total_bayar
            FROM pembayaran p
            WHERE YEAR(p.tgl_bayar) = :year
              AND p.ayat IS NOT NULL
              AND p.ayat != ''
            GROUP BY p.ayat, bulan
        ", ['year' => $year]);

        $syncTime = now();
        $count = 0;

        foreach (array_chunk($results, 500) as $chunk) {
            $rows = [];
            foreach ($chunk as $row) {
                $rows[] = [
                    'year' => $year,
                    'ayat' => (string) $row->ayat,
                    'kd_kecamatan' => null,
                    'month' => (int) $row->bulan,
                    'total_bayar' => floor((float) $row->total_bayar),
                    'synced_at' => $syncTime,
                    'created_at' => $syncTime,
                    'updated_at' => $syncTime,
                ];
            }

            SimpaduMonthlyRealization::upsert(
                $rows,
                ['year', 'ayat', 'kd_kecamatan', 'month'],
                ['total_bayar', 'synced_at', 'updated_at']
            );

            $count += count($chunk);
        }

        $duration = round(microtime(true) - $startTime, 2);
        Log::info("Finished Simpadu Monthly Realization Sync: {$count} records in {$duration}s");

        return ['count' => $count, 'duration' => $duration];
    }
}
