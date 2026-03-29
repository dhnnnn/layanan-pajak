# Dokumentasi Database `simpadunew`
> Untuk keperluan integrasi ke project Laravel

---

## Koneksi Database

```php
host     : localhost
username : root
password : 
database : simpadunew
port     : 3306 (default MySQL)
```

Konfigurasi Laravel di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simpadunew
DB_USERNAME=root
DB_PASSWORD=
```

---

## Tabel-Tabel Utama

### 1. `dat_objek_pajak`
Data objek/tempat usaha wajib pajak.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| nop | varchar | Nomor Objek Pajak (PK) |
| npwpd | varchar | Nomor Pokok Wajib Pajak Daerah |
| name | varchar | Nama objek/tempat usaha |
| jalan_op | varchar | Alamat objek pajak |
| kd_kecamatan | varchar | Kode kecamatan |
| kd_kelurahan | varchar | Kode kelurahan |
| JENIS_PAJAK | varchar | Kode jenis pajak (41101-41111) |
| AT | varchar | Kode sumur (khusus Air Tanah 41108) |

**Jenis Pajak yang tersedia:**
| Kode | Nama | Jumlah Objek |
|------|------|-------------|
| 41101 | Hotel | 303 |
| 41102 | Restoran | 4.182 |
| 41103 | Hiburan | 190 |
| 41104 | Reklame | 6.236 |
| 41105 | PPJ | 530 |
| 41107 | Parkir | 232 |
| 41108 | Air Tanah | 1.638 |
| 41111 | Minerba/Galian C | 172 |

---

### 2. `dat_subjek_pajak`
Data wajib pajak (pemilik/penanggung jawab).

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| npwpd | varchar | Nomor Pokok WP Daerah (PK) |
| nm_wp | varchar | Nama wajib pajak |

---

### 3. `ref_anggaran`
Referensi rekening anggaran / klasifikasi pajak.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| noayat_ang | varchar | Kode rekening (misal: 41101) |
| jenis_ang | varchar | Kode jenis/sub rekening |
| klas_ang | varchar | Kode kelas rekening |
| tahun_ang | varchar | Tahun anggaran |
| nama_pendek | varchar | Nama singkat rekening |
| nama_relasi | varchar | Nama relasi/kategori |
| nama | varchar | Nama lengkap rekening |

**Logika level rekening:**
- `jenis_ang = '00'` + `klas_ang = '00'` → **Induk** (rekening utama)
- `jenis_ang != '00'` + `klas_ang = '00'` → **Sub/Detail** (kategori turunan)

**Subbab per jenis pajak (tahun 2026):**

| Kode | Induk | Sub-kategori |
|------|-------|-------------|
| 41101 | PBJT-Jasa Perhotelan | Bintang 1-5, Melati 1-3, Motel, Cottage, Villa/Losmen |
| 41102 | PBJT-Makanan dan/atau Minuman | Restoran, Rumah Makan, Cafetaria, Kantin, Katering, Warung |
| 41103 | PBJT-Jasa Kesenian dan Hiburan | Bioskop, Karaoke, SPA, Kolam Renang, Pameran, dll (27 sub) |
| 41104 | Pajak Reklame | Papan/Billboard, Kain, Stiker, Selebaran, Berjalan, dll (13 sub) |
| 41105 | PBJT-Tenaga Listrik | PLN, Non PLN |
| 41107 | PBJT-Jasa Parkir | Penitipan Sepeda |
| 41108 | Pajak Air Tanah | Non Niaga, Niaga, Non Industri, Industri, PDAM |
| 41111 | Pajak Mineral bukan Logam | Batu, Andesit, Pasir, Tanah Urug, Tanah Liat, Trass, dll (15 sub) |

---

### 4. `ref_kecamatan`
Referensi 25 kecamatan di Kabupaten Pasuruan.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| KD_PROPINSI | varchar | Kode provinsi (35 = Jawa Timur) |
| KD_DATI2 | varchar | Kode kabupaten (14 = Pasuruan) |
| KD_KECAMATAN | varchar | Kode kecamatan (PK) |
| NM_KECAMATAN | varchar | Nama kecamatan |
| camat | varchar | Nama camat |
| operator | varchar | Nama operator |

**Daftar kecamatan:**
`000-LAINNYA`, `010-PURWODADI`, `020-TUTUR`, `030-PUSPO`, `040-TOSARI`, `050-LUMBANG`, `060-PASREPAN`, `070-KEJAYAN`, `080-WONOREJO`, `090-PURWOSARI`, `100-PRIGEN`, `110-SUKOREJO`, `120-PANDAAN`, `130-GEMPOL`, `140-BEJI`, `150-BANGIL`, `160-REMBANG`, `170-KRATON`, `180-POHJENTREK`, `190-GONDANGWETAN`, `200-REJOSO`, `210-WINONGAN`, `220-GRATI`, `230-LEKOK`, `240-NGULING`

---

### 5. `ref_kelurahan`
Referensi desa/kelurahan.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| KD_KECAMATAN | varchar | Kode kecamatan (FK) |
| KD_KELURAHAN | varchar | Kode kelurahan |
| NM_KELURAHAN | varchar | Nama kelurahan/desa |

---

### 6. `pembayaran`
Data pembayaran pajak.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| kohir | varchar | Nomor kohir/ketetapan (FK) |
| tgl_bayar | date | Tanggal pembayaran |
| jml_byr_pokok | decimal | Jumlah bayar pokok |

---

### 7. Tabel SPTPD (per jenis pajak)

#### `dat_sptpd_self` — Hotel, Restoran, Hiburan, Parkir (41101, 41102, 41103, 41107)
| Kolom | Keterangan |
|-------|------------|
| ayat | Kode jenis pajak |
| jenis | Kode sub rekening |
| kelas | Kode kelas rekening |
| npwpd | Nomor WP |
| nop | Nomor Objek Pajak |
| kohir | Nomor ketetapan |
| masa | Masa pajak |
| tgl_data | Tanggal lapor |
| pajak | Jumlah SPTPD |
| ket | Keterangan |
| petugas | Petugas input |
| nodata | Nomor data |

#### `dat_sptpd_at` — Air Tanah (41108)
| Kolom | Keterangan |
|-------|------------|
| ayat, jenis, kelas | Kode rekening |
| npwpd, nop, kohir | Identitas objek |
| masa_awal | Masa pajak |
| tgldata | Tanggal lapor |
| jmlsptpd | Jumlah SPTPD |
| kd_sumur | Kode sumur |
| petugasinput | Petugas input |
| nodata | Nomor data |

#### `dat_sptpd_reklame` — Reklame (41104)
| Kolom | Keterangan |
|-------|------------|
| ayat, jenis, kelas | Kode rekening |
| npwpd, nop, kohir | Identitas objek |
| tgl_awal | Masa awal |
| tgl_ketetapan | Tanggal ketetapan (dipakai filter) |
| tgldata | Tanggal lapor |
| total | Jumlah SPTPD |
| nama | Nama/materi reklame |
| petugas | Petugas input |
| nodata | Nomor data |

#### `dat_sptpd_ppj` — PPJ (41105)
| Kolom | Keterangan |
|-------|------------|
| ayat, jenis, kelas | Kode rekening |
| npwpd, nop, kohir | Identitas objek |
| masa_awal | Masa pajak |
| tgldata | Tanggal lapor |
| pajak | Jumlah SPTPD |
| jumlah | KVA |
| pemakaian | Jam pemakaian |
| petugasinput | Petugas input |
| nodata | Nomor data |

#### `dat_sptpd_minerba` — Minerba (41111)
| Kolom | Keterangan |
|-------|------------|
| ayat, jenis, kelas | Kode rekening |
| npwpd, nop, kohir | Identitas objek |
| masa_awal | Masa pajak |
| tgldata | Tanggal lapor |
| pajak | Jumlah SPTPD |
| ket | Keterangan |
| petugasinput | Petugas input |
| nodata | Nomor data |

---

## Query Utama — Penerimaan Ketetapan

Query ini digunakan di `penerimaanketetapan.php` untuk mengambil data realisasi penerimaan pajak berdasarkan jenis pajak dan rentang tanggal.

```sql
SELECT DISTINCT
    q.tgl_bayar,
    q.jml_byr_pokok,
    r.kohir,
    s.nop,
    s.npwpd,
    sj.nm_wp,
    s.`name`,
    s.jenis_pajak,
    r.tgl_lapor,
    r.jml_lapor,
    r.masa_awal,
    s.JENIS_PAJAK AS ayat,
    s.no_ayat     AS jenis,
    s.no_urut     AS kelas,
    s.jalan_op,
    s.kd_kecamatan,
    s.kd_kelurahan,
    kec.nm_kecamatan,
    kel.nm_kelurahan,
    k.nama_pendek,
    k.nama_relasi,
    ka.nama AS nama_rekening,
    CASE 
        WHEN s.jenis_pajak = '41108' THEN CONCAT('SUMUR ', s.AT) 
        ELSE r.ket 
    END AS ket,
    r.petugas,
    r.nodata

