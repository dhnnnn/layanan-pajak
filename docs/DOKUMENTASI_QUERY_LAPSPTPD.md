# Dokumentasi Query lapSPTPDwp.php

## Overview

Halaman ini menampilkan **Laporan SPTPD per Masa Pajak** secara dinamis berdasarkan range bulan yang dipilih user. Setiap bulan dalam range ditampilkan sebagai 3 kolom terpisah di tabel.

---

## 1. Perhitungan Range Bulan

```php
$awal  = date($_GET['date_from']."-01");   // contoh: 2026-01-01
$akhir = date($_GET['date_to'])."-01";     // contoh: 2026-05-01

$jmldata = (int)abs((strtotime($akhir) - strtotime($awal)) / (60*60*24*30));
// hasil: 4 → loop dari $x=0 s/d $x=4 = 5 bulan
```

`$jmldata` adalah selisih bulan antara tanggal awal dan akhir. Loop berjalan `$x = 0` sampai `$x <= $jmldata`.

---

## 2. Kolom Dinamis Per Bulan (SELECT)

Untuk setiap bulan, query menambahkan **3 kolom dinamis** ke bagian SELECT:

```php
$loopdate = date("Ym", strtotime($awal." +".$x." month"));
// contoh hasil: 202601, 202602, 202603, 202604, 202605
```

### Kolom yang dihasilkan per bulan:

| Alias Kolom | SQL | Keterangan |
|---|---|---|
| `202601` | `MIN(CASE WHEN DATE_FORMAT(r.masa_awal,'%Y%m') = '202601' THEN r.tgl_lapor END)` | Tanggal SPTPD dilaporkan |
| `masa_202601` | `MIN(CASE WHEN ... THEN DATE_FORMAT(r.masa_awal,'%Y-%m') WHEN DATE_FORMAT(s.date_add,'%Y%m') > '202601' THEN 'Blm Terdaftar' END)` | Masa pajak atau status pendaftaran |
| `jml_202601` | `SUM(CASE WHEN DATE_FORMAT(r.masa_awal,'%Y%m') = '202601' THEN r.jml_lapor ELSE 0 END)` | Jumlah pajak bulan tersebut |

Untuk range **Jan s/d Mei 2026** → menghasilkan **15 kolom dinamis** (5 bulan × 3 kolom).

---

## 3. Sumber Data — UNION ALL 5 Tabel SPTPD

Query menggunakan LEFT JOIN ke subquery yang menggabungkan 5 tabel SPTPD:

| Tabel | Jenis Pajak | Field Masa | Field Tgl Lapor | Field Jumlah |
|---|---|---|---|---|
| `dat_sptpd_at` | Air Tanah | `masa_awal` | `tgl_penetapan` | `jmlsptpd` |
| `dat_sptpd_reklame` | Reklame | `tgl_awal` | `tgl_ketetapan` | `total` |
| `dat_sptpd_minerba` | Minerba / Galian C | `masa_awal` | `tgldata` | `pajak` |
| `dat_sptpd_ppj` | PPJ | `masa_awal` | `tgldata` | `pajak` |
| `dat_sptpd_self` | Hotel, Restoran, Parkir, dll | `masa` | `tgl_data` | `pajak` |

Filter range pada semua tabel:
```sql
CAST(DATE_FORMAT(masa_awal, '%Y%m') AS char) BETWEEN '$datefrom' AND '$dateto'
```

JOIN ke `dat_objek_pajak`:
```sql
LEFT JOIN (...UNION ALL...) r ON r.npwpd = s.npwpd AND r.nop = s.NOP
```

---

## 4. Tabel Utama — Referensi

| Tabel | Alias | Relasi | Keterangan |
|---|---|---|---|
| `dat_objek_pajak` | `s` | — | Data utama objek pajak |
| `dat_subjek_pajak` | `sj` | `sj.npwpd = s.npwpd` | Data wajib pajak |
| `ref_anggaran` | `k` | `k.noayat_ang = s.JENIS_PAJAK` | Nama pendek jenis pajak |
| `ref_kecamatan` | `kec` | `kec.kd_kecamatan = s.kd_kecamatan` | Nama kecamatan |
| `ref_kelurahan` | `kel` | `kel.kd_kecamatan + kd_kelurahan` | Nama kelurahan/desa |

