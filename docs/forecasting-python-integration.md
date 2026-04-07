# Panduan Integrasi Forecasting Python ke Laravel

Dokumen ini menjelaskan mekanisme dan arsitektur untuk menjalankan model forecasting (prediksi realisasi pajak) yang ditulis dalam Python dari dalam aplikasi Laravel.

---

## 1. Gambaran Umum

Tujuannya adalah memprediksi realisasi penerimaan pajak ke depan berdasarkan data historis yang sudah ada di database. Model forecasting ditulis di Python (misalnya menggunakan `statsmodels`, `scikit-learn`, atau `prophet`), lalu dipanggil oleh Laravel dan hasilnya dikembalikan ke aplikasi.

### Alur Kerja Umum

```
[Laravel Controller / Artisan Command]
        |
        | (exec / HTTP / Queue)
        v
[Python Script / API Server]
        |
        | (baca data dari DB / terima input JSON)
        v
[Model Forecasting (ARIMA, Prophet, dll)]
        |
        | (output JSON hasil prediksi)
        v
[Laravel menerima hasil & simpan / tampilkan]
```

---

## 2. Pilihan Mekanisme Integrasi

Ada **3 pendekatan** yang bisa digunakan, masing-masing dengan trade-off berbeda.

---

### Opsi A: `Process` — Panggil Script Python Langsung

Laravel memanggil script Python menggunakan `Symfony\Component\Process\Process` (sudah include di Laravel).

**Kapan digunakan:** Forecasting tidak sering dipanggil, atau dijalankan via Artisan command / queue job.

#### Contoh Artisan Command

```php
// app/Console/Commands/RunForecastingCommand.php

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RunForecastingCommand extends Command
{
    protected $signature = 'forecast:run {--year=} {--no_ayat=}';
    protected $description = 'Jalankan forecasting realisasi pajak menggunakan Python';

    public function handle(): int
    {
        $year   = $this->option('year') ?? now()->year;
        $noAyat = $this->option('no_ayat') ?? 'all';

        $scriptPath = base_path('python/forecasting.py');

        $process = new Process([
            'python3', $scriptPath,
            '--year', $year,
            '--no_ayat', $noAyat,
        ]);

        $process->setTimeout(120); // 2 menit
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error($process->getErrorOutput());
            return self::FAILURE;
        }

        $result = json_decode($process->getOutput(), true);

        // Simpan hasil ke database atau cache
        // ForecastResult::upsert($result, ...);

        $this->info('Forecasting selesai.');
        return self::SUCCESS;
    }
}
```

#### Contoh Script Python (`python/forecasting.py`)

```python
# python/forecasting.py
import argparse
import json
import pymysql
import pandas as pd
from statsmodels.tsa.holtwinters import ExponentialSmoothing

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--year', type=int, required=True)
    parser.add_argument('--no_ayat', type=str, default='all')
    args = parser.parse_args()

    # Koneksi ke database
    conn = pymysql.connect(
        host='127.0.0.1',
        user='root',
        password='',
        database='layanan_pajak'
    )

    query = """
        SELECT bulan, SUM(total_realisasi) as realisasi
        FROM realization_data
        WHERE year = %s
        GROUP BY bulan
        ORDER BY bulan
    """
    df = pd.read_sql(query, conn, params=[args.year])
    conn.close()

    # Model forecasting
    model = ExponentialSmoothing(df['realisasi'], trend='add').fit()
    forecast = model.forecast(3)  # prediksi 3 bulan ke depan

    output = {
        'year': args.year,
        'forecast': forecast.tolist(),
    }

    # Output ke stdout sebagai JSON — dibaca oleh Laravel
    print(json.dumps(output))

if __name__ == '__main__':
    main()
```

> [!IMPORTANT]
> Script Python **harus** output ke `stdout` dalam format JSON. Laravel membaca output ini via `$process->getOutput()`.

---

### Opsi B: Python sebagai Microservice HTTP (Direkomendasikan untuk Produksi)

Python dijalankan sebagai server API terpisah menggunakan **FastAPI** atau **Flask**. Laravel memanggil endpoint HTTP-nya menggunakan `Http::post(...)`.

**Kapan digunakan:** Forecasting sering dipanggil, model berat (Prophet, LSTM), atau butuh response cepat di dashboard.

#### Arsitektur

```
Laravel App  ──HTTP POST──>  Python FastAPI Server (port 8001)
                                    |
                              Model Forecasting
                                    |
                             JSON Response
```

#### Contoh Python FastAPI Server (`python/server.py`)

```python
# python/server.py
from fastapi import FastAPI
from pydantic import BaseModel
from typing import List
import pandas as pd
from statsmodels.tsa.holtwinters import ExponentialSmoothing

app = FastAPI()

class ForecastRequest(BaseModel):
    data: List[float]   # data realisasi historis per bulan
    periods: int = 3    # berapa bulan ke depan

@app.post("/forecast")
def forecast(req: ForecastRequest):
    series = pd.Series(req.data)
    model = ExponentialSmoothing(series, trend='add').fit()
    result = model.forecast(req.periods).tolist()
    return {"forecast": result}
```

Jalankan server:
```bash
uvicorn python.server:app --host 127.0.0.1 --port 8001
```

#### Panggil dari Laravel

