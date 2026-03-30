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

        // Query logic based on DOKUMENTASI_DB_SIMPADUNEW.md
        $query = "
            SELECT 
                r.jp AS ayat,
                s.kd_kecamatan,
                MONTH(r.tgl_lapor) AS bulan,
                SUM(q.jml_byr_pokok) AS total_bayar
            FROM dat_objek_pajak s
            INNER JOIN (
                SELECT '41108' AS jp, ayat, jenis, kelas, npwpd, nop, kohir, tgldata AS tgl_lapor FROM dat_sptpd_at WHERE tgldata BETWEEN :df1 AND :dt1
                UNION ALL
                SELECT '41104' AS jp, ayat, jenis, kelas, npwpd, nop, kohir, tgldata AS tgl_lapor FROM dat_sptpd_reklame WHERE tgl_ketetapan BETWEEN :df2 AND :dt2
                UNION ALL
                SELECT '41111' AS jp, ayat, jenis, kelas, npwpd, nop, kohir, tgldata AS tgl_lapor FROM dat_sptpd_minerba WHERE tgldata BETWEEN :df3 AND :dt3
                UNION ALL
                SELECT '41105' AS jp, ayat, jenis, kelas, npwpd, nop, kohir, tgldata AS tgl_lapor FROM dat_sptpd_ppj WHERE tgldata BETWEEN :df4 AND :dt4
                UNION ALL
                SELECT ayat AS jp, ayat, jenis, kelas, npwpd, nop, kohir, tgl_data AS tgl_lapor FROM dat_sptpd_self WHERE tgl_data BETWEEN :df5 AND :dt5
            ) r ON r.npwpd = s.npwpd AND r.nop = s.nop AND r.jp = s.JENIS_PAJAK
            LEFT JOIN pembayaran q ON q.kohir = r.kohir
            WHERE s.JENIS_PAJAK LIKE :jp_filter
            GROUP BY r.jp, s.kd_kecamatan, bulan
        ";

        return DB::connection('simpadunew')->select($query, [
            'df1' => $dateFrom, 'dt1' => $dateTo,
            'df2' => $dateFrom, 'dt2' => $dateTo,
            'df3' => $dateFrom, 'dt3' => $dateTo,
            'df4' => $dateFrom, 'dt4' => $dateTo,
            'df5' => $dateFrom, 'dt5' => $dateTo,
            'jp_filter' => $jenisPajak
        ]);
    }
}
