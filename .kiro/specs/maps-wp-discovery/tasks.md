# Implementation Plan: Maps WP Discovery

## Overview

Implementasi fitur Maps WP Discovery untuk menemukan potensi Wajib Pajak baru melalui crawling Google Maps dan mencocokkan hasilnya dengan database WP terdaftar. Menggunakan arsitektur action-based sesuai pola existing project, dengan Leaflet.js untuk peta interaktif dan Alpine.js untuk state management.

Semua command dijalankan via `docker exec layanan_pajak_app` dengan working directory `/var/www/`.

## Tasks

- [x] 1. Setup konfigurasi Scraper API dan exception classes
  - [x] 1.1 Tambahkan konfigurasi scraper di `config/services.php`
    - Tambahkan key `scraper` dengan `url` dari `env('SCRAPER_API_URL')` dan `timeout` 30 detik
    - Tambahkan `SCRAPER_API_URL=http://pajak_scraper_app:8000` di `.env` dan `.env.example`
    - _Requirements: 2.1, 2.6_

  - [x] 1.2 Buat exception class `ScraperUnavailableException`
    - Buat file `app/Exceptions/ScraperUnavailableException.php`
    - Exception untuk kondisi Scraper API tidak dapat dijangkau (connection refused)
    - _Requirements: 2.5_

  - [x] 1.3 Buat exception class `ScraperErrorException`
    - Buat file `app/Exceptions/ScraperErrorException.php`
    - Exception untuk kondisi HTTP error atau timeout dari Scraper API
    - _Requirements: 2.4_

- [x] 2. Implementasi action classes untuk crawling dan matching
  - [x] 2.1 Buat `CrawlMapsAction` di `app/Actions/MapsDiscovery/CrawlMapsAction.php`
    - Definisikan `KEYWORD_MAPPING` constant untuk mapping kode ayat ke keyword pencarian
    - Implementasi `__invoke(array $keywords, string $area, int $maxResults = 20): Collection`
    - Kirim HTTP GET ke Scraper API untuk setiap keyword dengan parameter `query`, `max_results`, `locale`
    - Handle `ConnectionException` → throw `ScraperUnavailableException`
    - Handle non-200 response / timeout → throw `ScraperErrorException`
    - Deduplikasi hasil berdasarkan `place_id`
    - Log error via `Log::warning()` / `Log::error()` sesuai pola `GetTaxForecastAction`
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [ ]* 2.2 Tulis property test untuk keyword list construction (Property 1)
    - **Property 1: Keyword list construction dari mapping + tambahan**
    - Generate random ayat codes dari `KEYWORD_MAPPING` + random additional keywords
    - Verifikasi panjang daftar keyword = jumlah keyword mapping + (1 jika tambahan non-empty, 0 jika kosong)
    - File: `tests/Unit/Actions/MapsDiscovery/CrawlMapsActionTest.php`
    - **Validates: Requirements 1.2, 1.3**

  - [ ]* 2.3 Tulis property test untuk deduplikasi place_id (Property 4)
    - **Property 4: Deduplikasi berdasarkan place_id**
    - Generate random crawl results dengan duplicate place_ids
    - Verifikasi semua `place_id` dalam hasil unik dan jumlah <= total input
    - File: `tests/Unit/Actions/MapsDiscovery/CrawlMapsActionTest.php`
    - **Validates: Requirements 2.3**

  - [x] 2.4 Buat `MatchTaxPayersAction` di `app/Actions/MapsDiscovery/MatchTaxPayersAction.php`
    - Definisikan `SIMILARITY_THRESHOLD = 0.6`
    - Implementasi `__invoke(Collection $crawlResults, ?string $ayat, ?string $kdKecamatan): Collection`
    - Query `SimpaduTaxPayer` dengan filter `ayat` dan `kd_kecamatan`
    - Bandingkan `title` vs `nm_wp`/`nm_op` dan `subtitle` vs `almt_op` menggunakan `similar_text()` yang dinormalisasi
    - Klasifikasi: skor >= 0.6 → `"terdaftar"` (dengan `matched_npwpd`, `matched_name`), else → `"potensi_baru"`
    - Return collection dengan field tambahan: `status`, `matched_npwpd`, `matched_name`, `similarity_score`
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

  - [ ]* 2.5 Tulis property test untuk similarity score invariant (Property 5)
    - **Property 5: Similarity score invariant**
    - Generate random string pairs, verifikasi score dalam range [0.0, 1.0]
    - Verifikasi string identik → score = 1.0
    - File: `tests/Unit/Actions/MapsDiscovery/MatchTaxPayersActionTest.php`
    - **Validates: Requirements 3.2, 3.3**

  - [ ]* 2.6 Tulis property test untuk klasifikasi WP (Property 6)
    - **Property 6: Klasifikasi WP terdaftar vs potensi baru**
    - Generate random crawl results + random WP data
    - Verifikasi: score >= 0.6 → status `"terdaftar"` + `matched_npwpd` non-null; else → `"potensi_baru"` + null
    - File: `tests/Unit/Actions/MapsDiscovery/MatchTaxPayersActionTest.php`
    - **Validates: Requirements 3.4, 3.5**

  - [ ]* 2.7 Tulis property test untuk output fields (Property 7)
    - **Property 7: Output matching mengandung semua field yang diperlukan**
    - Generate random crawl results, verifikasi output mengandung semua field asli + field matching
    - File: `tests/Unit/Actions/MapsDiscovery/MatchTaxPayersActionTest.php`
    - **Validates: Requirements 3.6**

  - [ ]* 2.8 Tulis property test untuk partisi statistik (Property 8)
    - **Property 8: Partisi statistik terdaftar + potensi baru = total**
    - Generate random matched results, verifikasi jumlah terdaftar + potensi_baru = total
    - File: `tests/Unit/Actions/MapsDiscovery/MatchTaxPayersActionTest.php`
    - **Validates: Requirements 6.1**

