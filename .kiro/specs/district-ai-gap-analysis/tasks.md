# Implementation Tasks

## Tasks

- [x] 1. Tambah method `analyzeGap()` ke `GetDistrictAiRecommendationAction`
  - Tambah private method `analyzeGap(string $kdKecamatan, string $noAyat, int $currentYear, int $currentMonth): array`
  - Query 1: ambil semua npwpd + total_bayar per bulan untuk tahun lalu (`year = currentYear - 1`)
  - Query 2: ambil semua npwpd distinct per bulan untuk tahun ini (`year = currentYear`)
  - Per bulan (hanya bulan >= currentMonth): hitung wp_hilang, avg_bayar_per_wp, potensi_gap
  - Return `['total_potensi_gap' => float, 'gap_detail' => array]`
  - Jika tidak ada data tahun lalu, return total_potensi_gap = 0 tanpa error
  - **File:** `app/Actions/District/GetDistrictAiRecommendationAction.php`

- [x] 2. Tambah method `detectAnomalies()` ke `GetDistrictAiRecommendationAction`
  - Tambah private method `detectAnomalies(string $kdKecamatan, string $noAyat, int $currentYear): array`
  - Query: ambil npwpd + SUM(total_ketetapan) + SUM(total_bayar) untuk tahun ini, status='1', total_ketetapan > 0, GROUP BY npwpd
  - Klasifikasi: wp_belum_bayar (total_bayar = 0), wp_anomali (0 < total_bayar < total_ketetapan * 0.5)
  - Hitung total_potensi_anomali = SUM(total_ketetapan - total_bayar) untuk keduanya
  - Return `['wp_belum_bayar_count' => int, 'wp_anomali_count' => int, 'total_potensi_anomali' => float]`
  - **File:** `app/Actions/District/GetDistrictAiRecommendationAction.php`

- [x] 3. Update `__invoke()` untuk mengorkestrasi ketiga analisis dan return response baru
  - Panggil `analyzeGap()` dan `detectAnomalies()` di dalam `__invoke()`
  - Wrap SARIMA call dalam try/catch — jika gagal, set `selisih_sarima = 0` dan `model_used = 'Gap+Anomali'`
  - Hitung `recommendation = max(0, round(selisih_sarima + total_potensi_gap + total_potensi_anomali))`
  - Return JSON dengan field baru: `gap_detail`, `anomaly_detail`, dan `detail` yang diperkaya
  - Pastikan field lama tetap ada (backward-compatible): `recommendation`, `model_used`, `horizon_months`, `no_recommendation`, `detail.prediksi_sisa_tahun`, `detail.sisa_target`
  - **File:** `app/Actions/District/GetDistrictAiRecommendationAction.php`

- [x] 4. Tambah Panel_AI HTML ke view `create.blade.php`
  - Sisipkan `<div id="aiInsightPanel" class="hidden ...">` setelah blok `aiRecInfo`/`aiRecError`
  - Stats row: 4 kartu (Prediksi SARIMA, Potensi Gap WP, Potensi Anomali, Total Rekomendasi)
  - Gap table: tabel per bulan dengan kolom Bulan, WP Hilang, Rata-rata/WP, Potensi (conditional)
  - Anomaly row: ringkasan WP Belum Bayar + WP Anomali + total potensi (conditional)
  - Action list: daftar aksi teks untuk petugas (conditional per kondisi)
  - **File:** `resources/views/admin/district-additional-targets/create.blade.php`

- [x] 5. Tambah fungsi `renderAiInsight(data)` dan update handler tombol "Rekomendasi AI"
  - Tambah fungsi `renderAiInsight(data)` yang mengisi Panel_AI dari response JSON
  - Update handler `btnAiRec` untuk memanggil `renderAiInsight(data)` setelah fetch berhasil
  - Sembunyikan panel saat jenis pajak atau kecamatan berubah (`fetchAndSetPcts`)
  - **File:** `resources/views/admin/district-additional-targets/create.blade.php`

- [ ] 6. Tulis Pest tests untuk `GetDistrictAiRecommendationAction`
  - Test: gap analysis mengembalikan 0 jika tidak ada data tahun lalu
  - Test: gap analysis hanya menghitung bulan >= currentMonth
  - Test: anomaly detection mengklasifikasikan WP_Belum_Bayar dan WP_Anomali dengan benar
  - Test: recommendation = 0 jika semua komponen negatif atau nol
  - Test: fallback graceful jika SARIMA gagal (model_used = 'Gap+Anomali')
  - **File:** `tests/Feature/GetDistrictAiRecommendationActionTest.php`
