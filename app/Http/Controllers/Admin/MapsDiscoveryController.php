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
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

        $districts = District::query()
            ->orderBy('name')
            ->get();

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
        // Stop PHP execution when client disconnects (cancel button)
        ignore_user_abort(false);

        $validated = $request->validated();

        $keywords = [];
        $ayat = null;

        if (! empty($validated['tax_type_code'])) {
            $taxType = TaxType::query()
                ->where('simpadu_code', $validated['tax_type_code'])
                ->first();

            if ($taxType) {
                $ayat = $taxType->simpadu_code;
                $parentCode = explode('-', $ayat)[0];
                $keywords = CrawlMapsAction::KEYWORD_MAPPING[$ayat]
                    ?? CrawlMapsAction::KEYWORD_MAPPING[$parentCode]
                    ?? [];
            }
        }

        if (! empty($validated['keyword'])) {
            $userKeyword = trim($validated['keyword']);
            if (! in_array(strtolower($userKeyword), array_map('strtolower', $keywords))) {
                $keywords[] = $userKeyword;
            }
        }

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
            $maxResults = (int) ($validated['max_results'] ?? 20);
            $crawlResults = $crawlAction($keywords, $area, $maxResults);

            if ($districtShort !== null) {
                $needle = strtolower($districtShort);
                $crawlResults = $crawlResults->filter(
                    fn (array $item): bool => str_contains(strtolower($item['subtitle'] ?? ''), $needle)
                )->values();
            }

            if ($crawlResults->isEmpty()) {
                return response()->json([
                    'results' => [],
                    'stats' => ['total' => 0, 'terdaftar' => 0, 'potensi_baru' => 0],
                    'message' => 'Tidak ditemukan lokasi bisnis untuk pencarian ini. Coba ubah keyword atau wilayah.',
                ]);
            }

            $matchedResults = $matchAction($crawlResults, $ayat, $kdKecamatan);

            // Simpan hasil ke database
            $sessionId = Str::uuid()->toString();
            $userId = auth()->id();
            $keywordStr = implode(', ', $keywords);

            $matchedResults->each(function (array $item) use ($sessionId, $userId, $ayat, $districtName, $keywordStr): void {
                MapsDiscoveryResult::create([
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'title' => $item['title'],
                    'subtitle' => $item['subtitle'] ?? null,
                    'category' => $item['category'] ?? null,
                    'place_id' => $item['place_id'] ?? null,
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
                ]);
            });

            $terdaftar = $matchedResults->where('status', 'terdaftar')->count();
            $potensiBaru = $matchedResults->where('status', 'potensi_baru')->count();

            return response()->json([
                'results' => $matchedResults->values(),
                'stats' => [
                    'total' => $matchedResults->count(),
                    'terdaftar' => $terdaftar,
                    'potensi_baru' => $potensiBaru,
                ],
                'session_id' => $sessionId,
            ]);
        } catch (ScraperUnavailableException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        } catch (ScraperErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function report(): View
    {
        $sessions = MapsDiscoveryResult::query()
            ->selectRaw('session_id, user_id, tax_type_code, district_name, keyword, MIN(created_at) as crawled_at, COUNT(*) as total, SUM(CASE WHEN status = "terdaftar" THEN 1 ELSE 0 END) as terdaftar, SUM(CASE WHEN status = "potensi_baru" THEN 1 ELSE 0 END) as potensi_baru')
            ->groupBy('session_id', 'user_id', 'tax_type_code', 'district_name', 'keyword')
            ->orderByDesc('crawled_at')
            ->paginate(20);

        // Eager load user names
        $userIds = $sessions->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->pluck('name', 'id');

        return view('admin.maps-discovery.report', [
            'sessions' => $sessions,
            'users' => $users,
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
