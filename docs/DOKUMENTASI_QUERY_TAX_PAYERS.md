# Query Pengisian Data `simpadu_tax_payers`
> Panduan lengkap cara mengambil data ketetapan, pembayaran, dan tunggakan per WP dari SIMPADU ke Laravel

---

## Masalah yang Terjadi

Tabel `simpadu_tax_payers` di Laravel menampilkan `total_ketetapan = 0` dan `total_bayar = 0`
karena query belum join ke tabel SPTPD yang benar di database `simpadunew`.

---

## Sumber Data yang Benar

| Kolom | Sumber | Keterangan |
|-------|--------|------------|
| `total_ketetapan` | `dat_sptpd_*` (UNION semua tabel) | Pajak yang ditetapkan berdasarkan laporan WP |
| `total_bayar` | `pembayaran` | Yang benar-benar sudah masuk kas |
| `total_tunggakan` | `total_ketetapan - total_bayar` | Sisa yang belum dibayar |

---

## Query Lengkap

```sql
SELECT
    s.npwpd,
    s.nop,
    s.name                              AS nm_op,
    s.jalan_op                          AS almt_op,
    s.kd_kecamatan,
    s.JENIS_PAJAK,

    -- Total ketetapan dari semua tabel SPTPD
    COALESCE(sptpd.total_ketetapan, 0)  AS total_ketetapan,

    -- Total yang sudah dibayar
    COALESCE(byr.total_bayar, 0)        AS total_bayar,

    -- Tunggakan = ketetapan - bayar
    COALESCE(sptpd.total_ketetapan, 0)
        - COALESCE(byr.total_bayar, 0)  AS total_tunggakan

FROM dat_objek_pajak s

-- =============================================
-- KETETAPAN: gabung semua tabel SPTPD
-- =============================================
LEFT JOIN (
    SELECT nop, npwpd, SUM(jml_lapor) AS total_ketetapan
    FROM (
        -- Hotel, Restoran, Hiburan, Parkir
        SELECT nop, npwpd, pajak    AS jml_lapor
        FROM dat_sptpd_self
        WHERE YEAR(tgl_data) = :tahun

        UNION ALL

        -- Air Tanah
        SELECT nop, npwpd, jmlsptpd AS jml_lapor
        FROM dat_sptpd_at
        WHERE YEAR(tgldata) = :tahun

        UNION ALL

        -- Reklame
        SELECT nop, npwpd, total    AS jml_lapor
        FROM dat_sptpd_reklame
        WHERE YEAR(tgldata) = :tahun

        UNION ALL

        -- PPJ
        SELECT nop, npwpd, pajak    AS jml_lapor
        FROM dat_sptpd_ppj
        WHERE YEAR(tgldata) = :tahun

        UNION ALL

        -- Minerba
        SELECT nop, npwpd, pajak    AS jml_lapor
        FROM dat_sptpd_minerba
        WHERE YEAR(tgldata) = :tahun

    ) x
    GROUP BY nop, npwpd
) sptpd ON sptpd.nop = s.nop AND sptpd.npwpd = s.npwpd

-- =============================================
-- PEMBAYARAN: yang sudah masuk kas
-- =============================================
LEFT JOIN (
    SELECT
        nop,
        npwpd,
        SUM(jml_byr_pokok + lainlain) AS total_bayar
    FROM pembayaran
    WHERE YEAR(tgl_bayar) = :tahun
    GROUP BY nop, npwpd
) byr ON byr.nop = s.nop AND byr.npwpd = s.npwpd

-- =============================================
-- FILTER: hanya objek pajak aktif
-- =============================================
WHERE s.STATUS = '1'
  AND s.JENIS_PAJAK IN ('41101','41102','41103','41104','41105','41107','41108','41111')

ORDER BY s.JENIS_PAJAK, s.kd_kecamatan, s.npwpd
```

---

## Implementasi di Laravel

### Opsi 1 — Query langsung (untuk data kecil)