- [x] 3. Checkpoint - Pastikan semua test action classes pass
  - Jalankan `docker exec layanan_pajak_app php artisan test --compact tests/Unit/Actions/MapsDiscovery/`
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Buat form request dan controller
  - [x] 4.1 Buat `CrawlMapsDiscoveryRequest` di `app/Http/Requests/Admin/CrawlMapsDiscoveryRequest.php`
    - Validasi rules: `tax_type_code` (nullable, string, max:10), `district_id` (nullable, exists:districts,id), `keyword` (nullable, string, max:200)
    - Custom validation: `tax_type_code` atau `keyword` harus diisi minimal salah satu
    - _Requirements: 1.6_

  - [x] 4.2 Buat `MapsDiscoveryController` di `app/Http/Controllers/Admin/MapsDiscoveryController.php`
    - Method `index()`: query `TaxType` (yang punya `simpadu_code`) dan `District`, return view `admin.maps-discovery.index`
    - Method `crawl(CrawlMapsDiscoveryRequest, CrawlMapsAction, MatchTaxPayersAction)`: bangun keyword list dari mapping + input user, panggil actions, return JSON dengan `results` dan `stats`
    - Handle `ScraperUnavailableException` → JSON error 503
    - Handle `ScraperErrorException` → JSON error 500
    - Handle results kosong → JSON 200 dengan pesan info
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.4, 2.5, 7.1, 7.2_

- [x] 5. Daftarkan routes di `routes/web.php`
  - Tambahkan `use App\Http\Controllers\Admin\MapsDiscoveryController` di bagian import
  - Tambahkan route `GET maps-discovery` dan `POST maps-discovery/crawl` di dalam group `Route::middleware(['auth', 'role:admin|kepala_upt|pemimpin'])`
  - _Requirements: 8.1, 8.2, 8.3_

