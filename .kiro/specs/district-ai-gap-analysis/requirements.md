# Requirements Document

## Introduction

Fitur **Analisis Gap Historis + Deteksi Anomali** memperkaya tombol "Rekomendasi AI" yang sudah ada
pada form target tambahan per kecamatan di aplikasi monitoring realisasi pajak daerah Kabupaten Pasuruan.

Saat ini, `GetDistrictAiRecommendationAction` hanya menggunakan prediksi SARIMA dari data realisasi
historis. Masalahnya, tabel `simpadu_tax_payers` hanya berisi WP yang sudah melaporkan — WP yang
tidak lapor tidak terdeteksi, sehingga target per kecamatan bisa under-estimate.

Fitur ini menambahkan dua lapisan analisis baru:

1. **Analisis Gap Historis** — mendeteksi WP yang biasanya lapor (ada di data tahun lalu) tetapi
   tidak lapor tahun ini, lalu mengestimasi potensi pendapatan yang hilang.
2. **Deteksi Anomali Ketetapan vs Bayar** — mendeteksi WP yang sudah lapor tahun ini tetapi belum
   atau kurang membayar, lalu menghitung total potensi penagihan.

Hasil kedua analisis digabungkan dengan prediksi SARIMA untuk menghasilkan rekomendasi nominal
target tambahan yang lebih akurat, beserta panel informasi dan daftar aksi rekomendasi untuk petugas.

## Glossary

- **Gap_Analyzer**: Komponen yang menganalisis selisih jumlah WP pelapor antara tahun ini dan tahun lalu per bulan per kecamatan per jenis pajak.
- **Anomaly_Detector**: Komponen yang mendeteksi WP aktif tahun ini yang memiliki `total_ketetapan > 0` tetapi `total_bayar` di bawah ambang batas.
- **AI_Recommendation_Action**: `GetDistrictAiRecommendationAction` — action utama yang mengorkestrasi SARIMA, Gap_Analyzer, dan Anomaly_Detector.
- **WP**: Wajib Pajak — entitas yang terdaftar di `simpadu_tax_payers`.
- **WP_Hilang**: WP yang tercatat di data tahun lalu (bulan yang sama) tetapi tidak ada di data tahun ini untuk kecamatan dan jenis pajak yang sama.
- **WP_Anomali**: WP aktif tahun ini dengan `total_ketetapan > 0` dan `total_bayar < total_ketetapan * 0.5`.
- **WP_Belum_Bayar**: WP aktif tahun ini dengan `total_ketetapan > 0` dan `total_bayar = 0`.
- **Potensi_Gap**: Estimasi pendapatan dari WP_Hilang = rata-rata `total_bayar` per WP tahun lalu × jumlah WP_Hilang per bulan, diakumulasi untuk sisa tahun.
- **Potensi_Anomali**: Total `total_ketetapan - total_bayar` dari seluruh WP_Anomali dan WP_Belum_Bayar tahun ini.
- **Rekomendasi_Total**: Nominal target tambahan = prediksi SARIMA + Potensi_Gap + Potensi_Anomali.
- **Panel_AI**: Elemen UI yang muncul setelah klik "Rekomendasi AI", menampilkan ringkasan angka, daftar aksi, dan mengisi otomatis nominal target tambahan.
- **Kecamatan**: Wilayah administratif yang diidentifikasi oleh `kd_kecamatan` di `simpadu_tax_payers` dan `simpadu_code` di tabel `districts`.
- **No_Ayat**: Kode jenis pajak daerah yang menjadi filter analisis.
- **Bulan_Berjalan**: Bulan kalender saat ini (`now()->month`).
- **Tahun_Berjalan**: Tahun kalender saat ini (`now()->year`).

## Requirements

### Requirement 1: Analisis Gap Historis WP

**User Story:** Sebagai petugas pajak, saya ingin mengetahui WP yang biasanya lapor tahun lalu tetapi
tidak lapor tahun ini, sehingga saya dapat mengestimasi potensi pendapatan yang hilang dan
memasukkannya ke dalam target tambahan.

#### Acceptance Criteria

1. WHEN `GetDistrictAiRecommendationAction` dipanggil dengan `District` dan `noAyat` yang valid, THE `Gap_Analyzer` SHALL mengambil daftar `npwpd` unik dari `simpadu_tax_payers` untuk `kd_kecamatan`, `ayat`, dan `year = Tahun_Berjalan - 1` per bulan.
2. WHEN data tahun lalu tersedia, THE `Gap_Analyzer` SHALL membandingkan daftar `npwpd` tahun lalu per bulan dengan daftar `npwpd` tahun ini untuk bulan yang sama, dan mengidentifikasi `npwpd` yang ada di tahun lalu tetapi tidak ada di tahun ini sebagai WP_Hilang.
3. WHEN WP_Hilang teridentifikasi untuk suatu bulan, THE `Gap_Analyzer` SHALL menghitung rata-rata `total_bayar` per WP aktif pada bulan tersebut di tahun lalu sebagai `avg_bayar_per_wp`.
4. WHEN `avg_bayar_per_wp` dihitung, THE `Gap_Analyzer` SHALL menghitung `Potensi_Gap` per bulan = `jumlah_wp_hilang × avg_bayar_per_wp`, hanya untuk bulan-bulan mulai dari `Bulan_Berjalan` hingga bulan 12 pada `Tahun_Berjalan`.
5. THE `Gap_Analyzer` SHALL mengakumulasi `Potensi_Gap` seluruh bulan sisa tahun menjadi satu nilai `total_potensi_gap` bertipe `float`.
6. IF tidak ada data tahun lalu untuk kecamatan dan jenis pajak tersebut, THEN THE `Gap_Analyzer` SHALL mengembalikan `total_potensi_gap = 0` tanpa error.
7. THE `Gap_Analyzer` SHALL menyertakan `gap_detail` berupa array per bulan yang berisi: `month`, `wp_hilang_count`, `avg_bayar_per_wp`, dan `potensi_gap`.

