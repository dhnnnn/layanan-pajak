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
            return response()->json(['error' => $e->getMessage()], 503);
        } catch (ScraperErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
