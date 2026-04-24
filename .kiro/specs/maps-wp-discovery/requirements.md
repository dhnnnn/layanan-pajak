# Requirements Document

## Introduction

Fitur **Maps WP Discovery** menambahkan halaman baru di aplikasi monitoring pajak daerah Kabupaten Pasuruan
untuk menemukan potensi Wajib Pajak (WP) baru yang belum terdaftar di database `simpadu_tax_payers`.

Saat ini, data WP hanya berasal dari pelaporan manual dan impor SIMPADU. Banyak usaha (hotel, restoran,
hiburan, dll.) yang beroperasi di wilayah Pasuruan tetapi belum terdaftar sebagai WP. Fitur ini
memanfaatkan crawling Google Maps melalui API `pajak_scraper_app` untuk mencari lokasi bisnis berdasarkan
jenis pajak dan wilayah, lalu mencocokkan hasilnya dengan database WP yang sudah terdaftar.

Hasil ditampilkan dalam layout split-view: sidebar kiri berisi filter pencarian dan daftar hasil,
sedangkan sisi kanan menampilkan peta interaktif Leaflet.js dengan marker berwarna berbeda untuk
WP terdaftar (hijau) dan potensi WP baru (merah). Statistik ringkasan ditampilkan di atas peta.

## Glossary

- **Scraper_API**: Layanan crawling Google Maps yang berjalan di Docker container `pajak_scraper_app` pada network `pajak_shared`, endpoint `GET /search` di `http://pajak_scraper_app:8000`.
- **Maps_Controller**: Controller Laravel yang menangani halaman discovery, menerima input filter dari user, memanggil Scraper_API, dan mencocokkan hasil dengan database WP.
- **Matcher**: Komponen yang mencocokkan hasil crawling Google Maps dengan data WP terdaftar di `simpadu_tax_payers` menggunakan fuzzy string matching pada kolom `nm_wp`, `nm_op` (nama) dan `almt_op` (alamat) terhadap `title` dan `subtitle` dari hasil crawling.
- **WP_Terdaftar**: Hasil crawling yang berhasil dicocokkan dengan minimal satu record di `simpadu_tax_payers` berdasarkan kesamaan nama atau alamat.
- **WP_Potensi_Baru**: Hasil crawling yang tidak cocok dengan record manapun di `simpadu_tax_payers`.
- **Crawl_Result**: Satu item hasil dari Scraper_API yang berisi: `title`, `subtitle`, `category`, `place_id`, `url`, `latitude`, `longitude`.
- **Discovery_Page**: Halaman web dengan layout split-view yang menampilkan filter, daftar hasil, dan peta interaktif.
- **Map_View**: Komponen peta Leaflet.js yang menampilkan marker lokasi bisnis hasil crawling.
- **Marker_Terdaftar**: Marker berwarna hijau/biru pada peta yang menandakan lokasi bisnis sudah terdaftar sebagai WP.
- **Marker_Potensi**: Marker berwarna merah/oranye pada peta yang menandakan lokasi bisnis belum terdaftar sebagai WP.
- **Keyword_Mapping**: Pemetaan kode jenis pajak (`ayat`) ke keyword pencarian Google Maps, contoh: `41101` → `"hotel"`, `41102` → `"restoran", "cafe", "rumah makan"`.
- **Similarity_Score**: Nilai kesamaan antara dua string (0.0 - 1.0) yang digunakan Matcher untuk menentukan apakah hasil crawling cocok dengan WP terdaftar.
- **Similarity_Threshold**: Batas minimum Similarity_Score agar hasil crawling dianggap cocok dengan WP terdaftar, default `0.6`.
- **Stats_Card**: Komponen UI yang menampilkan ringkasan jumlah WP_Terdaftar dan WP_Potensi_Baru dari hasil crawling.

## Requirements

### Requirement 1: Filter dan Input Pencarian

**User Story:** Sebagai petugas pajak, saya ingin memilih jenis pajak, kecamatan, dan keyword tambahan sebelum melakukan crawling, sehingga hasil pencarian relevan dengan wilayah dan jenis usaha yang ingin saya telusuri.

#### Acceptance Criteria

1. THE Discovery_Page SHALL menampilkan form filter yang berisi: dropdown jenis pajak (dari tabel `tax_types` dengan `simpadu_code`), dropdown kecamatan (dari tabel `districts`), input teks keyword tambahan, dan tombol "Crawl Data".
2. WHEN user memilih jenis pajak, THE Discovery_Page SHALL mengisi keyword pencarian default berdasarkan Keyword_Mapping untuk kode `ayat` yang dipilih.
3. WHERE user mengisi keyword tambahan, THE Discovery_Page SHALL menambahkan keyword tersebut ke daftar keyword pencarian selain keyword default dari Keyword_Mapping.
4. WHEN user tidak memilih kecamatan, THE Discovery_Page SHALL menggunakan "Pasuruan" sebagai wilayah pencarian default.
5. WHEN user memilih kecamatan, THE Discovery_Page SHALL menambahkan nama kecamatan ke query pencarian untuk mempersempit wilayah.
6. IF jenis pajak tidak dipilih dan keyword tambahan kosong, THEN THE Discovery_Page SHALL menampilkan pesan validasi "Pilih jenis pajak atau isi keyword pencarian" dan tidak mengirim request crawling.

