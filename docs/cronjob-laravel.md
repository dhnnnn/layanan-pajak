# Panduan Konfigurasi Cronjob Laravel (Auto-Sync)

Dokumen ini menjelaskan cara mengonfigurasi dan menjalankan tugas sinkronisasi data secara otomatis menggunakan fitur Penjadwalan Tugas (Task Scheduling) Laravel.

## 1. Konfigurasi Waktu Terjadwal

Waktu pelaksanaan sinkronisasi harian dapat diatur melalui file `.env`. Gunakan variabel `SYNC_DAILY_HOUR` dengan format `HH:MM`.

```env
# Contoh: Jalankan setiap jam 2 pagi
SYNC_DAILY_HOUR=02:00
```

Jika variabel ini tidak diatur, sistem akan menggunakan waktu default yaitu `02:00`.

## 2. Command yang Dijadwalkan

Sistem telah dikonfigurasi untuk menjalankan dua perintah utama secara harian:

1.  **`simpadu:sync`**: Sinkronisasi data referensi (kecamatan, jenis pajak), data Wajib Pajak, dan target pajak tahun berjalan.
2.  **`simpadu:sync-payers`**: Sinkronisasi realisasi pembayaran Wajib Pajak tahun berjalan.

Log hasil sinkronisasi dapat dilihat di:
- `storage/logs/simpadu_sync.log`
- `storage/logs/simpadu_payers_sync.log`

## 3. Implementasi di Server Produksi

Agar penjadwalan Laravel berjalan otomatis di server produksi, Anda perlu menambahkan satu entri ke **crontab** server.

### Langkah-langkah:

1.  Masuk ke server via SSH.
2.  Buka editor crontab dengan perintah:
    ```bash
    crontab -e
    ```
3.  Tambahkan baris berikut di bagian paling bawah:
    ```bash
    * * * * * cd /path-ke-projek-anda && php artisan schedule:run >> /dev/null 2>&1
    ```
    > [!IMPORTANT]
    > Ganti `/path-ke-projek-anda` dengan path absolut lokasi aplikasi di server (contoh: `/var/www/layanan-pajak`).

### Penjelasan Crontab:
- `* * * * *`: Menjalankan cron setiap menit.
- `php artisan schedule:run`: Perintah Laravel untuk memeriksa apakah ada tugas yang harus dijalankan pada menit tersebut.
- `>> /dev/null 2>&1`: Membuang output agar tidak memenuhi email sistem/log sistem (opsional, karena kita sudah mencatat log ke file khusus di Laravel).

## 4. Verifikasi Penjadwalan

Untuk memastikan jadwal sudah terdaftar dengan benar di aplikasi, jalankan perintah berikut di terminal:

```bash
php artisan schedule:list
```

Anda akan melihat daftar command beserta waktu eksekusi berikutnya.

---

> [!TIP]
> Pastikan zona waktu di server (`timezone` di `config/app.php` dan waktu sistem operasi) sudah sesuai agar sinkronisasi berjalan sesuai jam yang diinginkan.
