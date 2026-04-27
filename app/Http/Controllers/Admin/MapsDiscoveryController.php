<?php

namespace App\Http\Controllers\Admin;

use App\Actions\MapsDiscovery\CrawlMapsAction;
use App\Actions\MapsDiscovery\MatchTaxPayersAction;
use App\Exceptions\ScraperErrorException;
use App\Exceptions\ScraperUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CrawlMapsDiscoveryRequest;
use App\Models\District;
use App\Models\MapsDiscoveryResult;
use App\Models\TaxType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MapsDiscoveryController extends Controller
{
    public function index(): View
    {
        $allowedPrefixes = array_keys(CrawlMapsAction::KEYWORD_MAPPING);

        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->whereNotNull('simpadu_code')
            ->whereIn('simpadu_code', $allowedPrefixes)
            ->orderBy('name')
            ->get(['id', 'name', 'simpadu_code']);

        $districts = District::query()->orderBy('name')->get();

        return view('admin.maps-discovery.index', [
            'taxTypes' => $taxTypes,
            'districts' => $districts,
        ]);
    }

    public function crawl(
        CrawlMapsDiscoveryRequest $request,
        CrawlMapsAction $crawlAction,
        MatchTaxPayersAction $matchAction,
    ): JsonResponse {
        ignore_user_abort(false);
        $validated = $request->validated();

        // === Build keywords ===
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

        // === Build area ===
        $area = 'Pasuruan';
        $kdKecamatan = null;
        $districtShort = null;
        $districtName = null;

        if (! empty($validated['district_id'])) {
            $district = District::query()->find($validated['district_id']);
            if ($district) {
                $districtShort = preg_replace('/^kecamatan\s+/i', '', $district->name);
                $districtName = $district->name;
                $area = "{$districtShort} Pasuruan";
                $kdKecamatan = $district->simpadu_code;
            }
        }

        try {
            // === Load data lama dari DB (yang sudah pernah di-crawl) ===
            $existingDbQuery = MapsDiscoveryResult::query()
                ->when($ayat, fn ($q) => $q->where('tax_type_code', $ayat));

            // Filter kecamatan di data lama
            if ($districtName) {
                $existingDbQuery->where('district_name', $districtName);
            }

            $existingFromDb = $existingDbQuery->orderByDesc('updated_at')->get();

            $existingPlaceIds = $existingFromDb
                ->pluck('place_id')
                ->filter(fn ($id) => ! empty($id))
                ->unique()
                ->values()
                ->toArray();

            // === Crawl data baru (skip yang sudah ada) ===
            $maxResults = (int) ($validated['max_results'] ?? 20);
            $crawlResults = $crawlAction($keywords, $area, $maxResults, $existingPlaceIds);

            $newMatchedResults = collect();

            if ($crawlResults->isNotEmpty()) {
                $newMatchedResults = $matchAction($crawlResults, $ayat, $kdKecamatan);

                // Filter kecamatan dari alamat
                if ($districtShort !== null) {
                    $needle = strtolower($districtShort);
                    $newMatchedResults = $newMatchedResults->filter(function (array $item) use ($needle): bool {
                        $parsedDistrict = strtolower($item['parsed_district'] ?? '');
                        if ($parsedDistrict !== '') {
                            return str_contains($parsedDistrict, $needle) || str_contains($needle, $parsedDistrict);
                        }

                        return str_contains(strtolower($item['subtitle'] ?? ''), $needle);
                    })->values();
                }

                // === Simpan data baru — hanya yang punya place_id valid ===
                $sessionId = Str::uuid()->toString();
                $userId = auth()->id();
                $keywordStr = implode(', ', $keywords);

                $newMatchedResults->each(function (array $item) use ($sessionId, $userId, $ayat, $districtName, $keywordStr): void {
                    $placeId = $item['place_id'] ?? '';
                    if (empty($placeId)) {
                        return; // Skip tanpa place_id — tidak bisa deduplicate
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
                            'status' => $item['status'],
                            'matched_npwpd' => $item['matched_npwpd'] ?? null,
                            'matched_name' => $item['matched_name'] ?? null,
                            'similarity_score' => $item['similarity_score'] ?? 0,
                            'tax_type_code' => $ayat,
                            'district_name' => $districtName,
                            'keyword' => $keywordStr,
                        ],
                    );
                });
            }

            // === Gabungkan: data lama + data baru untuk response ===
            $existingMapped = $existingFromDb->map(fn (MapsDiscoveryResult $r): array => [
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
                'similarity_score' => $r->similarity_score,
                'parsed_district' => $r->district_name,
                'is_new' => false,
            ]);

            $newWithFlag = $newMatchedResults->map(fn (array $item): array => array_merge($item, ['is_new' => true]));

            // Deduplicate: data baru overwrite data lama dengan place_id sama
            $newPlaceIds = $newWithFlag->pluck('place_id')->filter()->toArray();
            $existingFiltered = $existingMapped->filter(
                fn (array $item): bool => empty($item['place_id']) || ! in_array($item['place_id'], $newPlaceIds, true)
            );

            // Data baru di atas, data lama di bawah
            $allResults = $newWithFlag->merge($existingFiltered)->values();

            $terdaftar = $allResults->where('status', 'terdaftar')->count();
            $potensiBaru = $allResults->where('status', 'potensi_baru')->count();

            return response()->json([
                'results' => $allResults,
                'stats' => [
                    'total' => $allResults->count(),
                    'terdaftar' => $terdaftar,
                    'potensi_baru' => $potensiBaru,
                    'new_from_crawl' => $newMatchedResults->count(),
                ],
                'message' => $crawlResults->isEmpty() && $existingFromDb->isNotEmpty()
                    ? 'Tidak ada data baru. Menampilkan '.$existingFromDb->count().' data dari database.'
                    : null,
            ]);
        } catch (ScraperUnavailableException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        } catch (ScraperErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function report(Request $request): View
    {
        $query = MapsDiscoveryResult::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('tax_type_code')) {
            $query->where('tax_type_code', $request->input('tax_type_code'));
        }
        if ($request->filled('district_name')) {
            $query->where('district_name', $request->input('district_name'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('matched_name', 'like', "%{$search}%");
            });
        }

        $results = $query->orderByDesc('updated_at')->paginate(10)->withQueryString();

        $stats = [
            'total' => MapsDiscoveryResult::count(),
            'terdaftar' => MapsDiscoveryResult::where('status', 'terdaftar')->count(),
            'potensi_baru' => MapsDiscoveryResult::where('status', 'potensi_baru')->count(),
        ];

        $taxTypeCodes = MapsDiscoveryResult::query()
            ->whereNotNull('tax_type_code')
            ->distinct()
            ->pluck('tax_type_code');

        $taxTypeNames = TaxType::query()
            ->whereIn('simpadu_code', $taxTypeCodes)
            ->pluck('name', 'simpadu_code');

        $districtNames = MapsDiscoveryResult::query()
            ->whereNotNull('district_name')
            ->distinct()
            ->orderBy('district_name')
            ->pluck('district_name');

        return view('admin.maps-discovery.report', [
            'results' => $results,
            'stats' => $stats,
            'taxTypeCodes' => $taxTypeCodes,
            'taxTypeNames' => $taxTypeNames,
            'districtNames' => $districtNames,
            'filters' => $request->only(['status', 'tax_type_code', 'district_name', 'search']),
        ]);
    }

    public function reportDetail(string $sessionId): View
    {
        $results = MapsDiscoveryResult::where('session_id', $sessionId)
            ->orderBy('status')
            ->orderByDesc('similarity_score')
            ->get();

        $stats = [
            'total' => $results->count(),
            'terdaftar' => $results->where('status', 'terdaftar')->count(),
            'potensi_baru' => $results->where('status', 'potensi_baru')->count(),
        ];

        return view('admin.maps-discovery.report-detail', [
            'results' => $results,
            'stats' => $stats,
            'sessionId' => $sessionId,
        ]);
    }
}