---

### Requirement 2: Deteksi Anomali Ketetapan vs Bayar

**User Story:** Sebagai petugas pajak, saya ingin mengetahui WP yang sudah lapor tahun ini tetapi
belum atau kurang membayar, sehingga saya dapat menghitung total potensi penagihan dan
memasukkannya ke dalam target tambahan.

#### Acceptance Criteria

1. WHEN `GetDistrictAiRecommendationAction` dipanggil, THE `Anomaly_Detector` SHALL mengambil semua record dari `simpadu_tax_payers` dengan `kd_kecamatan`, `ayat`, `year = Tahun_Berjalan`, `status = '1'`, dan `total_ketetapan > 0`.
2. WHEN record tersebut tersedia, THE `Anomaly_Detector` SHALL mengklasifikasikan WP_Belum_Bayar sebagai record dengan `total_bayar = 0`.
3. WHEN record tersebut tersedia, THE `Anomaly_Detector` SHALL mengklasifikasikan WP_Anomali sebagai record dengan `total_bayar > 0` dan `total_bayar < total_ketetapan * 0.5`.
4. THE `Anomaly_Detector` SHALL menghitung `Potensi_Anomali` = jumlah `(total_ketetapan - total_bayar)` dari seluruh WP_Belum_Bayar dan WP_Anomali.
5. IF tidak ada WP_Belum_Bayar maupun WP_Anomali, THEN THE `Anomaly_Detector` SHALL mengembalikan `total_potensi_anomali = 0` tanpa error.
6. THE `Anomaly_Detector` SHALL menyertakan `anomaly_detail` berupa objek yang berisi: `wp_belum_bayar_count`, `wp_anomali_count`, `total_potensi_anomali`.

---

### Requirement 3: Penggabungan Rekomendasi Total

**User Story:** Sebagai petugas pajak, saya ingin mendapatkan satu angka rekomendasi target tambahan
yang sudah menggabungkan prediksi SARIMA, potensi gap WP, dan potensi anomali, sehingga rekomendasi
lebih komprehensif dan akurat.

#### Acceptance Criteria

1. WHEN prediksi SARIMA, `total_potensi_gap`, dan `total_potensi_anomali` tersedia, THE `AI_Recommendation_Action` SHALL menghitung `Rekomendasi_Total` = `max(0, round(selisih_sarima + total_potensi_gap + total_potensi_anomali))`.
2. THE `AI_Recommendation_Action` SHALL mengembalikan response JSON yang menyertakan field: `recommendation` (integer), `model_used`, `horizon_months`, `detail` (objek gabungan), `gap_detail` (array per bulan), `anomaly_detail` (objek), dan `no_recommendation` (boolean).
3. WHEN `Rekomendasi_Total <= 0`, THE `AI_Recommendation_Action` SHALL mengembalikan `no_recommendation = true` dan `recommendation = 0`.
4. IF prediksi SARIMA gagal tetapi `total_potensi_gap > 0` atau `total_potensi_anomali > 0`, THEN THE `AI_Recommendation_Action` SHALL tetap mengembalikan rekomendasi berdasarkan potensi gap dan anomali saja, dengan `model_used = 'Gap+Anomali'`.
5. THE `AI_Recommendation_Action` SHALL menyertakan dalam `detail`: `prediksi_sisa_tahun`, `sisa_target`, `selisih_sarima`, `total_potensi_gap`, `total_potensi_anomali`, dan `rekomendasi_total`.

---

### Requirement 4: Panel Informasi AI di UI

**User Story:** Sebagai petugas pajak, saya ingin melihat panel informasi yang jelas setelah klik
"Rekomendasi AI", sehingga saya memahami dasar perhitungan dan dapat mengambil tindakan yang tepat.

#### Acceptance Criteria

1. WHEN tombol "Rekomendasi AI" diklik dan response berhasil diterima, THE `Panel_AI` SHALL menampilkan ringkasan angka yang terdiri dari: prediksi SARIMA sisa tahun, potensi gap WP, potensi anomali, dan total rekomendasi.
2. WHEN `total_potensi_gap > 0`, THE `Panel_AI` SHALL menampilkan jumlah WP_Hilang per bulan dalam format tabel ringkas.
3. WHEN `total_potensi_anomali > 0`, THE `Panel_AI` SHALL menampilkan jumlah WP_Belum_Bayar, jumlah WP_Anomali, dan total potensi penagihan.
4. THE `Panel_AI` SHALL menampilkan daftar aksi rekomendasi teks untuk petugas berdasarkan kondisi: jika ada WP_Hilang maka tampilkan aksi "Lakukan kunjungan ke WP yang tidak lapor", jika ada WP_Anomali maka tampilkan aksi "Lakukan penagihan ke WP dengan pembayaran kurang dari 50%", jika ada WP_Belum_