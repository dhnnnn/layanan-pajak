# Konsep Dasar Pajak Daerah — Kabupaten Pasuruan
> Panduan untuk memahami alur data di sistem SIMPADU sebelum membangun aplikasi Laravel

---

## 1. Siapa Saja yang Terlibat?

### Wajib Pajak (WP)
Orang atau badan usaha yang wajib membayar pajak daerah.
Contoh: pemilik warung makan, hotel, tempat parkir, pengguna air tanah.

- Diidentifikasi dengan **NPWPD** (Nomor Pokok Wajib Pajak Daerah)
- Data tersimpan di tabel `dat_subjek_pajak`

### Objek Pajak
Tempat usaha atau aset yang dikenai pajak.
Contoh: satu warung makan = satu objek pajak.

- Diidentifikasi dengan **NOP** (Nomor Objek Pajak)
- Data tersimpan di tabel `dat_objek_pajak`
- Satu WP bisa punya banyak objek pajak

> Contoh: Bu Siti punya 3 warung makan → 1 NPWPD, 3 NOP berbeda

### Petugas BAPENDA/BPKPD
Yang menginput data SPTPD ke sistem. WP tidak input sendiri (kecuali lewat eSPTPD).

---

## 2. Jenis Pajak yang Ada

| Kode | Nama Pajak | Jumlah Objek |
|------|-----------|-------------|
| 41101 | Hotel | 303 |
| 41102 | Restoran | 4.182 |
| 41103 | Hiburan | 190 |
| 41104 | Reklame | 6.236 |
| 41105 | PPJ (Penerangan Jalan) | 530 |
| 41107 | Parkir | 232 |
| 41108 | Air Tanah | 1.638 |
| 41111 | Minerba / Galian C | 172 |
| 41112 | PBB P2 | — |
| 41113 | BPHTB | — |

Setiap jenis pajak punya **sub-kategori (subbab)**, contoh untuk Hotel:
- Bintang Lima, Bintang Empat, Bintang Tiga, Bintang Dua, Bintang Satu
- Melati Tiga, Melati Satu
- Motel, Cottage, Villa/Losmen/Penginapan/Rumah Kost

Sub-kategori ini tersimpan di tabel `ref_anggaran` dengan kombinasi kolom `jenis_ang` dan `klas_ang`.

---

## 3. Tiga Angka Penting yang Harus Dipahami

```
TARGET APBD  →  JUMLAH SPTPD  →  JUMLAH BAYAR
(perencanaan)    (ketetapan)       (realisasi)
```

### Target APBD
- Angka yang **direncanakan** akan terkumpul dalam satu tahun
- Ditetapkan di awal tahun oleh pemerintah daerah bersama DPRD
- Contoh: target pajak restoran 2026 = **Rp 41,3 Miliar**
- Tersimpan di tabel `m_target_anggaran`
- Dibagi per tribulan (triwulan): Tribulan 1 (Jan-Mar), 2 (Apr-Jun), 3 (Jul-Sep), 4 (Okt-Des)

### Jumlah SPTPD (Surat Pemberitahuan Pajak Daerah)
- Pajak yang **ditetapkan** berdasarkan laporan omzet WP per masa pajak
- Dihitung dari: omzet × tarif pajak
- Contoh: Warung Bu Siti omzet Januari Rp 1.500.000 × tarif 10% = **Rp 150.000**
- Tersimpan di tabel `dat_sptpd_self`, `dat_sptpd_at`, dll (tergantung jenis pajak)
- Setelah SPTPD diinput, sistem menerbitkan **KOHIR** (nomor ketetapan pajak)

### Jumlah Bayar (Realisasi)
- Uang yang **benar-benar masuk** ke kas daerah
- Bisa sama dengan SPTPD, bisa kurang kalau WP belum lunas
- Tersimpan di tabel `pembayaran`, kolom `jml_byr_pokok + lainlain`
- Realisasi total = SUM semua pembayaran dalam periode tertentu

### Perbandingan Ketiganya

| | Target APBD | Jumlah SPTPD | Jumlah Bayar |
|--|------------|-------------|-------------|
| Artinya | Harapan | Kewajiban WP | Kenyataan |
| Level | Seluruh kabupaten | Per WP per bulan | Per WP per transaksi |
| Dibuat oleh | Pemerintah + DPRD | Petugas BAPENDA | Bank/loket/QRIS |
| Tabel | `m_target_anggaran` | `dat_sptpd_*` | `pembayaran` |

---

## 4. Alur Lengkap dari Lapor sampai Realisasi