FROM dat_objek_pajak s
INNER JOIN dat_subjek_pajak sj ON sj.npwpd = s.npwpd
INNER JOIN ref_anggaran k 
    ON k.noayat_ang = s.JENIS_PAJAK
   AND k.jenis_ang  = '00'
   AND k.klas_ang   = '00'
   AND k.tahun_ang  = YEAR(NOW())
INNER JOIN ref_kecamatan kec ON kec.KD_KECAMATAN = s.KD_KECAMATAN
INNER JOIN ref_kelurahan kel 
    ON kel.KD_KECAMATAN = s.KD_KECAMATAN 
   AND kel.KD_KELURAHAN = s.KD_KELURAHAN
INNER JOIN (
    SELECT '41108' AS jp, ayat, jenis, kelas, npwpd, nop, kohir, masa_awal,
           tgldata AS tgl_lapor, jmlsptpd AS jml_lapor,
           CONCAT('SUMUR ', kd_sumur) AS ket, petugasinput AS petugas, nodata
    FROM dat_sptpd_at WHERE tgldata BETWEEN :date_from AND :date_to

    UNION ALL
    SELECT '41104' AS jp, ayat, jenis, kelas, npwpd, nop, kohir, tgl_awal AS masa_awal,
           tgldata AS tgl_lapor, total AS jml_lapor, nama AS ket, petugas, nodata
    FROM dat_sptpd_reklame WHERE tgl_ketetapan BETWEEN :date_from AND :date_to

    UNION ALL
    SELECT '41111' AS jp, ayat, jenis, kelas, npwpd, nop, kohir, masa_awal,
           tgldata AS tgl_lapor, pajak AS jml_lapor, ket, petugasinput, nodata
    FROM dat_sptpd_minerba WHERE tgldata BETWEEN :date_from AND :date_to

    UNION ALL
    SELECT '41105' AS jp, ayat, jenis, kelas, npwpd, nop, kohir, masa_awal,
           tgldata AS tgl_lapor, pajak AS jml_lapor,
           CONCAT('kva: ', jumlah, '/Jam: ', pemakaian), petugasinput, nodata
    FROM dat_sptpd_ppj WHERE tgldata BETWEEN :date_from AND :date_to

    UNION ALL
    SELECT ayat AS jp, ayat, jenis, kelas, npwpd, nop, kohir, masa AS masa_awal,
           tgl_data AS tgl_lapor, pajak AS jml_lapor, ket, petugas, nodata
    FROM dat_sptpd_self WHERE tgl_data BETWEEN :date_from AND :date_to
) r ON r.npwpd = s.npwpd AND r.nop = s.nop AND r.jp = s.JENIS_PAJAK

