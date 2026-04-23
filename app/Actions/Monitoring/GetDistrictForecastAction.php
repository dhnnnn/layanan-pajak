<?php

namespace App\Actions\Monitoring;

use App\Models\District;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetDistrictForecastAction
{
    /**
     * Ambil data historis realisasi per bulan untuk satu kecamatan,
     * lalu kirim ke ARIMA API untuk mendapatkan prediksi.
     *
     * @return array{kecamatan: string, historis: array, forecast: array, model_used: string, mae: float, mape: float, aic: float|null}|null
     */
    public function __invoke(District $district, ?string $noAyat = null, int $horizon = 12): ?array
    {
        $cacheKey = "forecast:district:{$district->id}:".($noAyat ?? 'all').":{$horizon}";
        $cacheTtl = (int) config('forecasting.cache_ttl', 3600);

        if ($cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Ambil data realisasi bulanan per kecamatan dari simpadu_tax_payers
        // Gunakan month > 0 (data per bulan, bukan summary month=0)
        $rows = DB::table('simpadu_tax_payers')
            ->where('kd_kecamatan', $district->simpadu_code)
            ->when($noAyat, fn ($q) => $q->where('ayat', $noAyat))
            ->where('status', '1')
            ->where('month', '>', 0)
            ->selectRaw('year, month, SUM(total_bayar) as total_bayar, SUM(total_ketetapan) as total_ketetapan')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        if ($rows->count() < 2) {
            return null;
        }

        // Ambil data sampai bulan terakhir yang berurutan (potong di gap pertama)
        // agar ARIMA tidak terpengaruh data acak di bulan-bulan yang tidak berurutan.
        // Juga potong jika nilai bulan terakhir < 20% rata-rata (bulan belum selesai lapor)
        $validRows = collect();
        $prevYear = null;
        $prevMonth = null;

        foreach ($rows as $r) {
            if ((float) $r->total_bayar <= 0) {
                if ($prevYear !== null) {
                    break;
                }

                continue;
            }

            if ($prevYear !== null) {
                $expectedYear = $prevMonth === 12 ? $prevYear + 1 : $prevYear;
                $expectedMonth = $prevMonth === 12 ? 1 : $prevMonth + 1;
                if ((int) $r->year !== $expectedYear || (int) $r->month !== $expectedMonth) {
                    break;
                }
            }

            $validRows->push($r);
            $prevYear = (int) $r->year;
            $prevMonth = (int) $r->month;
        }

        // Potong bulan-bulan di akhir yang nilainya < 50% rata-rata historis
        // (bulan yang belum selesai lapor / data belum lengkap)
        if ($validRows->count() >= 2) {
            $avg = $validRows->avg(fn ($r) => (float) $r->total_bayar);
            while ($validRows->count() >= 2 && (float) $validRows->last()->total_bayar < $avg * 0.50) {
                $validRows->pop();
            }
        }

        if ($validRows->count() < 2) {
            return null;
        }

        $historisData = $validRows->map(fn ($r) => [
            'periode' => sprintf('%d-%02d', $r->year, $r->month),
            'nilai' => (float) $r->total_bayar,
        ])->values()->toArray();

        // Ketetapan: ikuti periode yang sama dengan validRows (sudah dipotong di gap)
        // agar tidak ada data acak Juli/Agustus yang masuk
        $ketetapanData = $validRows->filter(fn ($r) => (float) $r->total_ketetapan > 0)
            ->map(fn ($r) => [
                'periode' => sprintf('%d-%02d', $r->year, $r->month),
                'nilai' => (float) $r->total_ketetapan,
            ])->values()->toArray();

        try {
            $response = Http::timeout(config('forecasting.timeout', 60))
                ->post(config('forecasting.url').'/forecast/from-data', [
                    'jenis_pajak' => 'realisasi_'.$district->simpadu_code.($noAyat ? "_{$noAyat}" : ''),
                    'data' => $historisData,
                    'horizon' => $horizon,
                ]);

            if (! $response->successful()) {
                Log::warning('District forecast service error', [
                    'district' => $district->name,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $json = $response->json();

            // Tambahkan field kecamatan & total_ketetapan ke response
            $result = array_merge($json, [
                'kecamatan' => $district->name,
                'total_ketetapan' => $ketetapanData,
            ]);

            if ($cacheTtl > 0) {
                Cache::put($cacheKey, $result, $cacheTtl);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('District forecast service unreachable', [
                'district' => $district->name,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