---

### Requirement 2: Integrasi Scraper API

**User Story:** Sebagai petugas pajak, saya ingin sistem secara otomatis mencari lokasi bisnis di Google Maps berdasarkan filter yang saya pilih, sehingga saya tidak perlu mencari manual satu per satu.

#### Acceptance Criteria

1. WHEN tombol "Crawl Data" diklik dengan filter valid, THE Maps_Controller SHALL mengirim HTTP GET request ke Scraper_API di `http://pajak_scraper_app:8000/search` dengan parameter `query` (gabungan keyword + wilayah), `max_results` (default 20), dan `locale=id-ID`.
2. WHEN Scraper_API mengembalikan response sukses (HTTP 200), THE Maps_Controller SHALL mem-parsing field `results` dari response JSON dan mengekstrak `title`, `subtitle`, `category`, `place_id`, `url`, `latitude`, `longitude` dari setiap item.
3. WHEN jenis pajak memiliki lebih dari satu keyword di Keyword_Mapping, THE Maps_Controller SHALL mengirim request terpisah untuk setiap keyword dan menggabungkan hasilnya, dengan deduplikasi berdasarkan `place_id`.
4. IF Scraper_API mengembalikan HTTP error atau timeout, THEN THE Maps_Controller SHALL mengembalikan response error dengan pesan "Gagal mengambil data dari Google Maps. Pastikan layanan scraper aktif." dan HTTP status 500.
5. IF Scraper_API tidak dapat dijangkau (connection refused), THEN THE Maps_Controller SHALL mengembalikan response error dengan pesan "Layanan scraper tidak tersedia. Hubungi administrator." dan HTTP status 503.
6. THE Maps_Controller SHALL menggunakan timeout 30 detik untuk setiap request ke Scraper_API.

---

### Requirement 3: Pencocokan Hasil Crawling dengan Database WP

**User Story:** Sebagai petugas pajak, saya ingin sistem secara otomatis mencocokkan hasil crawling dengan database WP yang sudah terdaftar, sehingga saya dapat langsung melihat mana yang sudah terdaftar dan mana yang potensi baru.

#### Acceptance Criteria

1. WHEN hasil crawling diterima dari Scraper_API, THE Matcher SHALL mengambil data WP dari `simpadu_tax_payers` yang memiliki `ayat` sesuai jenis pajak yang dipilih dan opsional `kd_kecamatan` sesuai kecamatan yang dipilih.
2. THE Matcher SHALL menghitung Similarity_Score antara `title` dari Crawl_Result dengan `nm_wp` dan `nm_op` dari setiap record WP menggunakan algoritma fuzzy string matching (similar_text atau levenshtein yang dinormalisasi).
3. THE Matcher SHALL menghitung Similarity_Score antara `subtitle` dari Crawl_Result dengan `almt_op` dari setiap record WP.
4. WHEN Similarity_Score nama (title vs nm_wp atau nm_op) >= Similarity_Threshold ATAU Similarity_Score alamat (subtitle vs almt_op) >= Similarity_Threshold, THE Matcher SHALL mengklasifikasikan Crawl_Result sebagai WP_Terdaftar dan menyertakan data `npwpd` dari record WP yang cocok.
5. WHEN tidak ada record WP yang memenuhi Similarity_Threshold untuk nama maupun alamat, THE Matcher SHALL mengklasifikasikan Crawl_Result sebagai WP_Potensi_Baru.
6. THE Matcher SHALL mengembalikan setiap Crawl_Result dengan tambahan field: `status` ("terdaftar" atau "potensi_baru"), `matched_npwpd` (null jika potensi baru), `matched_name` (nama WP yang cocok, null jika potensi baru), dan `similarity_score` (skor tertinggi yang ditemukan).

---

### Requirement 4: Tampilan Peta Interaktif

**User Story:** Sebagai petugas pajak, saya ingin melihat hasil crawling ditampilkan di peta interaktif dengan marker berwarna berbeda, sehingga saya dapat secara visual mengidentifikasi lokasi potensi WP baru di wilayah Pasuruan.

#### Acceptance Criteria

1. THE Map_View SHALL menampilkan peta Leaflet.js yang di-center pada koordinat Kabupaten Pasuruan (latitude -7.6455, longitude 112.9075) dengan zoom level default 12.
2. WHEN hasil crawling tersedia, THE Map_View SHALL menampilkan Marker_Terdaftar (warna hijau/biru) untuk setiap Crawl_Result dengan status "terdaftar" pada koordinat `latitude` dan `longitude` dari Crawl_Result.
3. WHEN hasil crawling tersedia, THE Map_View SHALL menampilkan Marker_Potensi (warna merah/oranye) untuk setiap Crawl_Result dengan status "potensi_baru" pada koordinat `latitude` dan `longitude` dari Crawl_Result.
4. WHEN user mengklik marker pada peta, THE Map_View SHALL menampilkan popup yang berisi: nama tempat (`title`), alamat (`subtitle`), kategori (`category`), status ("Terdaftar" atau "Potensi Baru"), dan link ke Google Maps (`url`).
5. WHEN marker bertipe WP_Terdaftar diklik, THE Map_View SHALL menampilkan tambahan informasi NPWPD dan nama WP yang cocok di dalam popup.
6. THE Map_View SHALL menggunakan Leaflet.js dari CDN tanpa memerlukan instalasi npm.
7. WHEN hasil crawling diperbarui setelah crawling ulang, THE Map_View SHALL menghapus semua marker lama dan menampilkan marker baru sesuai hasil terbaru.

