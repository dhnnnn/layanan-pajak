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
     * @param  string|null  $startPeriode  Jika diisi (format: 'YYYY-MM'), forecast dimulai dari periode ini
     * @return array{historis: array, forecast: array, model_used: string, mae: float, mape: float, aic: float|null}|null
     */
    public function __invoke(string $ayat, string $label, int $horizon = 12, ?string $startPeriode = null, bool $skipCache = false): ?array
    {
        $cacheKey = "forecast:{$ayat}:{$horizon}:{$startPeriode}";
        $cacheTtl = (int) config('forecasting.cache_ttl', 3600);

        if ($cacheTtl > 0 && ! $skipCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $currentYear = (int) now()->year;
        $currentMonth = (int) now()->month;

        $baseQuery = SimpaduMonthlyRealization::query()
            ->where(function ($q) use ($currentYear, $currentMonth): void {
                $q->where('year', '<', $currentYear)
                    ->orWhere(function ($q2) use ($currentYear, $currentMonth): void {
                        $q2->where('year', $currentYear)
                            ->where('month', '<=', $currentMonth); // include bulan berjalan jika datanya sudah ada
                    });
            });

        // Jika ayat = 'all', aggregate semua jenis pajak per bulan
        if ($ayat === 'all') {
            $rows = $baseQuery
                ->selectRaw('year, month, SUM(total_bayar) as total_bayar')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
        } else {
            $rows = $baseQuery
                ->where('ayat', $ayat)
                ->orderBy('year')
                ->orderBy('month')
                ->get(['year', 'month', 'total_bayar']);
        }

        if ($rows->count() < 2) {
            return null;
        }

        $historisData = $rows->map(fn ($r) => [
            'periode' => sprintf('%d-%02d', $r->year, $r->month),
            'nilai' => (float) $r->total_bayar,
        ])->values()->toArray();

        try {
            $payload = [
                'jenis_pajak' => $ayat,
                'data' => $historisData,
                'horizon' => $horizon,
            ];

            if ($startPeriode) {
                $payload['start_periode'] = $startPeriode;
            }

            $response = Http::timeout(config('forecasting.timeout', 60))
                ->post(config('forecasting.url').'/forecast/from-data', $payload);

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