---

## 5. Struktur Header Tabel (2 Baris)

```
┌────┬─────────────┬───────┬─────┬────────────┬────────┬───────────┬──────┬──────────────────────┬──────────────────────┬─────┬────────┐
│ No │ JENIS PAJAK │ NPWPD │ NOP │ NAMA OBJEK │ ALAMAT │ KECAMATAN │ DESA │       Jan 2026        │       Feb 2026        │ ... │ STATUS │
│    │             │       │     │            │        │           │      │ Tgl | Masa Pajak | Jml  │ Tgl | Masa Pajak | Jml │     │        │
└────┴─────────────┴───────┴─────┴────────────┴────────┴───────────┴──────┴──────────────────────┴──────────────────────┴─────┴────────┘
```

- Kolom tetap pakai `rowspan="2"`
- Setiap bulan pakai `colspan="3"` di baris pertama
- Sub-kolom `Tgl SPTPD | Masa Pajak | Jml SPTPD` di baris kedua

---

## 6. Logika Tampilan Per Sel

### Tgl SPTPD
```php
// Format dibalik dari Y-m-d menjadi d-m-Y
echo implode("-", array_reverse(explode("-", $r[$tglCol])));
// Jika kosong → tampil "-"
```

### Masa Pajak
```php
// Jika ada nilai dan bukan 'Blm Terdaftar' → format Indo (Jan 2026)
echo TanggalIndo($r[$masaCol]."-01");
// Jika 'Blm Terdaftar' → tampil apa adanya
// Jika null → tampil "-"
```

### Jumlah SPTPD
```php
echo number_format((float)($r[$jmlCol] ?? 0), 0, ',', '.');
// Selalu tampil angka, default 0 jika null
```

---

## 7. Kondisi "Blm Terdaftar"

Kolom `masa_YYYYMM` akan bernilai `'Blm Terdaftar'` apabila:

```sql
DATE_FORMAT(s.date_add, '%Y%m') > '$loopdate'
```

Artinya objek pajak baru terdaftar **setelah** bulan yang sedang di-loop, sehingga wajar belum ada laporan di bulan tersebut.

---

## 8. Filter Parameter GET

| Parameter | Kolom | Keterangan |
|---|---|---|
| `pajak` | `s.JENIS_PAJAK LIKE '$pajak'` | Kode jenis pajak (41101–41111), `%` = semua |
| `date_from` | Range masa pajak | Format `yyyy-mm` |
| `date_to` | Range masa pajak | Format `yyyy-mm` |
| `kecamatan` | `s.kd_kecamatan LIKE '$kecamatan'` | Kode kecamatan, `%` = semua |
| `sifatwp` | `skpd LIKE '$sifatwp'` (hanya `dat_sptpd_self`) | `T`=Tetap, `Y`=Insidentil, `%`=semua |
| `jeniswp` | Filter di PHP (bukan SQL) | `1`=Semua, `2`=Tidak Lapor, `3`=Lapor |

---

## 9. Bug yang Ditemukan

### Bug 1 — `$Judul` Undefined (line 488)

Variabel `$Judul` hanya di-set untuk kode pajak spesifik, tidak ada fallback untuk `pajak = '%'` (Semua):

```php
// Kondisi saat ini — tidak ada else/default
if ($pajak == '41101') { $Judul = "...Hotel"; }
else if ($pajak == '41102') { $Judul = "...Restoran"; }
// ... dst
// TIDAK ADA: else { $Judul = "Laporan SPTPD per Masa Pajak"; }
```

**Fix:**
```php
else { $Judul = "Laporan SPTPD per Masa Pajak (Semua Jenis)"; }
```

### Bug 2 — Operator Precedence di `TanggalIndo()`

```php
// Salah — ?? hanya berlaku untuk array access, bukan concat
$result = $BulanIndo[(int)$bulan-1] ?? '' . " ". $tahun;

// Benar — tambahkan kurung
$result = ($BulanIndo[(int)$bulan-1] ?? '') . " " . $tahun;
```

---

## 10. Contoh Query Final (Range Jan–Mei 2026)

