<?php

namespace App\Actions\Tax;

use App\Models\SimpaduMonthlyRealization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetTaxForecastAction
{
    /**
     * Ambil forecast realisasi pajak untuk satu ayat (jenis pajak).
     *
     * @param  string  $ayat  Kode ayat pajak, misal "41101"
     * @param  string  $label  Label tampilan, misal "Pajak Hotel"
     * @param  int  $horizon  Jumlah bulan ke depan yang di-forecast
     * @return array{historis: array, forecast: array, model_used: string, mae: float, mape: float, aic: float|null}|null
     */
    public function __invoke(string $ayat, string $label, int $horizon = 12): ?array
    {
        $cacheKey = "forecast:{$ayat}:{$horizon}";
        $cacheTtl = (int) config('forecasting.cache_ttl', 3600);

        if ($cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Exclude bulan berjalan yang belum selesai agar tidak merusak pola ARIMA.
        // Bulan dianggap "selesai" jika sudah lewat dari bulan saat ini.
        $currentYear = (int) now()->year;
        $currentMonth = (int) now()->month;

        $rows = SimpaduMonthlyRealization::query()
            ->where('ayat', $ayat)
            ->where(function ($q) use ($currentYear, $currentMonth): void {
                // Ambil semua bulan dari tahun-tahun sebelumnya
                $q->where('year', '<', $currentYear)
                  // Atau bulan yang sudah selesai di tahun berjalan (bulan < bulan sekarang)
                    ->orWhere(function ($q2) use ($currentYear, $currentMonth): void {
                        $q2->where('year', $currentYear)
                            ->where('month', '<', $currentMonth);
                    });
            })
            ->orderBy('year')
            ->orderBy('month')
            ->get(['year', 'month', 'total_bayar']);

        if ($rows->count() < 2) {
            return null;
        }

        // Format ke array {periode: "YYYY-MM", nilai: float}
        $historisData = $rows->map(fn ($r) => [
            'periode' => sprintf('%d-%02d', $r->year, $r->month),
            'nilai' => (float) $r->total_bayar,
        ])->values()->toArray();

        try {
            $response = Http::timeout(config('forecasting.timeout', 60))
                ->post(config('forecasting.url').'/forecast/from-data', [
                    'jenis_pajak' => $ayat,
                    'data' => $historisData,
                    'horizon' => $horizon,
                ]);

            if (! $response->successful()) {
                Log::warning('Forecasting service error', [
                    'ayat' => $ayat,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $result = $response->json();
            $result['label'] = $label;

            if ($cacheTtl > 0) {
                Cache::put($cacheKey, $result, $cacheTtl);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Forecasting service unreachable', [
                'ayat' => $ayat,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