LEFT JOIN pembayaran q ON q.kohir = r.kohir
LEFT JOIN ref_anggaran ka
    ON ka.noayat_ang = r.ayat
   AND ka.jenis_ang  = r.jenis
   AND ka.klas_ang   = r.kelas
   AND ka.tahun_ang  = YEAR(NOW())
   AND ka.noayat_ang = r.jp

WHERE s.JENIS_PAJAK = :jenis_pajak   -- gunakan '%' untuk semua jenis pajak

ORDER BY r.tgl_lapor, s.kd_kecamatan, s.kd_kelurahan, s.name
```

**Parameter query:**
| Parameter | Contoh | Keterangan |
|-----------|--------|------------|
| `:jenis_pajak` | `41101` | Kode jenis pajak, `%` untuk semua |
| `:date_from` | `2026-01-01` | Tanggal awal filter |
| `:date_to` | `2026-03-31` | Tanggal akhir filter |

---

## Implementasi di Laravel

### Konfigurasi `config/database.php`
```php
'simpadunew' => [
    'driver'    => 'mysql',
    'host'      => env('DB_SIMPADU_HOST', '127.0.0.1'),
    'port'      => env('DB_SIMPADU_PORT', '3306'),
    'database'  => env('DB_SIMPADU_DATABASE', 'simpadunew'),
    'username'  => env('DB_SIMPADU_USERNAME', 'lokal'),
    'password'  => env('DB_SIMPADU_PASSWORD', 'Mojosari879'),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
    'strict'    => false,
],
```

### `.env` tambahan
```env
DB_SIMPADU_HOST=127.0.0.1
DB_SIMPADU_PORT=3306
DB_SIMPADU_DATABASE=simpadunew
DB_SIMPADU_USERNAME=lokal
DB_SIMPADU_PASSWORD=Mojosari879
```

### Contoh penggunaan di Laravel (Query Builder)
```php
use Illuminate\Support\Facades\DB;