```sql
SELECT
    s.nop, s.npwpd, s.name, s.jenis_pajak,

    -- Januari 2026
    MIN(CASE WHEN DATE_FORMAT(r.masa_awal,'%Y%m') = '202601' THEN r.tgl_lapor END) AS `202601`,
    MIN(CASE WHEN DATE_FORMAT(r.masa_awal,'%Y%m') = '202601' THEN DATE_FORMAT(r.masa_awal,'%Y-%m')
             WHEN DATE_FORMAT(s.date_add,'%Y%m') > '202601' THEN 'Blm Terdaftar' END) AS `masa_202601`,
    SUM(CASE WHEN DATE_FORMAT(r.masa_awal,'%Y%m') = '202601' THEN r.jml_lapor ELSE 0 END) AS `jml_202601`,

    -- ... kolom Feb s/d Mei dibuat dengan pola yang sama ...

    s.JENIS_PAJAK ayat, s.no_ayat jenis, s.no_urut kelas,
    UC_Words(s.jalan_op) jalan_op, s.kd_kecamatan, s.kd_kelurahan,
    kec.nm_kecamatan, kel.nm_kelurahan, k.nama_pendek, s.STATUS,
    CASE WHEN s.jenis_pajak = '41108' THEN CONCAT('SUMUR ', s.AT) ELSE '-' END AS ket

FROM dat_objek_pajak s
INNER JOIN dat_subjek_pajak sj ON sj.npwpd = s.npwpd
INNER JOIN ref_anggaran k ON k.noayat_ang = s.JENIS_PAJAK AND k.jenis_ang='00' AND k.klas_ang='00' AND k.tahun_ang = YEAR(NOW())
INNER JOIN ref_kecamatan kec ON kec.kd_kecamatan = s.kd_kecamatan
INNER JOIN ref_kelurahan kel ON kel.kd_kecamatan = s.kd_kecamatan AND kel.kd_kelurahan = s.kd_kelurahan
LEFT JOIN (
    SELECT ayat, jenis, kelas, npwpd, nop, kohir, masa_awal, tgl_penetapan AS tgl_lapor, jmlsptpd AS jml_lapor FROM dat_sptpd_at
    WHERE CAST(DATE_FORMAT(masa_awal,'%Y%m') AS char) BETWEEN '202601' AND '202605'
    UNION ALL
    SELECT ayat, jenis, kelas, npwpd, nop, kohir, tgl_awal, tgl_ketetapan, total FROM dat_sptpd_reklame
    WHERE CAST(DATE_FORMAT(tgl_awal,'%Y%m') AS char) BETWEEN '202601' AND '202605'
    UNION ALL
    SELECT ayat, jenis, kelas, npwpd, nop, kohir, masa_awal, tgldata, pajak FROM dat_sptpd_minerba
    WHERE CAST(DATE_FORMAT(masa_awal,'%Y%m') AS char) BETWEEN '202601' AND '202605'
    UNION ALL
    SELECT ayat, jenis, kelas, npwpd, nop, kohir, masa_awal, tgldata, pajak FROM dat_sptpd_ppj
    WHERE CAST(DATE_FORMAT(masa_awal,'%Y%m') AS char) BETWEEN '202601' AND '202605'
    UNION ALL
    SELECT ayat, jenis, kelas, npwpd, nop, kohir, masa, tgl_data, pajak FROM dat_sptpd_self
    WHERE CAST(DATE_FORMAT(masa,'%Y%m') AS char) BETWEEN '202601' AND '202605'
) r ON r.npwpd = s.npwpd AND r.nop = s.NOP

WHERE s.JENIS_PAJAK LIKE '%'
  AND s.kd_kecamatan LIKE '%'

GROUP BY s.nop, s.npwpd, s.name, s.jenis_pajak, s.JENIS_PAJAK, s.no_ayat, s.no_urut,
         s.jalan_op, s.kd_kecamatan, s.kd_kelurahan, kec.nm_kecamatan, kel.nm_kelurahan,
         k.nama_pendek, CASE WHEN s.jenis_pajak = '41108' THEN CONCAT('SUMUR ', s.AT) ELSE '-' END

ORDER BY s.npwpd, s.nop, s.kd_kecamatan, s.kd_kelurahan, s.name;
```
