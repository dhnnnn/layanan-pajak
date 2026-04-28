<?php

namespace App\Actions\MapsDiscovery;

use App\Exceptions\ScraperErrorException;
use App\Exceptions\ScraperUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrawlMapsAction
{
    /**
     * Mapping kode ayat pajak ke keyword pencarian Google Maps.
     *
     * @var array<string, list<string>>
     */
    public const KEYWORD_MAPPING = [
        '41101' => ['hotel'],
        '41102' => ['restoran', 'cafe', 'rumah makan'],
        '41103' => ['hiburan', 'karaoke', 'bioskop'],
        '41104' => ['parkir'],
        '41105' => ['penerangan jalan'],
        '41107' => ['reklame'],
        '41108' => ['air tanah'],
        '41111' => ['sarang burung walet'],
    ];

    /**
     * Crawl lokasi bisnis dari Google Maps via Scraper API.
     *
     * @param  list<string>  $keywords
     * @param  string  $area  Nama wilayah pencarian
     * @param  list<string>  $excludePlaceIds  Place ID yang sudah ada di DB, scraper akan skip
     */
    public function __invoke(array $keywords, string $area, int $maxResults = 20, array $excludePlaceIds = []): Collection
    {
        $combinedKeyword = implode(' ', $keywords);

        return $this->fetchResults($combinedKeyword, $area, $maxResults, $excludePlaceIds);
    }

    /**
     * @param  list<string>  $excludePlaceIds
     * @return Collection<int, array{title: string, subtitle: string, category: string, place_id: string, url: string, latitude: ?float, longitude: ?float}>
     */
    private function fetchResults(string $keyword, string $area, int $maxResults, array $excludePlaceIds = []): Collection
    {
        $query = "{$keyword} {$area}";

        try {
            // POST agar exclude_place_ids bisa dikirim sebagai array di body (tidak kena URL length limit)
            $response = Http::timeout(config('services.scraper.timeout'))
                ->post(config('services.scraper.url').'/search', [
                    'query' => $query,
                    'max_results' => $maxResults,
                    'locale' => 'id-ID',
                    'exclude_place_ids' => $excludePlaceIds,
                ]);

            if (! $response->successful()) {
                Log::warning('Scraper API error', [
                    'keyword' => $keyword,
                    'area' => $area,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new ScraperErrorException;
            }

            $items = $response->json('results', []);

            return collect($items)->map(fn (array $item): array => [
                'title' => $item['title'] ?? '',
                'subtitle' => $this->cleanAddress($item['subtitle'] ?? ''),
                'category' => $item['category'] ?? '',
                'place_id' => $item['place_id'] ?? '',
                'url' => $item['url'] ?? '',
                'latitude' => $item['latitude'] ?? null,
                'longitude' => $item['longitude'] ?? null,
                'rating' => $item['rating'] ?? null,
                'reviews' => $item['reviews'] ?? null,
                'price_range' => $item['price_range'] ?? null,
            ]);
        } catch (ConnectionException $e) {
            Log::error('Scraper API unreachable', [
                'keyword' => $keyword,
                'area' => $area,
                'error' => $e->getMessage(),
            ]);

            throw new ScraperUnavailableException(previous: $e);
        }
    }

    /**
     * Bersihkan alamat dari karakter unicode/emoji dan whitespace berlebih.
     */
    private function cleanAddress(string $address): string
    {
        $cleaned = preg_replace('/[\x{1F000}-\x{1FFFF}|\x{2600}-\x{27FF}|\x{FE00}-\x{FEFF}|\x{E000}-\x{F8FF}]/u', '', $address);
        $cleaned = preg_replace('/[\x{25A0}-\x{25FF}]/u', '', $cleaned ?? $address);

        return trim(preg_replace('/\s+/', ' ', $cleaned ?? $address));
    }
}
