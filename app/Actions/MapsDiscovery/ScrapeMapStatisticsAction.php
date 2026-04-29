<?php

namespace App\Actions\MapsDiscovery;

use App\Exceptions\ScraperErrorException;
use App\Exceptions\ScraperUnavailableException;
use App\Models\MapsDiscoveryResult;
use App\Models\MapsStatistic;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScrapeMapStatisticsAction
{
    /**
     * Mapping nama hari dari Google Maps ke format database.
     *
     * @var array<string, string>
     */
    private const DAY_MAP = [
        // Indonesian
        'senin' => 'senin',
        'selasa' => 'selasa',
        'rabu' => 'rabu',
        'kamis' => 'kamis',
        'jumat' => 'jumat',
        'sabtu' => 'sabtu',
        'minggu' => 'minggu',
        // English
        'monday' => 'senin',
        'tuesday' => 'selasa',
        'wednesday' => 'rabu',
        'thursday' => 'kamis',
        'friday' => 'jumat',
        'saturday' => 'sabtu',
        'sunday' => 'minggu',
    ];

    /**
     * Ambil statistik kunjungan per jam untuk satu tempat.
     *
     * Prioritas:
     * 1. Baca dari maps_statistics (sudah pernah di-scrape)
     * 2. Konversi dari popular_times JSON yang tersimpan saat crawling awal
     * 3. Scrape ulang via API (fallback jika popular_times kosong)
     *
     * @return array{success: bool, message: string, statistics?: array, source?: string}
     */
    public function __invoke(MapsDiscoveryResult $result): array
    {
        // Prioritas 1: sudah ada di tabel maps_statistics
        $existingCount = MapsStatistic::where('maps_discovery_result_id', $result->id)->count();
        if ($existingCount > 0) {
            return [
                'success' => true,
                'message' => 'Statistik sudah tersedia',
                'source' => 'database',
                'statistics' => $this->buildStatisticsResponse($result),
            ];
        }

        // Prioritas 2: konversi dari popular_times JSON yang tersimpan saat crawling
        if (! empty($result->popular_times)) {
            $this->saveFromPopularTimes($result, $result->popular_times);

            return [
                'success' => true,
                'message' => 'Statistik berhasil dimuat dari data crawling',
                'source' => 'popular_times',
                'statistics' => $this->buildStatisticsResponse($result),
            ];
        }

        // Prioritas 3: scrape ulang via API (untuk data lama yang belum punya popular_times)
        if (! $result->place_id) {
            return [
                'success' => false,
                'message' => 'Place ID tidak tersedia, tidak dapat scrape statistik.',
            ];
        }

        return $this->scrapeFromApi($result);
    }

    /**
     * Konversi popular_times JSON ke tabel maps_statistics.
     *
     * Format popular_times dari scraper:
     * {
     *   "Senin": [{"hour": 10, "occupancy_percent": 25}, ...],
     *   "Selasa": [...],
     *   ...
     * }
     */
    private function saveFromPopularTimes(MapsDiscoveryResult $result, array $popularTimes): void
    {
        DB::transaction(function () use ($result, $popularTimes): void {
            foreach ($popularTimes as $dayName => $hours) {
                $dayKey = self::DAY_MAP[strtolower($dayName)] ?? null;
                if ($dayKey === null || ! is_array($hours)) {
                    continue;
                }

                foreach ($hours as $hourData) {
                    $hour = (int) ($hourData['hour'] ?? -1);
                    $occupancy = (int) ($hourData['occupancy_percent'] ?? 0);

                    if ($hour < 0 || $hour > 23 || $occupancy === 0) {
                        continue;
                    }

                    $hourRange = "{$hour}-".($hour + 1);

                    MapsStatistic::updateOrCreate(
                        [
                            'maps_discovery_result_id' => $result->id,
                            'hour_range' => $hourRange,
                            'day_of_week' => $dayKey,
                        ],
                        ['visitor_count' => $occupancy]
                    );
                }
            }
        });
    }

    /**
     * Scrape statistik dari API (fallback untuk data lama).
     *
     * @return array{success: bool, message: string, statistics?: array, source?: string}
     */
    private function scrapeFromApi(MapsDiscoveryResult $result): array
    {
        // Cek apakah scraper API configured
        if (empty(config('services.scraper.url'))) {
            return [
                'success' => false,
                'message' => 'Scraper API belum dikonfigurasi.',
            ];
        }

        try {
            // Prioritas: gunakan URL langsung jika ada (lebih reliable)
            $payload = [
                'locale' => 'id-ID',
                'timeout_ms' => 240000, // 4 menit untuk collect semua hari
            ];
            
            if (! empty($result->url)) {
                $payload['place_url'] = $result->url;
                Log::info('Sending URL to scraper', [
                    'url_length' => strlen($result->url),
                    'url_preview' => substr($result->url, 0, 100).'...',
                    'full_url' => $result->url,
                ]);
            } elseif (! empty($result->place_id)) {
                $payload['place_id'] = $result->place_id;
            } else {
                return [
                    'success' => false,
                    'message' => 'URL dan Place ID tidak tersedia, tidak dapat scrape statistik.',
                ];
            }

            $response = Http::timeout(config('services.scraper.timeout'))
                ->post(config('services.scraper.url').'/place-stats', $payload);

            if (! $response->successful()) {
                Log::warning('Scraper API error saat ambil statistik', [
                    'payload' => $payload,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal mengambil data dari scraper API (HTTP '.$response->status().').',
                ];
            }

            $data = $response->json();
            $popularTimes = $data['popular_times'] ?? [];

            if (empty($popularTimes)) {
                // Tandai bahwa sudah dicoba tapi tidak tersedia, agar tidak scrape ulang terus
                $result->update(['popular_times' => ['_unavailable' => true]]);

                return [
                    'success' => false,
                    'message' => 'Data popular times tidak tersedia untuk tempat ini di Google Maps.',
                ];
            }

            // Simpan ke popular_times dan maps_statistics
            $result->update(['popular_times' => $popularTimes]);
            $this->saveFromPopularTimes($result, $popularTimes);

            return [
                'success' => true,
                'message' => 'Statistik berhasil di-scrape dari Google Maps',
                'source' => 'scraper_api',
                'statistics' => $this->buildStatisticsResponse($result),
            ];
        } catch (ConnectionException $e) {
            Log::error('Scraper API unreachable saat ambil statistik', [
                'url' => $result->url ?? null,
                'place_id' => $result->place_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Layanan scraper tidak dapat dijangkau. Pastikan scraper API sedang berjalan.',
            ];
        } catch (\Exception $e) {
            Log::error('Unexpected error saat scrape statistik', [
                'url' => $result->url ?? null,
                'place_id' => $result->place_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Bangun response statistik dari tabel maps_statistics.
     *
     * @return array{table: array, day_totals: array, hour_totals: array, grand_total: int}
     */
    public function buildStatisticsResponse(MapsDiscoveryResult $result): array
    {
        $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

        $stats = MapsStatistic::where('maps_discovery_result_id', $result->id)
            ->get()
            ->groupBy('day_of_week');

        // Bangun tabel: hour_range → [senin, selasa, ..., minggu, total]
        $table = [];
        $dayTotals = array_fill_keys($days, 0);
        $grandTotal = 0;

        foreach ($stats as $day => $dayStats) {
            foreach ($dayStats as $stat) {
                $hr = $stat->hour_range;
                if (! isset($table[$hr])) {
                    $table[$hr] = array_fill_keys($days, 0);
                }
                $table[$hr][$day] = $stat->visitor_count;
                $dayTotals[$day] += $stat->visitor_count;
                $grandTotal += $stat->visitor_count;
            }
        }

        // Sort by hour
        uksort($table, fn ($a, $b) => (int) explode('-', $a)[0] <=> (int) explode('-', $b)[0]);

        // Tambahkan row total per jam
        $hourTotals = [];
        foreach ($table as $hr => $dayCounts) {
            $hourTotals[$hr] = array_sum($dayCounts);
        }

        return [
            'table' => $table,
            'day_totals' => $dayTotals,
            'hour_totals' => $hourTotals,
            'grand_total' => $grandTotal,
        ];
    }
}
