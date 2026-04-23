# Design Document

## Overview

Fitur ini memodifikasi `GetDistrictAiRecommendationAction` untuk menambahkan dua private method baru
(`analyzeGap` dan `detectAnomalies`) yang berjalan paralel dengan prediksi SARIMA yang sudah ada.
Hasilnya digabungkan menjadi satu response JSON yang lebih kaya, lalu ditampilkan di Panel_AI pada
view `district-additional-targets/create.blade.php`.

Tidak ada tabel baru, tidak ada migration, tidak ada route baru — semua perubahan terbatas pada
satu Action class dan satu Blade view.

## Architecture

```
DistrictAdditionalTargetController
  └── aiRecommendation()
        └── GetDistrictAiRecommendationAction (dimodifikasi)
              ├── SARIMA forecast (sudah ada, via HTTP ke pajak_forecast_api)
              ├── analyzeGap()        ← BARU: query simpadu_tax_payers
              └── detectAnomalies()   ← BARU: query simpadu_tax_payers
                    ↓
              Response JSON (diperkaya)
                    ↓
        create.blade.php — Panel_AI (dimodifikasi)
              ├── Stats row (4 angka)
              ├── Gap table (per bulan)
              ├── Anomaly summary
              └── Action list
```

## Components

### 1. `GetDistrictAiRecommendationAction` (dimodifikasi)

**File:** `app/Actions/District/GetDistrictAiRecommendationAction.php`

#### Alur eksekusi baru

```
__invoke(District, noAyat)
  1. Ambil SimpaduTarget (sudah ada)
  2. Jalankan SARIMA forecast (sudah ada) — jika gagal, set selisih_sarima = 0
  3. analyzeGap(kd_kecamatan, noAyat, currentYear, currentMonth) → gap result
  4. detectAnomalies(kd_kecamatan, noAyat, currentYear) → anomaly result
  5. Hitung Rekomendasi_Total = max(0, round(selisih_sarima + potensi_gap + potensi_anomali))
  6. Return response JSON lengkap
```

#### Method `analyzeGap()`

```php
private function analyzeGap(
    string $kdKecamatan,
    string $noAyat,
    int $currentYear,
    int $currentMonth
): array
```

**Query tahun lalu per bulan:**
```sql
SELECT month, npwpd, SUM(total_bayar) as total_bayar
FROM simpadu_tax_payers
WHERE kd_kecamatan = ? AND ayat = ? AND year = ? AND status = '1' AND month > 0
GROUP BY month, npwpd
```

**Query tahun ini per bulan:**
```sql
SELECT DISTINCT month, npwpd
FROM simpadu_tax_payers
WHERE kd_kecamatan = ? AND ayat = ? AND year = ? AND status = '1' AND month > 0
```

**Logika per bulan (hanya bulan >= currentMonth):**
- `wp_hilang` = npwpd yang ada di tahun lalu tapi tidak ada di tahun ini
- `avg_bayar_per_wp` = rata-rata total_bayar per npwpd di tahun lalu untuk bulan itu
- `potensi_gap` = count(wp_hilang) × avg_bayar_per_wp

**Return:**
```php
[
    'total_potensi_gap' => float,
    'gap_detail' => [
        ['month' => int, 'wp_hilang_count' => int, 'avg_bayar_per_wp' => float, 'potensi_gap' => float],
        ...
    ],
]
```

#### Method `detectAnomalies()`

```php
private function detectAnomalies(
    string $kdKecamatan,
    string $noAyat,
    int $currentYear
): array
```

**Query:**
```sql
SELECT npwpd, SUM(total_ketetapan) as total_ketetapan, SUM(total_bayar) as total_bayar
FROM simpadu_tax_payers
WHERE kd_kecamatan = ? AND ayat = ? AND year = ? AND status = '1' AND total_ketetapan > 0
GROUP BY npwpd
```

**Klasifikasi:**
- `wp_belum_bayar`: total_bayar = 0
- `wp_anomali`: total_bayar > 0 AND total_bayar < total_ketetapan * 0.5
- `potensi_anomali` = SUM(total_ketetapan - total_bayar) untuk keduanya

**Return:**
```php
[
    'wp_belum_bayar_count' => int,
    'wp_anomali_count'     => int,
    'total_potensi_anomali' => float,
]
```

#### Response JSON baru (backward-compatible)

