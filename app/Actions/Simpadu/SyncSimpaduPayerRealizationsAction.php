<?php

namespace App\Actions\Simpadu;

use App\Models\SimpaduTaxPayerRealization;
use App\Models\TaxType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncSimpaduPayerRealizationsAction
{
    /**
     * Sync realization data per Payer from Simpadu to local database.
     */
    public function __invoke(int $year): void
    {
        // SQL from user: Get summary per Payer (NPWPD) and District (kd_kecamatan)
        $query = "
            SELECT
                p.npwpd,
                sj.nm_wp,
                obj.kd_kecamatan,
                p.ayat,
                p.jenis,
                p.kelas,
                SUM(p.jml_byr_pokok + p.lainlain) AS total_realisasi
            FROM pembayaran p
            JOIN dat_subjek_pajak sj ON sj.npwpd = p.npwpd
            LEFT JOIN dat_objek_pajak obj ON obj.nop = p.nop
            WHERE YEAR(p.tgl_bayar) = :year
            GROUP BY p.npwpd, sj.nm_wp, obj.kd_kecamatan, p.ayat, p.jenis, p.kelas
        ";

        $results = DB::connection('simpadunew')->select($query, ['year' => $year]);

        // Map Simpadu records to our local Tax Types
        // We use simpadu_code (e.g., '41101-01' or '41121')
        $taxTypes = TaxType::whereNotNull('simpadu_code')->get();
        
        // Prepare local data for batch processing or UPSERT
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
                            'npwpd' => $row->npwpd,
                            'kd_kecamatan' => $row->kd_kecamatan,
                        ],
                        [
                            'nm_wp' => $row->nm_wp,
                            'total_realization' => (float) $row->total_realisasi,
                            'last_sync_at' => $syncTime,
                        ]
                    );
                }
            }
        });
    }
}