$data = DB::connection('simpadunew')
    ->table('dat_objek_pajak as s')
    ->select([
        's.nop', 's.npwpd', 's.name', 's.jalan_op',
        'sj.nm_wp',
        'kec.nm_kecamatan', 'kel.nm_kelurahan',
        'r.kohir', 'r.tgl_lapor', 'r.masa_awal',
        'r.jml_lapor', 'q.jml_byr_pokok', 'q.tgl_bayar',
        'k.nama_pendek', 'k.nama_relasi',
        'ka.nama as nama_rekening',
    ])
    ->join('dat_subjek_pajak as sj', 'sj.npwpd', '=', 's.npwpd')
    ->join('ref_kecamatan as kec', 'kec.KD_KECAMATAN', '=', 's.KD_KECAMATAN')
    ->join('ref_kelurahan as kel', function($join) {
        $join->on('kel.KD_KECAMATAN', '=', 's.KD_KECAMATAN')
             ->on('kel.KD_KELURAHAN', '=', 's.KD_KELURAHAN');
    })
    ->joinSub($sptpdUnion, 'r', function($join) {
        $join->on('r.npwpd', '=', 's.npwpd')
             ->on('r.nop', '=', 's.nop')
             ->on('r.jp', '=', 's.JENIS_PAJAK');
    })
    ->leftJoin('pembayaran as q', 'q.kohir', '=', 'r.kohir')
    ->where('s.JENIS_PAJAK', $jenisPajak)
    ->orderBy('r.tgl_lapor')
    ->get();
```

### Contoh mengambil referensi kecamatan
```php
$kecamatan = DB::connection('simpadunew')
    ->table('ref_kecamatan')
    ->select('KD_KECAMATAN', 'NM_KECAMATAN')
    ->orderBy('KD_KECAMATAN')
    ->get();
```

### Contoh mengambil subbab jenis pajak
```php
// Ambil induk (jenis_ang='00', klas_ang='00')
$induk = DB::connection('simpadunew')
    ->table('ref_anggaran')
    ->where('noayat_ang', $kodeJenisPajak)
    ->where('jenis_ang', '00')
    ->where('klas_ang', '00')
    ->where('tahun_ang', date('Y'))
    ->first();

// Ambil sub-kategori
$sub = DB::connection('simpadunew')
    ->table('ref_anggaran')
    ->where('noayat_ang', $kodeJenisPajak)
    ->where('jenis_ang', '!=', '00')
    ->where('klas_ang', '00')
    ->where('tahun_ang', date('Y'))
    ->get();
```

---

## Catatan Penting

1. **Kolom `jenis` dan `kelas`** di tabel SPTPD menentukan masuk ke subbab mana di `ref_anggaran`
2. **Filter tanggal** berbeda per tabel SPTPD:
   - `dat_sptpd_reklame` → filter pakai `tgl_ketetapan`
   - Tabel lainnya → filter pakai `tgldata` atau `tgl_data`
3. **Merge data kohir** — satu kohir bisa punya banyak baris pembayaran, perlu di-group dan dijumlahkan `jml_byr_pokok`
4. **BPHTB (41112)** dan **PBB P2 (41114)** tidak ada di `dat_objek_pajak`, pakai tabel `dat_bphtb` yang terpisah
5. **Strict mode** sebaiknya dimatikan (`'strict' => false`) karena query lama banyak yang tidak kompatibel dengan strict SQL