```json
{
  "recommendation": 65000000,
  "model_used": "SARIMA",
  "horizon_months": 8,
  "no_recommendation": false,
  "detail": {
    "prediksi_sisa_tahun": 50000000,
    "sisa_target": 45000000,
    "selisih_sarima": 5000000,
    "total_potensi_gap": 45000000,
    "total_potensi_anomali": 15000000,
    "rekomendasi_total": 65000000
  },
  "gap_detail": [
    { "month": 5, "wp_hilang_count": 8, "avg_bayar_per_wp": 2500000, "potensi_gap": 20000000 },
    { "month": 6, "wp_hilang_count": 5, "avg_bayar_per_wp": 2500000, "potensi_gap": 12500000 }
  ],
  "anomaly_detail": {
    "wp_belum_bayar_count": 12,
    "wp_anomali_count": 7,
    "total_potensi_anomali": 15000000
  }
}
```

**Fallback jika SARIMA gagal:** `model_used = 'Gap+Anomali'`, `selisih_sarima = 0`, tetap return
rekomendasi dari gap + anomali.

---

### 2. Panel_AI di `create.blade.php` (dimodifikasi)

**File:** `resources/views/admin/district-additional-targets/create.blade.php`

Panel disisipkan tepat setelah `<div class="mt-1.5 space-y-0.5">` (area aiRecInfo/aiRecError),
hanya muncul setelah response AI berhasil diterima.

#### Struktur HTML Panel_AI

```
<div id="aiInsightPanel" class="hidden mt-3 ...">

  <!-- Header -->
  <div> ✨ Analisis Rekomendasi AI </div>

  <!-- Stats Row: 4 kartu -->
  <div class="grid grid-cols-2 sm:grid-cols-4">
    [Prediksi SARIMA] [Potensi Gap WP] [Potensi Anomali] [Total Rekomendasi]
  </div>

  <!-- Gap Table (conditional: hanya jika gap_detail ada) -->
  <div id="aiGapTable">
    <table> Bulan | WP Hilang | Rata-rata/WP | Potensi </table>
  </div>

  <!-- Anomaly Summary (conditional: hanya jika anomaly_detail ada) -->
  <div id="aiAnomalyRow">
    WP Belum Bayar: X | WP Bayar < 50%: Y | Potensi Penagihan: Rp Z
  </div>

  <!-- Action List -->
  <div id="aiActionList">
    • Lakukan kunjungan ke X WP yang tidak lapor
    • Lakukan penagihan ke Y WP dengan pembayaran < 50%
    • Lakukan penagihan ke Z WP yang belum bayar sama sekali
  </div>

</div>
```

#### Fungsi JavaScript `renderAiInsight(data)`

Dipanggil dari handler tombol "Rekomendasi AI" setelah fetch berhasil.

```javascript
function renderAiInsight(data) {
    const detail = data.detail ?? {};
    const gap    = data.gap_detail ?? [];
    const anom   = data.anomaly_detail ?? {};
    const fmt    = v => 'Rp ' + Math.round(v).toLocaleString('id-ID');
    const monthNames = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    // 1. Stats row
    // 2. Gap table (jika ada bulan dengan wp_hilang_count > 0)
    // 3. Anomaly row (jika wp_belum_bayar_count > 0 || wp_anomali_count > 0)
    // 4. Action list (conditional per kondisi)

    document.getElementById('aiInsightPanel').classList.remove('hidden');
}
```

---

## Data Flow

```
Klik "Rekomendasi AI"
  → fetch GET /admin/district-additional-targets/ai-recommendation
           ?district_id=X&no_ayat=Y
  → DistrictAdditionalTargetController::aiRecommendation()
  → GetDistrictAiRecommendationAction::__invoke(District, noAyat)
      ├── SARIMA: POST pajak_forecast_api/forecast/from-data
      ├── analyzeGap(): 2 query ke simpadu_tax_payers
      └── detectAnomalies(): 1 query ke simpadu_tax_payers
  → JSON response
  → renderAiInsight(data) di browser
  → Panel_AI tampil + nominal terisi otomatis
```

## Correctness Properties

1. **P1 — Non-negative recommendation:** `recommendation >= 0` selalu.
2. **P2 — Gap hanya sisa tahun:** `gap_detail` hanya berisi bulan `>= currentMonth`.
3. **P3 — Anomali hanya tahun berjalan:** `detectAnomalies` hanya query `year = currentYear`.
4. **P4 — Fallback graceful:** Jika SARIMA gagal, `selisih_sarima = 0` dan rekomendasi tetap dihitung dari gap + anomali.
5. **P5 — Zero-safe:** Jika tidak ada data gap maupun anomali, `recommendation` sama dengan hasil SARIMA saja (backward-compatible).
6. **P6 — Backward-compatible response:** Field `recommendation`, `model_used`, `horizon_months`, `no_recommendation`, `detail.prediksi_sisa_tahun`, `detail.sisa_target` tetap ada dan tidak berubah semantiknya.
