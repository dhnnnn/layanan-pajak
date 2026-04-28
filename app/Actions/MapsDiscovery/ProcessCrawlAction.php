<?php

namespace App\Actions\MapsDiscovery;

use App\Models\District;
use App\Models\MapsDiscoveryResult;
use App\Models\TaxType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProcessCrawlAction
{
    public function __construct(
        private CrawlMapsAction $crawlAction,
    ) {}

    /**
     * Crawl lokasi bisnis dari Google Maps dan simpan ke DB.
     *
     * @param  array{tax_type_code?: string, keyword?: string, district_id?: string, village?: string, max_results?: int}  $validated
     * @return array{results: Collection, stats: array{total: int, terdaftar: int, potensi_baru: int, belum_dicek: int, new_from_crawl: int}, message?: string}
     */
    public function __invoke(array $validated): array
    {
        // Build keywords
        $keywords = [];
        $ayat = null;

        if (! empty($validated['tax_type_code'])) {
            $taxType = TaxType::query()->where('simpadu_code', $validated['tax_type_code'])->first();
            if ($taxType) {
                $ayat = $taxType->simpadu_code;
                $keywords = CrawlMapsAction::KEYWORD_MAPPING[$ayat]
                    ?? CrawlMapsAction::KEYWORD_MAPPING[explode('-', $ayat)[0]]
                    ?? [];
            }
        }

        if (! empty($validated['keyword'])) {
            $parts = preg_split('/[,;.]+/', strip_tags($validated['keyword']), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parts as $part) {
                $clean = trim(preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $part));
                if ($clean !== '' && ! in_array(strtolower($clean), array_map('strtolower', $keywords))) {
                    $keywords[] = $clean;
                }
            }
        }

        // Build area — kecamatan + desa untuk scope lebih kecil
        $area = 'Pasuruan';
        $districtName = null;
        $districtShort = null;

        if (! empty($validated['district_id'])) {
            $district = District::query()->find($validated['district_id']);
            if ($district) {
                $districtShort = preg_replace('/^kecamatan\s+/i', '', $district->name);
                $districtName = $district->name;

                // Jika ada desa, tambahkan ke area untuk scope lebih kecil
                $village = trim($validated['village'] ?? '');
                if ($village !== '') {
                    $area = "{$village} {$districtShort} Pasuruan";
                } else {
                    $area = "{$districtShort} Pasuruan";
                }
            }
        }

        // Exclude hanya place_id yang sesuai filter saat ini (ayat + kecamatan)
        $excludeQuery = MapsDiscoveryResult::query()
            ->whereNotNull('place_id')
            ->where('place_id', '!=', '');

        if ($ayat) {
            $excludeQuery->where('tax_type_code', $ayat);
        }
        if ($districtName) {
            $excludeQuery->where('district_name', $districtName);
        }

        $existingPlaceIds = $excludeQuery->pluck('place_id')
            ->unique()
            ->values()
            ->toArray();

        $maxResults = (int) ($validated['max_results'] ?? 20);
        $crawlResults = ($this->crawlAction)($keywords, $area, $maxResults, $existingPlaceIds);

        // Filter: hanya hasil yang alamatnya mengandung nama kecamatan (jika dipilih)
        if ($districtShort !== null) {
            $needle = strtolower($districtShort);
            $crawlResults = $crawlResults->filter(function (array $item) use ($needle): bool {
                $subtitle = strtolower($item['subtitle'] ?? '');

                return str_contains($subtitle, $needle)
                    || str_contains($subtitle, 'pasuruan');
            })->values();
        }

        if ($crawlResults->isEmpty()) {
            return [
                'results' => collect(),
                'stats' => ['total' => 0, 'new' => 0],
                'message' => 'Tidak ditemukan lokasi baru di wilayah ini.',
            ];
        }

        // Simpan langsung ke DB — tanpa matching, status default "belum_dicek"
        $sessionId = Str::uuid()->toString();
        $userId = auth()->id();
        $keywordStr = implode(', ', $keywords);
        $saved = 0;

        foreach ($crawlResults as $item) {
            $placeId = $item['place_id'] ?? '';
            if (empty($placeId)) {
                continue;
            }

            MapsDiscoveryResult::updateOrCreate(
                ['place_id' => $placeId],
                [
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'title' => $item['title'],
                    'subtitle' => $item['subtitle'] ?? null,
                    'category' => $item['category'] ?? null,
                    'url' => $item['url'] ?? null,
                    'latitude' => $item['latitude'] ?? null,
                    'longitude' => $item['longitude'] ?? null,
                    'rating' => $item['rating'] ?? null,
                    'reviews' => $item['reviews'] ?? null,
                    'price_range' => $item['price_range'] ?? null,
                    'status' => 'belum_dicek',
                    'matched_npwpd' => null,
                    'matched_name' => null,
                    'similarity_score' => 0,
                    'tax_type_code' => $ayat,
                    'district_name' => $districtName,
                    'keyword' => $keywordStr,
                ],
            );
            $saved++;
        }

        // Return semua data (baru + lama) untuk ditampilkan di maps
        $allResults = MapsDiscoveryResult::query()
            ->when($ayat, fn ($q) => $q->where('tax_type_code', $ayat))
            ->when($districtName, fn ($q) => $q->where('district_name', $districtName))
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (MapsDiscoveryResult $r): array => [
                'title' => $r->title,
                'subtitle' => $r->subtitle,
                'category' => $r->category,
                'place_id' => $r->place_id,
                'url' => $r->url,
                'latitude' => $r->latitude,
                'longitude' => $r->longitude,
                'rating' => $r->rating,
                'reviews' => $r->reviews,
                'price_range' => $r->price_range,
                'status' => $r->status,
                'matched_npwpd' => $r->matched_npwpd,
                'matched_name' => $r->matched_name,
            ]);

        $terdaftar = $allResults->where('status', 'terdaftar')->count();
        $potensiBaru = $allResults->where('status', 'potensi_baru')->count();
        $belumDicek = $allResults->where('status', 'belum_dicek')->count();

        return [
            'results' => $allResults->values(),
            'stats' => [
                'total' => $allResults->count(),
                'terdaftar' => $terdaftar,
                'potensi_baru' => $potensiBaru,
                'belum_dicek' => $belumDicek,
                'new_from_crawl' => $saved,
            ],
        ];
    }
}