- [x] 6. Implementasi view Blade dengan Leaflet.js dan Alpine.js
  - [x] 6.1 Buat view `resources/views/admin/maps-discovery/index.blade.php`
    - Gunakan layout `<x-layouts.admin>` sesuai pola existing
    - Layout split-view: sidebar kiri (w-1/3) untuk filter form + daftar hasil, kanan (w-2/3) untuk stats cards + peta
    - Form filter: dropdown jenis pajak, dropdown kecamatan, input keyword, tombol "Crawl Data"
    - Alpine.js component untuk state management (`loading`, `results`, `error`, `stats`)
    - Leaflet.js dari CDN (unpkg.com/leaflet@1.9.4), tile OpenStreetMap
    - Peta di-center pada Pasuruan (-7.6455, 112.9075) zoom 12
    - Marker hijau (`L.divIcon`) untuk WP terdaftar, merah untuk potensi baru
    - Popup marker: nama, alamat, kategori, status, link Google Maps, info NPWPD (jika terdaftar)
    - Daftar hasil di sidebar dengan badge status berwarna
    - Klik item sidebar → pan + zoom ke marker + buka popup
    - Stats cards: "Terdaftar" (hijau, ikon centang) dan "Potensi Baru" (merah, ikon peringatan)
    - Loading indicator pada tombol dan area hasil saat crawling
    - Error handling: tampilkan pesan error di area notifikasi, pertahankan state filter
    - Validasi koordinat: hanya tampilkan marker jika dalam batas Jawa Timur (lat -8.5 s/d -7.0, lng 111.0 s/d 114.5)
    - _Requirements: 1.1, 1.2, 1.4, 1.6, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.1, 5.2, 5.3, 5.4, 5.5, 6.1, 6.2, 6.3, 6.4, 7.1, 7.2, 7.3, 7.4_

- [x] 7. Checkpoint - Verifikasi halaman dan integrasi
  - Jalankan `docker exec layanan_pajak_app php artisan route:list --name=maps-discovery` untuk verifikasi routes terdaftar
  - Jalankan `docker exec layanan_pajak_app vendor/bin/pint --dirty` untuk format code
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. Tulis feature tests untuk controller
  - [ ] 8.1 Buat feature test `tests/Feature/Admin/MapsDiscoveryControllerTest.php`
    - Test GET `/admin/maps-discovery` menampilkan halaman dengan filter (dropdown jenis pajak, kecamatan)
    - Test POST `/admin/maps-discovery/crawl` dengan filter valid → JSON response dengan `results` dan `stats`
    - Test POST `/admin/maps-discovery/crawl` tanpa jenis pajak dan keyword → validation error 422
    - Test unauthenticated user di-redirect ke login untuk kedua route
    - Test route names terdaftar: `admin.maps-discovery.index` dan `admin.maps-discovery.crawl`
    - Mock `CrawlMapsAction` dan `MatchTaxPayersAction` untuk isolasi test
    - Test error handling: mock `ScraperUnavailableException` → 503, `ScraperErrorException` → 500
    - _Requirements: 1.1, 1.6, 2.4, 2.5, 7.1, 8.1, 8.2, 8.3_

  - [ ]* 8.2 Tulis unit test untuk `CrawlMapsAction` edge cases
    - Test keyword mapping menghasilkan keyword yang benar untuk setiap ayat
    - Test default area "Pasuruan" ketika kecamatan tidak dipilih
    - Test Scraper API connection refused → `ScraperUnavailableException`
    - Test Scraper API HTTP error → `ScraperErrorException`
    - Test timeout configuration = 30 detik
    - File: `tests/Unit/Actions/MapsDiscovery/CrawlMapsActionTest.php`
    - _Requirements: 1.4, 2.4, 2.5, 2.6_

  - [ ]* 8.3 Tulis unit test untuk `MatchTaxPayersAction` edge cases
    - Test matching dengan data WP yang persis sama → status terdaftar
    - Test matching tanpa data WP → semua potensi_baru
    - Test crawl result dengan koordinat null tetap diproses
    - Test results kosong → empty collection
    - File: `tests/Unit/Actions/MapsDiscovery/MatchTaxPayersActionTest.php`
    - _Requirements: 3.4, 3.5, 7.1, 7.3_

- [ ] 9. Final checkpoint - Pastikan semua test pass dan code terformat
  - Jalankan `docker exec layanan_pajak_app php artisan test --compact tests/Feature/Admin/MapsDiscoveryControllerTest.php tests/Unit/Actions/MapsDiscovery/`
  - Jalankan `docker exec layanan_pajak_app vendor/bin/pint --dirty`
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks bertanda `*` bersifat opsional dan bisa dilewati untuk MVP lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Checkpoint memastikan validasi inkremental di setiap tahap
- Property tests memvalidasi correctness properties universal dari design document
- Unit tests memvalidasi contoh spesifik dan edge cases
- Semua command dijalankan via `docker exec layanan_pajak_app` dengan working directory `/var/www/`