```php
use Illuminate\Support\Facades\Http;

$response = Http::post(config('services.forecasting.url') . '/forecast', [
    'data'    => $historicalData,  // array realisasi bulanan
    'periods' => 3,
]);

$forecast = $response->json('forecast');
```

Tambahkan ke `config/services.php`:
```php
'forecasting' => [
    'url' => env('FORECASTING_SERVICE_URL', 'http://127.0.0.1:8001'),
],
```

Dan di `.env`:
```env
FORECASTING_SERVICE_URL=http://127.0.0.1:8001
```

---

### Opsi C: Queue Job + Exec (Async, Non-Blocking)

Forecasting dijalankan di background via Laravel Queue. Cocok jika proses Python memakan waktu lama dan tidak perlu hasil real-time.

```php
// app/Jobs/RunForecastingJob.php

class RunForecastingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $year,
        public readonly string $noAyat,
    ) {}

    public function handle(): void
    {
        $process = new Process([
            'python3', base_path('python/forecasting.py'),
            '--year', $this->year,
            '--no_ayat', $this->noAyat,
        ]);

        $process->setTimeout(300);
        $process->run();

        if ($process->isSuccessful()) {
            $result = json_decode($process->getOutput(), true);
            // Simpan hasil ke tabel forecast_results
        }
    }
}
```

Dispatch dari controller:
```php
RunForecastingJob::dispatch($year, $noAyat);
```

---

## 3. Struktur File yang Disarankan

```
layanan-pajak/
├── app/
│   ├── Actions/Admin/
│   │   └── RunForecastingAction.php      ← Action untuk trigger forecasting
│   ├── Jobs/
│   │   └── RunForecastingJob.php         ← (Opsi C) Queue job
│   └── Models/
│       └── ForecastResult.php            ← Model untuk simpan hasil
├── python/
│   ├── forecasting.py                    ← Script utama forecasting
│   ├── server.py                         ← (Opsi B) FastAPI server
│   └── requirements.txt                  ← Dependensi Python
├── database/
│   └── migrations/
│       └── xxxx_create_forecast_results_table.php
└── docs/
    └── forecasting-python-integration.md ← Dokumen ini
```

---

## 4. Tabel Database untuk Menyimpan Hasil Forecast

```php
// Migration: create_forecast_results_table

Schema::create('forecast_results', function (Blueprint $table) {
    $table->id();
    $table->string('no_ayat');
    $table->year('year');
    $table->tinyInteger('bulan');           // bulan yang diprediksi (1-12)
    $table->decimal('forecast_value', 15, 2);
    $table->string('model_used')->default('exponential_smoothing');
    $table->timestamp('generated_at')->useCurrent();
    $table->timestamps();

    $table->unique(['no_ayat', 'year', 'bulan']);
});
```

---

## 5. Perbandingan Opsi

| Kriteria              | Opsi A (exec)       | Opsi B (HTTP API)     | Opsi C (Queue+exec)   |
|-----------------------|---------------------|-----------------------|-----------------------|
| Kompleksitas setup    | Rendah              | Sedang                | Sedang                |
| Performa              | Lambat (cold start) | Cepat (server hidup)  | Non-blocking          |
| Cocok untuk           | Cron / Artisan      | Real-time dashboard   | Proses berat async    |
| Kebutuhan server      | Python terinstall   | Python + port terbuka | Python + Queue worker |
| Rekomendasi           | Dev / Cron harian   | Produksi real-time    | Produksi async        |

---

## 6. Instalasi Dependensi Python

Buat file `python/requirements.txt`:

```txt
pandas
statsmodels
pymysql
python-dotenv
prophet          # opsional, untuk model Prophet
scikit-learn     # opsional, untuk ML-based forecasting
fastapi          # opsional, untuk Opsi B
uvicorn          # opsional, untuk Opsi B
```

Install:
```bash
pip install -r python/requirements.txt
```

---

## 7. Tips Keamanan

- Jangan hardcode kredensial database di script Python. Gunakan `python-dotenv` untuk membaca dari file `.env`.
- Jika menggunakan Opsi B (HTTP API), pastikan port Python **tidak** terekspos ke publik. Binding ke `127.0.0.1` saja.
- Validasi dan sanitasi semua input sebelum dikirim ke script Python untuk menghindari command injection.
- Gunakan `$process->setTimeout()` agar proses Python tidak berjalan selamanya jika terjadi error.

---

## 8. Rekomendasi untuk Proyek Ini

Mengingat aplikasi ini sudah menggunakan Artisan Command dan Queue (lihat `docs/cronjob-laravel.md`), pendekatan yang paling sesuai adalah:

1. **Opsi A** untuk forecasting yang dijalankan via cron harian — misalnya setiap malam setelah sync data dari Simpadu, hasilnya di-pre-compute dan disimpan ke tabel `forecast_results`.
2. **Opsi B** jika nanti dibutuhkan tampilan forecasting real-time di dashboard monitoring realisasi (`RealizationMonitoringController`).

Kombinasi keduanya juga memungkinkan: cron harian menggunakan `exec()` untuk pre-compute forecast, hasilnya disimpan ke tabel `forecast_results`, lalu dashboard hanya membaca dari tabel tersebut tanpa perlu memanggil Python secara langsung saat user membuka halaman.
