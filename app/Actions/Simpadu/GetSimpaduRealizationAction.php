<?php

namespace App\Actions\Simpadu;

use Illuminate\Support\Facades\DB;

class GetSimpaduRealizationAction
{
    /**
     * Fetch realization data from simpadunew database.
     * 
     * @param int $year
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param string|null $jenisPajak
     * @return \Illuminate\Support\Collection
     */
    public function __invoke(int $year, ?string $dateFrom = null, ?string $dateTo = null, ?string $jenisPajak = '%')
    {
        $dateFrom = $dateFrom ?: "{$year}-01-01";
        $dateTo = $dateTo ?: "{$year}-12-31";

        // Optimized Cash Basis Query: Drive directly from 'pembayaran' table
        // Formula: SUM(jml_byr_pokok + lainlain)
        $query = "
            SELECT 
                q.ayat,
                q.jenis,
                q.kelas,
                s.kd_kecamatan,
                MONTH(q.tgl_bayar) AS bulan,
                SUM(q.jml_byr_pokok + q.lainlain) AS total_bayar
            FROM pembayaran q 
            LEFT JOIN dat_objek_pajak s ON s.nop = q.nop
            WHERE q.tgl_bayar BETWEEN :df AND :dt
              AND q.ayat LIKE :jp_filter
            GROUP BY q.ayat, q.jenis, q.kelas, s.kd_kecamatan, bulan
        ";

        return DB::connection('simpadunew')->select($query, [
            'df' => $dateFrom, 
            'dt' => $dateTo,
            'jp_filter' => $jenisPajak
        ]);
    }
}
