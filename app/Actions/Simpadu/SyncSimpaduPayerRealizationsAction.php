<?php

namespace App\Actions\Simpadu;

use App\Models\SimpaduTaxPayerRealization;
use App\Models\TaxType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSimpaduPayerRealizationsAction
{
    /**
     * Sync realization data per Payer from Simpadu to local database.
     */
    public function __invoke(int $year): void
    {
        Log::info("Starting Simpadu Realization Sync per WP for year: {$year}");

        /**
         * Refining normalization: 
         * 1. Comma Rule: Ignore cents and remove thousand dots.
         * 2. No Comma Rule: Treat as 1:1 numeric (decimal).
         * 3. Aggregation: Sum results and floor to ignore cents.
         */
        $query = "
            SELECT
                COALESCE(NULLIF(p.npwpd, ''), obj.npwpd) as resolved_npwpd,
                sj.nm_wp,
                obj.kd_kecamatan,
                p.ayat,
                p.jenis,
                p.kelas,
                SUM(
                    (CASE 
                        WHEN p.jml_byr_pokok LIKE '%,%' THEN CAST(REPLACE(SUBSTRING_INDEX(p.jml_byr_pokok, ',', 1), '.', '') AS DECIMAL(20,2))
                        ELSE CAST(p.jml_byr_pokok AS DECIMAL(20,2))
                    END) + 
                    (CASE 
                        WHEN p.lainlain LIKE '%,%' THEN CAST(REPLACE(SUBSTRING_INDEX(p.lainlain, ',', 1), '.', '') AS DECIMAL(20,2))
                        ELSE CAST(p.lainlain AS DECIMAL(20,2))
                    END)
                ) AS total_realisasi
            FROM pembayaran p
            LEFT JOIN (
                SELECT nop, npwpd, MAX(kd_kecamatan) as kd_kecamatan 
                FROM dat_objek_pajak 
                GROUP BY nop, npwpd
            ) obj ON obj.nop = p.nop
            LEFT JOIN dat_subjek_pajak sj ON sj.npwpd = COALESCE(NULLIF(p.npwpd, ''), obj.npwpd)
            WHERE YEAR(p.tgl_bayar) = :year
            GROUP BY resolved_npwpd, sj.nm_wp, obj.kd_kecamatan, p.ayat, p.jenis, p.kelas
        ";

        $results = DB::connection('simpadunew')->select($query, ['year' => $year]);

        // Map Simpadu records to our local Tax Types
        $taxTypes = TaxType::whereNotNull('simpadu_code')->get();
        $syncTime = now();

        DB::transaction(function () use ($results, $taxTypes, $year, $syncTime) {
            foreach ($results as $row) {
                $ayat = (string) $row->ayat;
                $jenis = str_pad((string) $row->jenis, 2, '0', STR_PAD_LEFT);
                
                // Try full code (ayat-jenis)
                $code = "{$ayat}-{$jenis}";
                $taxType = $taxTypes->where('simpadu_code', $code)->first();
                
                // Fallback to ayat only if not found (for single-ayat types)
                if (!$taxType) {
                    $taxType = $taxTypes->where('simpadu_code', $ayat)->first();
                }

                if ($taxType) {
                    SimpaduTaxPayerRealization::updateOrCreate(
                        [
                            'tax_type_id' => $taxType->id,
                            'year' => $year,
                            'npwpd' => $row->resolved_npwpd ?: 'PBB-NON-NPWPD',
                            'kd_kecamatan' => $row->kd_kecamatan,
                        ],
                        [
                            'nm_wp' => $row->nm_wp ?: 'WAJIB PAJAK PBB (NON-NPWPD)',
                            'total_realization' => floor($row->total_realisasi), // Floors cents
                            'last_sync_at' => $syncTime,
                        ]
                    );
                }
            }
        });

        Log::info("Finished Simpadu Realization Sync for " . count($results) . " WP records.");
    }
}
