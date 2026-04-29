<?php

namespace App\Http\Controllers\Admin;

use App\Actions\MapsDiscovery\CalculatePotentialTaxAction;
use App\Actions\MapsDiscovery\CrawlMapsAction;
use App\Actions\MapsDiscovery\GetDiscoveryReportAction;
use App\Actions\MapsDiscovery\GetVillagesByDistrictAction;
use App\Actions\MapsDiscovery\ProcessCrawlAction;
use App\Actions\MapsDiscovery\ScrapeMapStatisticsAction;
use App\Actions\MapsDiscovery\SyncDiscoveryResultsAction;
use App\Exceptions\ScraperErrorException;
use App\Exceptions\ScraperUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CrawlMapsDiscoveryRequest;
use App\Models\District;
use App\Models\MapsDiscoveryResult;
use App\Models\MonitoringReport;
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
            return response()->json(['error' => $e->getMessage()], 5003);
        } catch (ScraperErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 5000);
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

    /**
     * Halaman detail analisis potensi pajak.
     */
    public function analisisDetail(string $id, ScrapeMapStatisticsAction $action): View
    {
        $result = MapsDiscoveryResult::with(['monitoringReports.officer', 'potentialCalculations'])
            ->findOrFail($id);

        $statisticsData = null;
        $statisticsError = null;
        $isLoading = false;

        // Cek apakah sudah ada di maps_statistics
        $hasStatistics = $result->mapsStatistics()->exists();

        if ($hasStatistics) {
            // Sudah ada, langsung load
            $statsResult = $action($result);
            if ($statsResult['success']) {
                $statisticsData = $statsResult['statistics'];
            } else {
                $statisticsError = $statsResult['message'] ?? 'Gagal memuat statistik';
            }
        } elseif (! empty($result->popular_times)) {
            // Ada popular_times di DB, konversi ke maps_statistics
            try {
                $statsResult = $action($result);
                if ($statsResult['success']) {
                    $statisticsData = $statsResult['statistics'];
                } else {
                    $statisticsError = $statsResult['message'] ?? 'Gagal memuat statistik';
                }
            } catch (\Exception $e) {
                $statisticsError = 'Terjadi kesalahan: '.$e->getMessage();
            }
        } else {
            // Belum ada data - perlu scrape dari API (loading state)
            $isLoading = true;
        }

        return view('admin.maps-discovery.analisis-detail', [
            'result' => $result,
            'statisticsData' => $statisticsData,
            'statisticsError' => $statisticsError,
            'isLoading' => $isLoading,
            'latestCalculation' => $result->potentialCalculations()->latest()->first(),
        ]);
    }

    /**
     * API: Scrape statistik Maps untuk satu tempat.
     */
    public function scrapeStatistics(string $id, ScrapeMapStatisticsAction $action): JsonResponse
    {
        $result = MapsDiscoveryResult::findOrFail($id);

        try {
            $data = $action($result);

            return response()->json($data);
        } catch (ScraperUnavailableException $e) {
            return response()->json(['success' => false, 'message' => 'Scraper tidak dapat dijangkau.'], 503);
        } catch (ScraperErrorException $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data dari scraper.'], 500);
        }
    }

    /**
     * API: Hitung potensi pajak berdasarkan data monitoring dan statistik Maps.
     */
    public function calculatePotential(Request $request, string $id, CalculatePotentialTaxAction $action): JsonResponse
    {
        $validated = $request->validate([
            'checker_result' => ['required', 'integer', 'min:1'],
            'monitoring_hour' => ['required', 'string', 'regex:/^\d{1,2}-\d{1,2}$/'],
            'day_of_week' => ['required', 'string', 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu'],
            'avg_menu_price' => ['required', 'numeric', 'min:1000'],
            'avg_duration_hours' => ['required', 'numeric', 'min:0.5', 'max:12'],
        ]);

        $result = MapsDiscoveryResult::findOrFail($id);

        // Buat monitoring report sementara (tanpa simpan ke DB) untuk kalkulasi
        $monitoring = new MonitoringReport([
            'maps_discovery_result_id' => $result->id,
            'officer_id' => auth()->id(),
            'monitoring_date' => now()->toDateString(),
            'monitoring_hour' => $validated['monitoring_hour'],
            'day_of_week' => $validated['day_of_week'],
            'visitor_count' => $validated['checker_result'],
        ]);

        $calcResult = $action(
            $result,
            $monitoring,
            (float) $validated['avg_menu_price'],
            (float) $validated['avg_duration_hours']
        );

        if (! $calcResult['success']) {
            return response()->json(['success' => false, 'message' => $calcResult['message']], 422);
        }

        return response()->json([
            'success' => true,
            'calculation' => $calcResult['calculation'],
        ]);
    }
}