```
1. WP datang ke kantor BAPENDA (atau lapor via eSPTPD)
   └── Melaporkan omzet bulan ini

2. Petugas input SPTPD ke sistem
   └── Sistem hitung pajak terutang
   └── Terbit KOHIR (nomor ketetapan)
   └── Data masuk ke dat_sptpd_self / dat_sptpd_at / dll

3. WP menerima tagihan (KOHIR)
   └── Bayar ke bank / loket / QRIS / Virtual Account

4. Data pembayaran masuk ke tabel `pembayaran`
   └── Linked ke KOHIR

5. Realisasi otomatis terakumulasi
   └── Dashboard SUM dari tabel pembayaran
   └── Dibandingkan dengan Target APBD
```

---

## 5. Cara Menghitung Realisasi

Realisasi diambil dari tabel `pembayaran`:

```sql
SELECT
    ayat,                                    -- kode jenis pajak
    SUM(jml_byr_pokok + lainlain) AS realisasi
FROM pembayaran
WHERE YEAR(tgl_bayar) = 2026
GROUP BY ayat
```

Khusus **Minerba (41111)**, realisasinya dikalikan 0.8 (80%) karena ada bagi hasil ke pemerintah pusat.

---

## 6. Kolom Penting di Tabel `pembayaran`

| Kolom | Keterangan |
|-------|------------|
| `npwpd` | Siapa WP-nya |
| `nop` | Objek pajak mana |
| `ayat` | Kode jenis pajak (41101, 41102, dst) |
| `jenis` | Kode subbab |
| `kelas` | Kode kelas |
| `kohir` | Nomor ketetapan (link ke SPTPD) |
| `jml_pokok` | Pajak pokok yang seharusnya dibayar |
| `jml_byr_pokok` | Yang benar-benar dibayar (pokok) |
| `lainlain` | Pembayaran lain-lain (sanksi, bunga) |
| `byr_denda` | Denda yang dibayar |
| `kurangbyr` | Sisa yang belum dibayar |
| `tgl_bayar` | Tanggal pembayaran |
| `lokasibyr` | Tempat bayar (BANK, QRIS, dll) |

---

## 7. Target Per Kecamatan

Saat ini **tidak ada target per kecamatan** di database SIMPADU.
Target hanya ada di level jenis pajak (kabupaten keseluruhan).

Namun **realisasi per kecamatan BISA dihitung** karena:
- `dat_objek_pajak` punya kolom `kd_kecamatan`
- `pembayaran` bisa di-join ke `dat_objek_pajak` via `nop + npwpd`

Ada 25 kecamatan di Kabupaten Pasuruan:
`PURWODADI, TUTUR, PUSPO, TOSARI, LUMBANG, PASREPAN, KEJAYAN, WONOREJO, PURWOSARI, PRIGEN, SUKOREJO, PANDAAN, GEMPOL, BEJI, BANGIL, REMBANG, KRATON, POHJENTREK, GONDANGWETAN, REJOSO, WINONGAN, GRATI, LEKOK, NGULING`

Untuk fitur tracking target vs realisasi per kecamatan di Laravel,
perlu dibuat tabel baru `m_target_kecamatan` — lihat file `DOKUMENTASI_TARGET_KECAMATAN.md`.

---

## 8. PBB — Kasus Khusus

PBB (Pajak Bumi dan Bangunan) berbeda dari pajak lainnya:

| | Pajak Lain (Hotel, Restoran, dll) | PBB |
|--|----------------------------------|-----|
| WP | Pelaku usaha, punya NPWPD | Pemilik tanah/bangunan, tidak perlu NPWPD |
| Objek | Tempat usaha | Tanah dan bangunan |
| Cara lapor | WP lapor omzet (SPTPD) | Pemerintah terbitkan SPPT massal tiap tahun |
| Tabel | `dat_sptpd_*` | `dat_sknjop_pbb` |

Itulah kenapa ada istilah **"Wajib Pajak PBB (NON-NPWPD)"** — mereka tidak diwajibkan punya NPWPD, cukup diidentifikasi lewat NOP propertinya.

---

## 9. Ringkasan Relasi Antar Tabel

```
dat_subjek_pajak (WP)
    └── npwpd ──────────────────────────────────────┐
                                                     │
dat_objek_pajak (tempat usaha)                       │
    ├── npwpd ──────────────────────────────────────┘
    ├── nop                                          │
    ├── JENIS_PAJAK → ref_anggaran (nama rekening)   │
    ├── kd_kecamatan → ref_kecamatan                 │
    └── kd_kelurahan → ref_kelurahan                 │
                                                     │
dat_sptpd_* (laporan pajak per WP per bulan)         │
    ├── npwpd ───────────────────────────────────────┘
    ├── nop                                          │
    └── kohir ───────────────────────────────────────┐
                                                     │
pembayaran (uang masuk ke kas)                       │
    ├── kohir ───────────────────────────────────────┘
    ├── npwpd
    ├── nop
    ├── ayat + jenis + kelas → ref_anggaran
    └── tgl_bayar (dasar perhitungan realisasi)

m_target_anggaran (target APBD per jenis pajak)
    └── no_ayat + thn_anggaran → target tahunan + % per tribulan
```
