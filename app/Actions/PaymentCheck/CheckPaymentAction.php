<?php

namespace App\Actions\PaymentCheck;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckPaymentAction
{
    private const TAX_TYPES = [
        'hotel' => ['ayat' => '41101', 'source' => 'self'],
        'restoran' => ['ayat' => '41102', 'source' => 'self'],
        'hiburan' => ['ayat' => '41103', 'source' => 'self'],
        'reklame' => ['ayat' => '41104', 'source' => 'reklame'],
        'ppj' => ['ayat' => '41105', 'source' => 'ppj'],
        'parkir' => ['ayat' => '41107', 'source' => 'self'],
        'at' => ['ayat' => '41108', 'source' => 'at'],
        'minerba' => ['ayat' => '41109', 'source' => 'self'],
        'bphtb' => ['ayat' => '41113', 'source' => 'bphtb'],
    ];

    public function __invoke(string $npwpd, string $tahun, string $jenisPajak, bool $npwpdLama = false, ?string $namaWp = null): array
    {
        $jenisPajak = strtolower($jenisPajak);

        if (! array_key_exists($jenisPajak, self::TAX_TYPES)) {
            return ['error' => 'Jenis pajak tidak valid.'];
        }

        // Verifikasi nama WP — wajib cocok
        $verified = $this->verifyNamaWp($npwpd, $namaWp ?? '', $npwpdLama);
        if (! $verified) {
            return ['error' => 'NPWPD dan nama wajib pajak tidak cocok.'];
        }

        $config = self::TAX_TYPES[$jenisPajak];

        $rows = match ($config['source']) {
            'self' => $this->querySelf($npwpd, $tahun, $config['ayat'], $npwpdLama),
            'reklame' => $this->queryReklame($npwpd, $tahun),
            'ppj' => $this->queryPpj($npwpd, $tahun),
            'at' => $this->queryAirTanah($npwpd, $tahun),
            'bphtb' => $this->queryBphtb($npwpd, $tahun),
            default => collect(),
        };

        if ($rows->isEmpty()) {
            return ['data' => [], 'summary' => null];
        }

        $items = $rows->map(fn ($row) => $this->formatRow((array) $row))->values();

        return [
            'npwpd' => $items->first()['npwpd'],
            'nama_op' => $items->first()['nama_op'],
            'jenis_pajak' => $jenisPajak,
            'tahun' => $tahun,
            'data' => $items,
            'summary' => [
                'total_sptpd' => $items->sum('jml_sptpd'),
                'total_denda' => $items->sum('sisa_denda'),
                'total_bayar_pokok' => $items->sum('bayar_pokok'),
                'total_bayar_denda' => $items->sum('bayar_denda'),
                'total_sisa' => $items->sum('sisa'),
            ],
        ];
    }

    private function verifyNamaWp(string $npwpd, string $namaWp, bool $npwpdLama): bool
    {
        $col = $npwpdLama ? 'OLD_NPWPD' : 'NPWPD';

        $row = DB::connection('simpadunew')->selectOne(
            "SELECT NM_WP FROM dat_subjek_pajak WHERE {$col} = ?",
            [$npwpd]
        );

        if ($row === null) {
            return false;
        }

        $normalizedInput = strtoupper(preg_replace('/\s+/', '', trim($namaWp)));
        $normalizedFull = strtoupper(preg_replace('/\s+/', '', trim($row->NM_WP)));

        // Cocok dengan nama lengkap (termasuk "/")
        if ($normalizedFull === $normalizedInput) {
            return true;
        }

        // Cocok dengan salah satu bagian yang dipisah "/"
        foreach (explode('/', $row->NM_WP) as $part) {
            if (strtoupper(preg_replace('/\s+/', '', trim($part))) === $normalizedInput) {
                return true;
            }
        }

        return false;
    }