---

### Requirement 5: Daftar Hasil Crawling di Sidebar

**User Story:** Sebagai petugas pajak, saya ingin melihat daftar hasil crawling di samping peta dengan status masing-masing, sehingga saya dapat menelusuri detail setiap lokasi bisnis yang ditemukan.

#### Acceptance Criteria

1. WHEN hasil crawling tersedia, THE Discovery_Page SHALL menampilkan daftar Crawl_Result di sidebar kiri di bawah form filter, dengan setiap item menampilkan: nama tempat (`title`), alamat (`subtitle`), kategori (`category`), dan badge status.
2. THE Discovery_Page SHALL menampilkan badge berwarna hijau bertuliskan "Terdaftar" untuk WP_Terdaftar dan badge berwarna merah bertuliskan "Potensi Baru" untuk WP_Potensi_Baru.
3. WHEN user mengklik item di daftar sidebar, THE Map_View SHALL melakukan pan dan zoom ke marker yang bersesuaian di peta dan membuka popup informasi marker tersebut.
4. THE Discovery_Page SHALL menampilkan total jumlah hasil di atas daftar dalam format "Ditemukan X lokasi".
5. WHILE proses crawling sedang berjalan, THE Discovery_Page SHALL menampilkan indikator loading pada tombol "Crawl Data" dan area daftar hasil.

---

### Requirement 6: Statistik Ringkasan

**User Story:** Sebagai petugas pajak, saya ingin melihat ringkasan statistik jumlah WP terdaftar vs potensi baru dari hasil crawling, sehingga saya dapat menilai seberapa besar potensi WP baru di wilayah tersebut.

#### Acceptance Criteria

1. WHEN hasil crawling tersedia, THE Discovery_Page SHALL menampilkan Stats_Card di area atas peta yang berisi: kartu "Terdaftar" dengan jumlah WP_Terdaftar dan kartu "Potensi Baru" dengan jumlah WP_Potensi_Baru.
2. THE Stats_Card "Terdaftar" SHALL berwarna hijau dan menampilkan ikon centang beserta angka jumlah.
3. THE Stats_Card "Potensi Baru" SHALL berwarna merah dan menampilkan ikon peringatan beserta angka jumlah.
4. WHEN belum ada hasil crawling, THE Stats_Card SHALL menampilkan angka 0 untuk kedua kartu.

---

### Requirement 7: Penanganan Error dan Edge Case

**User Story:** Sebagai petugas pajak, saya ingin sistem menangani error dengan baik dan memberikan pesan yang jelas, sehingga saya tahu apa yang terjadi ketika ada masalah.

#### Acceptance Criteria

1. IF Scraper_API mengembalikan `results` kosong (array kosong), THEN THE Discovery_Page SHALL menampilkan pesan "Tidak ditemukan lokasi bisnis untuk pencarian ini. Coba ubah keyword atau wilayah." di area daftar hasil dan peta tetap ditampilkan tanpa marker.
2. IF terjadi error jaringan saat memanggil Scraper_API, THEN THE Discovery_Page SHALL menampilkan pesan error di area notifikasi dan mempertahankan state filter yang sudah diisi user.
3. IF Crawl_Result memiliki `latitude` atau `longitude` bernilai null atau di luar batas wilayah Jawa Timur (latitude -8.5 s/d -7.0, longitude 111.0 s/d 114.5), THEN THE Matcher SHALL tetap memprosesnya untuk pencocokan tetapi Map_View tidak menampilkan marker untuk item tersebut.
4. WHEN user melakukan crawling ulang dengan filter berbeda, THE Discovery_Page SHALL menghapus hasil crawling sebelumnya dan menampilkan hasil baru.

---

### Requirement 8: Routing dan Otorisasi

**User Story:** Sebagai administrator, saya ingin halaman discovery hanya dapat diakses oleh user yang memiliki izin, sehingga fitur ini terkontrol penggunaannya.

#### Acceptance Criteria

1. THE Maps_Controller SHALL mendaftarkan route `GET /admin/maps-discovery` untuk menampilkan Discovery_Page dan route `POST /admin/maps-discovery/crawl` untuk menjalankan proses crawling.
2. THE Maps_Controller SHALL menerapkan middleware `auth` pada kedua route sehingga hanya user yang sudah login yang dapat mengakses.
3. WHEN user yang tidak terautentikasi mengakses route discovery, THE Maps_Controller SHALL mengarahkan user ke halaman login.