```php
use Illuminate\Support\Facades\DB;

$tahun = 2026;

$sql = "
SELECT
    s.npwpd, s.nop, s.name AS nm_op, s.jalan_op AS almt_op,
    s.kd_kecamatan, s.JENIS_PAJAK,
    COALESCE(sptpd.total_ketetapan, 0) AS total_ketetapan,
    COALESCE(byr.total_bayar, 0)       AS total_bayar,
    COALESCE(sptpd.total_ketetapan, 0) - COALESCE(byr.total_bayar, 0) AS total_tunggakan
FROM dat_objek_pajak s
LEFT JOIN (
    SELECT nop, npwpd, SUM(jml_lapor) AS total_ketetapan FROM (
        SELECT nop, npwpd, pajak    AS jml_lapor FROM dat_sptpd_self    WHERE YEAR(tgl_data) = ?
        UNION ALL
        SELECT nop, npwpd, jmlsptpd AS jml_lapor FROM dat_sptpd_at     WHERE YEAR(tgldata)  = ?
        UNION ALL
        SELECT nop, npwpd, total    AS jml_lapor FROM dat_sptpd_reklame WHERE YEAR(tgldata)  = ?
        UNION ALL
        SELECT nop, npwpd, pajak    AS jml_lapor FROM dat_sptpd_ppj    WHERE YEAR(tgldata)  = ?
        UNION ALL
        SELECT nop, npwpd, pajak    AS jml_lapor FROM dat_sptpd_minerba WHERE YEAR(tgldata) = ?
    ) x GROUP BY nop, npwpd
) sptpd ON sptpd.nop = s.nop AND sptpd.npwpd = s.npwpd
LEFT JOIN (
    SELECT nop, npwpd, SUM(jml_byr_pokok + lainlain) AS total_bayar
    FROM pembayaran WHERE YEAR(tgl_bayar) = ?
    GROUP BY nop, npwpd
) byr ON byr.nop = s.nop AND byr.npwpd = s.npwpd
WHERE s.STATUS = '1'
  AND s.JENIS_PAJAK IN ('41101','41102','41103','41104','41105','41107','41108','41111')
";

$data = DB::connection('simpadunew')
    ->select($sql, [$tahun, $tahun, $tahun, $tahun, $tahun, $tahun]);
```

---

### Opsi 2 — Sync ke tabel lokal (rekomendasi untuk data besar)

Karena data SIMPADU bisa puluhan ribu baris, lebih baik disync ke tabel lokal Laravel
menggunakan **Scheduled Job** yang jalan otomatis tiap malam.

```php
// app/Console/Commands/SyncTaxPayers.php

public function handle(): void
{
    $tahun = now()->year;

    // Ambil dari simpadunew
    $data = DB::connection('simpadunew')->select($sql, [...]);

    // Chunk untuk hindari memory overflow
    $chunks = array_chunk($data, 500);

    foreach ($chunks as $chunk) {
        $rows = array_map(fn($row) => [
            'npwpd'           => $row->npwpd,
            'nop'             => $row->nop,
            'nm_op'           => $row->nm_op,
            'almt_op'         => $row->almt_op,
            'kd_kecamatan'    => $row->kd_kecamatan,
            'total_ketetapan' => $row->total_ketetapan,
            'total_bayar'     => $row->total_bayar,
            'total_tunggakan' => $row->total_tunggakan,
            'updated_at'      => now(),
            'created_at'      => now(),
        ], $chunk);

        DB::table('simpadu_tax_payers')->upsert(
            $rows,
            ['npwpd', 'nop'],           // kolom unik (key)
            [                            // kolom yang diupdate
                'nm_op', 'almt_op', 'kd_kecamatan',
                'total_ketetapan', 'total_bayar',
                'total_tunggakan', 'updated_at',
            ]
        );
    }

    $this->info('Sync selesai: ' . count($data) . ' data diproses.');
}
```

Daftarkan di scheduler:

```php
// app/Console/Kernel.php
$schedule->command('sync:tax-payers')->dailyAt('01:00'); // jalan tiap jam 1 pagi
```

---

## Penjelasan Kolom SPTPD per Tabel

| Tabel | Filter Tanggal | Kolom Ketetapan |
|-------|---------------|----------------|
| `dat_sptpd_self` | `tgl_data` | `pajak` |
| `dat_sptpd_at` | `tgldata` | `jmlsptpd` |
| `dat_sptpd_reklame` | `tgldata` | `total` |
| `dat_sptpd_ppj` | `tgldata` | `pajak` |
| `dat_sptpd_minerba` | `tgldata` | `pajak` |

> Perhatikan: nama kolom tanggal dan kolom nilai berbeda-beda di tiap tabel SPTPD.
> Ini penyebab utama kenapa data bisa 0 kalau salah kolom.

---

## Catatan Penting

1. **Gunakan `COALESCE`** — WP yang belum pernah lapor/bayar akan NULL tanpa ini
2. **`total_tunggakan` bisa negatif** — kalau WP bayar lebih dari ketetapan (lebih bayar)
3. **Minerba (41111)** — di dashboard realisasinya dikali 0.8, tapi untuk data per WP gunakan nilai asli
4. **Reklame** — filter tanggal pakai `tgl_ketetapan` di beberapa query lain, tapi di sini pakai `tgldata` agar konsisten
5. **Sync berkala** — data SIMPADU terus berubah, pastikan sync minimal 1x sehari