    private function formatRow(array $row): array
    {
        return [
            'npwpd' => $row['npwpd'] ?? null,
            'nop' => $row['nop'] ?? null,
            'nama_op' => $row['nama_op'] ?? null,
            'kohir' => $row['kohir'] ?? null,
            'masa_awal' => $row['masa_awal'] ?? null,
            'jatuh_tempo' => $row['jatuh_tempo'] ?? null,
            'tgl_bayar' => ($row['tgl_bayar'] && $row['tgl_bayar'] !== '-') ? $row['tgl_bayar'] : null,
            'jml_sptpd' => (int) ($row['jml_sptpd'] ?? 0),
            'bayar_pokok' => (int) ($row['bayar_pokok'] ?? 0),
            'bayar_denda' => (int) ($row['bayar_denda'] ?? 0),
            'sisa_pokok' => (int) ($row['sisa_pokok'] ?? 0),
            'sisa_denda' => (int) ($row['sisa_denda'] ?? 0),
            'sisa' => (int) ($row['sisa'] ?? 0),
            'keterangan' => $row['ket'] ?? null,
        ];
    }

    /** Hotel, Restoran, Hiburan, Parkir, Minerba */
    private function querySelf(string $npwpd, string $tahun, string $ayat, bool $npwpdLama): Collection
    {
        $npwpdCol = $npwpdLama ? 's.old_npwpd' : 's.npwpd';
        $joinCol = $npwpdLama ? 's.old_NPWPD' : 's.NPWPD';

        return collect(DB::connection('simpadunew')->select("
            SELECT
                {$npwpdCol} AS npwpd,
                s.nop,
                s.`NAME` AS nama_op,
                r.kohir,
                r.masa_awal,
                r.masa_akhir AS jatuh_tempo,
                r.tgl_bayar,
                r.jmlsptpd AS jml_sptpd,
                IFNULL(r.jml_byr_pokok, 0) AS bayar_pokok,
                IFNULL(r.byr_denda, 0) AS bayar_denda,
                IFNULL(r.jmlsptpd - r.jml_byr_pokok, r.jmlsptpd) AS sisa_pokok,
                GREATEST(IFNULL(r.denda, 0) - IFNULL(r.byr_denda, 0), 0) AS sisa_denda,
                GREATEST(
                    IFNULL(r.jmlsptpd - r.jml_byr_pokok, r.jmlsptpd)
                    + IFNULL(r.denda, 0) - IFNULL(r.byr_denda, 0),
                    0
                ) AS sisa,
                r.ket
            FROM dat_objek_pajak s
            INNER JOIN dat_subjek_pajak sp ON sp.NPWPD = {$joinCol}
            INNER JOIN (
                SELECT
                    p.ayat, p.npwpd, p.nop, p.kohir,
                    p.masa AS masa_awal, p.jatuhtempo AS masa_akhir,
                    x.tgl_bayar, p.pajak AS jmlsptpd,
                    x.jml_byr_pokok, x.byr_denda, x.denda, p.ket
                FROM dat_sptpd_self p
                LEFT JOIN (
                    SELECT kohir, ayat,
                        MAX(tgl_bayar) AS tgl_bayar,
                        SUM(IFNULL(jml_byr_pokok, 0)) AS jml_byr_pokok,
                        SUM(IFNULL(denda, 0)) AS denda,
                        SUM(IFNULL(byr_denda, 0)) AS byr_denda
                    FROM pembayaran
                    WHERE NPWPD = ?
                    GROUP BY kohir, ayat
                ) x ON x.kohir = p.kohir AND x.ayat = p.ayat
                WHERE p.NPWPD = ?

                UNION ALL

                SELECT
                    p.ayat, p.npwpd, p.nop, p.no_reg AS kohir,
                    p.masa_ref AS masa_awal, p.jatuh_tempo AS masa_akhir,
                    x.tgl_bayar, p.pokok_pajak_kurang_bayar AS jmlsptpd,
                    x.jml_byr_pokok, x.byr_denda, x.denda, 'SKPDKB' AS ket
                FROM dat_skpdkb p
                LEFT JOIN (
                    SELECT kohir, ayat,
                        MAX(tgl_bayar) AS tgl_bayar,
                        SUM(IFNULL(jml_byr_pokok, 0)) AS jml_byr_pokok,
                        SUM(IFNULL(denda, 0)) AS denda,
                        SUM(IFNULL(byr_denda, 0)) AS byr_denda
                    FROM pembayaran
                    WHERE NPWPD = ?
                    GROUP BY kohir, ayat
                ) x ON x.kohir = p.no_reg AND x.ayat = p.ayat
                WHERE p.NPWPD = ?
            ) r ON r.npwpd = {$npwpdCol} AND r.nop = s.NOP
            WHERE {$npwpdCol} = ?
              AND r.ayat = ?
              AND YEAR(r.masa_awal) = ?
            ORDER BY s.nop, r.masa_awal
        ", [$npwpd, $npwpd, $npwpd, $npwpd, $npwpd, $ayat, $tahun]));
    }

    /** Reklame */
    private function queryReklame(string $npwpd, string $tahun): Collection
    {
        return collect(DB::connection('simpadunew')->select("
            SELECT
                s.npwpd,
                s.nop,
                s.`NAME` AS nama_op,
                r.kohir,
                r.masa_awal,
                r.masa_akhir AS jatuh_tempo,
                r.tgl_bayar,
                r.jmlsptpd AS jml_sptpd,
                IFNULL(r.jml_byr_pokok, 0) AS bayar_pokok,
                IFNULL(r.byr_denda, 0) AS bayar_denda,
                IFNULL(r.jmlsptpd - r.jml_byr_pokok, r.jmlsptpd) AS sisa_pokok,
                GREATEST(IFNULL(r.denda, 0) - IFNULL(r.byr_denda, 0), 0) AS sisa_denda,
                GREATEST(
                    IFNULL(r.jmlsptpd - r.jml_byr_pokok, r.jmlsptpd)
                    + IFNULL(r.denda, 0) - IFNULL(r.byr_denda, 0),
                    0
                ) AS sisa,
                r.ket
            FROM dat_objek_pajak s
            INNER JOIN dat_subjek_pajak sp ON sp.NPWPD = s.NPWPD
            INNER JOIN (
                SELECT
                    p.ayat, p.npwpd, p.nop, p.kohir,
                    p.tgl_awal AS masa_awal, p.tgl_akhirketetapan AS masa_akhir,
                    x.tgl_bayar, p.total AS jmlsptpd,
                    x.jml_byr_pokok, x.byr_denda, x.denda, '-' AS ket
                FROM dat_sptpd_reklame p
                LEFT JOIN (
                    SELECT kohir, ayat,
                        MAX(tgl_bayar) AS tgl_bayar,
                        SUM(IFNULL(jml_byr_pokok, 0)) AS jml_byr_pokok,
                        SUM(IFNULL(denda, 0)) AS denda,
                        SUM(IFNULL(byr_denda, 0)) AS byr_denda
                    FROM pembayaran
                    WHERE NPWPD = ?
                    GROUP BY kohir, ayat
                ) x ON x.kohir = p.kohir AND x.ayat = p.ayat
                WHERE p.NPWPD = ?

                UNION ALL

                SELECT
                    p.ayat, p.npwpd, p.nop, p.no_reg AS kohir,
                    p.masa_ref AS masa_awal, p.jatuh_tempo AS masa_akhir,
                    x.tgl_bayar, p.pajak_yg_harus_dibayar AS jmlsptpd,
                    x.jml_byr_pokok, x.byr_denda, x.denda, 'SKPDKB' AS ket
                FROM dat_skpdkb p
                LEFT JOIN (
                    SELECT kohir, ayat,
                        MAX(tgl_bayar) AS tgl_bayar,
                        SUM(IFNULL(jml_byr_pokok, 0)) AS jml_byr_pokok,
                        SUM(IFNULL(denda, 0)) AS denda,
                        SUM(IFNULL(byr_denda, 0)) AS byr_denda
                    FROM pembayaran
                    WHERE NPWPD = ?
                    GROUP BY kohir, ayat
                ) x ON x.kohir = p.no_reg AND x.ayat = '41104'
                WHERE p.NPWPD = ?
            ) r ON r.npwpd = s.npwpd AND r.nop = s.NOP
            WHERE s.NPWPD = ?
              AND r.ayat = '41104'
              AND YEAR(r.masa_awal) = ?
              AND r.kohir IS NOT NULL
            ORDER BY r.masa_awal
        ", [$npwpd, $npwpd, $npwpd, $npwpd, $npwpd, $tahun]));
    }

    /** PPJ */
    private function queryPpj(string $npwpd, string $tahun): Collection
    {
        return collect(DB::connection('simpadunew')->select("
            SELECT
                s.npwpd,
                s.nop,
                s.`NAME` AS nama_op,
                r.kohir,
                r.masa_awal,
                r.masa_akhir AS jatuh_tempo,
                IFNULL(r.tgl_bayar, '-') AS tgl_bayar,
                r.jmlsptpd AS jml_sptpd,
                IFNULL(r.jml_byr_pokok, 0) AS bayar_pokok,
                IFNULL(r.byr_denda, 0) AS bayar_denda,
                IFNULL(r.jmlsptpd - r.jml_byr_pokok, r.jmlsptpd) AS sisa_pokok,
                GREATEST(IFNULL(r.denda, 0) - IFNULL(r.byr_denda, 0), 0) AS sisa_denda,
                GREATEST(
                    IFNULL(r.jmlsptpd - r.jml_byr_pokok, r.jmlsptpd)
                    + IFNULL(r.denda, 0) - IFNULL(r.byr_denda, 0),
                    0
                ) AS sisa,
                r.ket
            FROM dat_objek_pajak s
            INNER JOIN dat_subjek_pajak sp ON sp.NPWPD = s.NPWPD
            INNER JOIN (
                SELECT
                    p.ayat, p.npwpd, p.nop, p.kohir,
                    p.masa_awal, p.masa_akhir,
                    x.tgl_bayar, p.pajak AS jmlsptpd,
                    x.jml_byr_pokok, x.byr_denda, x.denda, '-' AS ket
                FROM dat_sptpd_ppj p
                LEFT JOIN (
                    SELECT kohir, ayat, tgl_bayar, jml_byr_pokok, denda, byr_denda
                    FROM pembayaran
                    WHERE NPWPD = ?
                ) x ON x.kohir = p.kohir AND x.ayat = p.ayat
                WHERE p.NPWPD = ?

                UNION ALL

                SELECT
                    p.ayat, p.npwpd, p.nop, p.no_reg AS kohir,
                    p.masa_ref AS masa_awal, p.jatuh_tempo AS masa_akhir,
                    x.tgl_bayar, p.pajak_yg_harus_dibayar AS jmlsptpd,
                    x.jml_byr_pokok, x.byr_denda, x.denda, 'SKPDKB' AS ket
                FROM dat_skpdkb p
                LEFT JOIN (
                    SELECT kohir, ayat, tgl_bayar, jml_byr_pokok, denda, byr_denda
                    FROM pembayaran
                    WHERE NPWPD = ?
                ) x ON x.kohir = p.no_reg AND x.ayat = p.ayat
                WHERE p.NPWPD = ?
            ) r ON r.npwpd = s.npwpd AND r.nop = s.NOP
            WHERE s.NPWPD = ?
              AND r.ayat = '41105'
              AND YEAR(r.masa_awal) = ?
            ORDER BY s.npwpd, r.masa_awal
        ", [$npwpd, $npwpd, $npwpd, $npwpd, $npwpd, $tahun]));
    }

    /** Air Tanah */
    private function queryAirTanah(string $npwpd, string $tahun): Collection
    {
        return collect(DB::connection('simpadunew')->select("
            SELECT
                s.npwpd,
                s.nop,
                s.`NAME` AS nama_op,
                r.kohir,
                r.masa_awal,
                r.masa_akhir AS jatuh_tempo,
                IFNULL(r.tgl_bayar, '-') AS tgl_bayar,
                r.jmlsptpd AS jml_sptpd,
                IFNULL(r.jml_byr_pokok, 0) AS bayar_pokok,
                IFNULL(r.byr_denda, 0) AS bayar_denda,
                (r.jmlsptpd - IFNULL(r.jml_byr_pokok, 0)) AS sisa_pokok,
                GREATEST(r.denda - IFNULL(r.byr_denda, 0), 0) AS sisa_denda,
                GREATEST(
                    (r.jmlsptpd - IFNULL(r.jml_byr_pokok, 0))
                    + (r.denda - IFNULL(r.byr_denda, 0)),
                    0
                ) AS sisa,
                r.ket
            FROM dat_objek_pajak s
            INNER JOIN dat_subjek_pajak sp ON sp.NPWPD = s.NPWPD
            INNER JOIN (
                SELECT
                    p.ayat, p.npwpd, p.nop, p.kohir,
                    p.masa_awal, p.tgl_batas AS masa_akhir,
                    pay.tgl_bayar, p.jmlsptpd,
                    pay.jml_byr_pokok, pay.byr_denda, pay.denda,
                    CONCAT('SUMUR ', p.kd_sumur) AS ket
                FROM dat_sptpd_at p
                LEFT JOIN (
                    SELECT kohir, ayat,
                        MAX(tgl_bayar) AS tgl_bayar,
                        SUM(IFNULL(jml_byr_pokok, 0)) AS jml_byr_pokok,
                        SUM(IFNULL(byr_denda, 0)) AS byr_denda,
                        SUM(IFNULL(denda, 0)) AS denda
                    FROM pembayaran
                    WHERE NPWPD = ?
                    GROUP BY kohir, ayat
                ) pay ON pay.kohir = p.kohir AND pay.ayat = p.ayat
                WHERE p.NPWPD = ?

                UNION ALL

                SELECT
                    p.ayat, p.npwpd, p.nop, p.no_reg AS kohir,
                    p.tgl_reg AS masa_awal, p.jatuh_tempo AS masa_akhir,
                    pay.tgl_bayar, p.pajak_yg_harus_dibayar AS jmlsptpd,
                    pay.jml_byr_pokok, pay.byr_denda, pay.denda, 'SKPDKB' AS ket
                FROM dat_skpdkb2 p
                LEFT JOIN (
                    SELECT kohir, ayat,
                        MAX(tgl_bayar) AS tgl_bayar,
                        SUM(IFNULL(jml_byr_pokok, 0)) AS jml_byr_pokok,
                        SUM(IFNULL(byr_denda, 0)) AS byr_denda,
                        SUM(IFNULL(denda, 0)) AS denda
                    FROM pembayaran
                    WHERE NPWPD = ?
                    GROUP BY kohir, ayat
                ) pay ON pay.kohir = p.no_reg AND pay.ayat = p.ayat
                WHERE p.NPWPD = ?
            ) r ON r.npwpd = s.npwpd AND r.nop = s.NOP
            WHERE s.NPWPD = ?
              AND r.ayat = '41108'
              AND YEAR(r.masa_awal) = ?
            ORDER BY s.nop, r.masa_awal, r.ket
        ", [$npwpd, $npwpd, $npwpd, $npwpd, $npwpd, $tahun]));
    }

    /** BPHTB — input berupa no_register (kohir), bukan NPWPD */
    private function queryBphtb(string $noReg, string $tahun): Collection
    {
        return collect(DB::connection('simpadunew')->select("
            SELECT
                t.KOHIR AS npwpd,
                t.nop,
                t.NAMA_WP AS nama_op,
                t.KOHIR AS kohir,
                CONCAT(t.TAHUN_PAJAK, '-01-01') AS masa_awal,
                NULL AS jatuh_tempo,
                p.tgl_bayar,
                t.pajak_bphtb AS jml_sptpd,
                IFNULL(p.jml_byr_pokok, 0) AS bayar_pokok,
                IFNULL(p.byr_denda, 0) AS bayar_denda,
                IFNULL(t.pajak_bphtb, 0) - IFNULL(p.jml_byr_pokok, 0) AS sisa_pokok,
                GREATEST(IFNULL(p.denda, 0) - IFNULL(p.byr_denda, 0), 0) AS sisa_denda,
                GREATEST(
                    IFNULL(t.pajak_bphtb, 0) - IFNULL(p.jml_byr_pokok, 0)
                    + IFNULL(p.denda, 0) - IFNULL(p.byr_denda, 0),
                    0
                ) AS sisa,
                NULL AS ket
            FROM dat_bphtb t
            LEFT JOIN pembayaran p ON t.kohir = p.kohir AND p.ayat = '41113'
            WHERE t.tahun_pajak = ?
              AND t.kohir = ?
        ", [$tahun, $noReg]));
    }
}
