<?php

namespace App\Http\Controllers\Admin;

use App\Actions\MapsDiscovery\CrawlMapsAction;
use App\Actions\MapsDiscovery\GetDiscoveryReportAction;
use App\Actions\MapsDiscovery\GetVillagesByDistrictAction;
use App\Actions\MapsDiscovery\ProcessCrawlAction;
use App\Actions\MapsDiscovery\SyncDiscoveryResultsAction;
use App\Exceptions\ScraperErrorException;
use App\Exceptions\ScraperUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CrawlMapsDiscoveryRequest;
use App\Models\District;
use App\Models\MapsDiscoveryResult;
use App\Models\TaxType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /**
     * API: ambil daftar desa/kelurahan berdasarkan district_id.
     */
    public function villages(Request $request, GetVillagesByDistrictAction $action): JsonResponse
    {
        $districtId = $request->query('district_id');
        if (! $districtId) {
            return response()->json([]);
        }

        return response()->json($action($districtId));
    }

    /**
     * Crawl data dari Google Maps — fokus ambil sebanyak-banyaknya.
     * Simpan langsung ke DB tanpa matching. Matching dilakukan terpisah via sync.
     */
    public function crawl(CrawlMapsDiscoveryRequest $request, ProcessCrawlAction $action): JsonResponse
    {
        ignore_user_abort(false);

        try {
            $result = $action($request->validated());

            if (isset($result['message'])) {
                return response()->json([
                    'results' => [],
                    'stats' => $result['stats'],
                    'message' => $result['message'],
                ]);
            }

            return response()->json([
                'results' => $result['results'],
                'stats' => $result['stats'],
            ]);
        } catch (ScraperUnavailableException $e) {
            return response()->json(['error' => $e->getMessage()], 1003);
        } catch (ScraperErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 1000);
        }
    }

    /**
     * Sinkronkan data crawling dengan database WP.
     */
    public function sync(Request $request, SyncDiscoveryResultsAction $action): JsonResponse
    {
        return response()->json($action());
    }

    public function report(Request $request, GetDiscoveryReportAction $action): View
    {
        return view('admin.maps-discovery.report', $action($request, auth()->user()));
    }

    /**
     * API: ambil data map (lat/lng + status) untuk halaman report.
     */
    public function reportMapData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = MapsDiscoveryResult::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Kepala UPT: hanya kecamatan UPT-nya
        if ($user->isKepalaUpt()) {
            $allowedDistricts = $user->upt()?->districts->pluck('name')->toArray() ?? [];
            if (! empty($allowedDistricts)) {
                $query->whereIn('district_name', $allowedDistricts);
            }
        }

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
                    ->orWhere('subtitle', 'like', "%{$search}%");
            });
        }

        $points = $query->get(['title', 'subtitle', 'category', 'status', 'latitude', 'longitude', 'url', 'matched_npwpd', 'matched_name', 'rating', 'reviews'])
            ->filter(function ($r): bool {
                // Filter: hanya koordinat di wilayah Kabupaten Pasuruan
                return $r->latitude !== null
                    && $r->longitude !== null
                    && $r->latitude >= -7.95
                    && $r->latitude <= -7.35
                    && $r->longitude >= 112.55
                    && $r->longitude <= 113.05;
            })
            ->values();

        return response()->json($points);
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
