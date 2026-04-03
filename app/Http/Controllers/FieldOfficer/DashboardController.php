<?php

namespace App\Http\Controllers\FieldOfficer;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\TaxTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));
        $complianceMonth = max(1, min(12, $request->integer('compliance_month', (int) date('n'))));

        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        $districts = District::whereIn('simpadu_code', $assignedDistrictCodes)
            ->orderBy('name')
            ->get();

        $empty = [
            'summary' => ['total_wp' => 0, 'total_tunggakan' => 0, 'total_ketetapan' => 0, 'total_bayar' => 0, 'persentase' => 0],
            'districts' => $districts,
            'topDelinquents' => collect(),
            'compliance' => ['month' => $complianceMonth, 'total' => 0, 'reported' => 0, 'percentage' => 0],
            'year' => $year,
            'complianceMonth' => $complianceMonth,
            'availableYears' => $this->getAvailableYears(),
        ];

        if (empty($assignedDistrictCodes)) {
            return view('field-officer.dashboard', $empty);
        }

        // Summary totals
        $stats = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('status', '1')->where('month', 0)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->selectRaw('COUNT(*) as total_wp, COALESCE(SUM(total_tunggakan),0) as total_tunggakan, COALESCE(SUM(total_ketetapan),0) as total_ketetapan, COALESCE(SUM(total_bayar),0) as total_bayar')
            ->first();

        $percentage = $stats->total_ketetapan > 0 ? ($stats->total_bayar / $stats->total_ketetapan) * 100 : 0;

        // Top 5 tunggakan
        $topDelinquents = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('status', '1')->where('month', 0)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->where('total_tunggakan', '>', 0)
            ->select(['npwpd', 'nm_wp', 'nm_op', 'kd_kecamatan',
                DB::raw('SUM(total_ketetapan) as target'),
                DB::raw('SUM(total_bayar) as realization'),
                DB::raw('SUM(total_tunggakan) as debt')])
            ->groupBy(['npwpd', 'nm_wp', 'nm_op', 'kd_kecamatan'])
            ->orderByDesc('debt')
            ->limit(5)
            ->get();

        // Kepatuhan bulan ini
        $totalWp = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('status', '1')->where('month', 0)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->distinct()->count(DB::raw('CONCAT(npwpd, nop)'));

        $reportedWp = DB::table('simpadu_sptpd_reports')
            ->where('year', $year)->where('month', $complianceMonth)
            ->whereIn('npwpd', function ($q) use ($assignedDistrictCodes, $year) {
                $q->select('npwpd')->from('simpadu_tax_payers')
                  ->where('year', $year)->whereIn('kd_kecamatan', $assignedDistrictCodes);
            })
            ->distinct()->count(DB::raw('CONCAT(npwpd, nop)'));

        return view('field-officer.dashboard', [
            'summary' => [
                'total_wp' => $stats->total_wp,
                'total_tunggakan' => $stats->total_tunggakan,
                'total_ketetapan' => $stats->total_ketetapan,
                'total_bayar' => $stats->total_bayar,
                'persentase' => $percentage,
            ],
            'districts' => $districts,
            'topDelinquents' => $topDelinquents,
            'compliance' => [
                'month' => $complianceMonth,
                'total' => $totalWp,
                'reported' => $reportedWp,
                'percentage' => $totalWp > 0 ? ($reportedWp / $totalWp) * 100 : 0,
            ],
            'year' => $year,
            'complianceMonth' => $complianceMonth,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    public function tunggakan(Request $request): View
    {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));
        $search = $request->query('search');
        $districtId = $request->query('district_id');

        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        $assignedDistrictIds = $user->accessibleDistricts()
            ->pluck('id')
            ->toArray();

        $query = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->where('month', 0)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->where('total_tunggakan', '>', 0);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nm_wp', 'like', "%{$search}%")
                  ->orWhere('npwpd', 'like', "%{$search}%")
                  ->orWhere('nop', 'like', "%{$search}%");
            });
        }

        if ($districtId && in_array($districtId, $assignedDistrictIds)) {
            $districtCode = District::find($districtId)?->simpadu_code;
            if ($districtCode) {
                $query->where('kd_kecamatan', $districtCode);
            }
        }

        $taxpayers = $query->orderByDesc('total_tunggakan')->paginate(20);

        $districts = District::whereIn('simpadu_code', $assignedDistrictCodes)
            ->orderBy('name')
            ->get();

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('field-officer.arrears', [
            'taxpayers' => $taxpayers,
            'districts' => $districts,
            'year' => $year,
            'month' => $month,
            'search' => $search,
            'selectedDistrictId' => $districtId,
            'months' => $months,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    public function wpPerKecamatan(Request $request): View
    {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));

        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        $districtStats = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->where('month', 0)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->selectRaw('
                kd_kecamatan,
                COUNT(*) as total_wp,
                COALESCE(SUM(total_ketetapan), 0) as total_ketetapan,
                COALESCE(SUM(total_bayar), 0) as total_bayar,
                COALESCE(SUM(total_tunggakan), 0) as total_tunggakan
            ')
            ->groupBy('kd_kecamatan')
            ->get();

        $districts = District::whereIn('simpadu_code', $assignedDistrictCodes)
            ->orderBy('name')
            ->get()
            ->map(function ($district) use ($districtStats) {
                $stat = $districtStats->firstWhere('kd_kecamatan', $district->simpadu_code);
                return [
                    'id' => $district->id,
                    'name' => $district->name,
                    'code' => $district->simpadu_code,
                    'total_wp' => $stat?->total_wp ?? 0,
                    'total_ketetapan' => $stat?->total_ketetapan ?? 0,
                    'total_bayar' => $stat?->total_bayar ?? 0,
                    'total_tunggakan' => $stat?->total_tunggakan ?? 0,
                    'persentase' => $stat && $stat->total_ketetapan > 0
                        ? ($stat->total_bayar / $stat->total_ketetapan) * 100
                        : 0,
                ];
            });

        return view('field-officer.wp-by-district', [
            'districts' => $districts,
            'year' => $year,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    public function pencapaianTarget(Request $request): View
    {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'tunggakan');
        $sortDir = $request->query('sort_dir', 'desc');
        $statusFilter = $request->query('status_filter', '1');
        $taxTypeId = $request->query('tax_type_id');
        $districtId = $request->query('district_id');

        $assignedDistricts = $user->accessibleDistricts()->orderBy('name')->get();
        $allAssignedDistrictCodes = $assignedDistricts->pluck('simpadu_code')->filter()->toArray();
        
        $selectedDistrict = $districtId ? $assignedDistricts->firstWhere('id', $districtId) : null;
        // firstWhere returns false if not found — normalize to null
        if ($selectedDistrict === false) $selectedDistrict = null;
        $activeDistrictCodes = $selectedDistrict 
            ? [$selectedDistrict->simpadu_code] 
            : $allAssignedDistrictCodes;

        // 1. Load Tax Types for filter
        $taxTypes = \App\Models\TaxType::query()
            ->whereNull('parent_id')
            ->whereNotNull('simpadu_code')
            ->orderBy('name')
            ->get(['id', 'name', 'simpadu_code']);

        $selectedTaxType = $taxTypeId ? $taxTypes->firstWhere('id', $taxTypeId) : null;

        if (empty($allAssignedDistrictCodes)) {
            return view('field-officer.target-achievement', [
                'summary' => ['total_ketetapan' => 0, 'total_bayar' => 0, 'total_tunggakan' => 0, 'persentase' => 0],
                'wpData' => collect(),
                'year' => $year, 'sortBy' => $sortBy, 'sortDir' => $sortDir,
                'statusFilter' => $statusFilter, 'availableYears' => $this->getAvailableYears(),
                'taxTypes' => $taxTypes, 'taxTypeId' => $taxTypeId,
                'assignedDistricts' => collect(),
                'districtId' => null,
            ]);
        }

        // Summary - apply tax type and district filter
        $summaryQuery = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('status', '1')->where('month', 0)
            ->whereIn('kd_kecamatan', $activeDistrictCodes);

        if ($selectedTaxType) {
            $summaryQuery->where('ayat', $selectedTaxType->simpadu_code);
        }

        $stats = $summaryQuery
            ->selectRaw('COALESCE(SUM(total_ketetapan),0) as k, COALESCE(SUM(total_bayar),0) as b, COALESCE(SUM(total_tunggakan),0) as t')
            ->first();

        $pct = $stats->k > 0 ? ($stats->b / $stats->k) * 100 : 0;

        // WP list — filter by selected district
        $plainSortCols = ['name' => 'nm_wp', 'sptpd' => 'total_ketetapan', 'bayar' => 'total_bayar', 'tunggakan' => 'total_tunggakan'];
        $rawSortCols = ['selisih' => '(SUM(stp.total_bayar) - SUM(stp.total_ketetapan))'];

        $query = DB::table('simpadu_tax_payers as stp')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
            ->where('stp.year', $year)
            ->where('stp.month', 0)
            ->whereIn('stp.kd_kecamatan', $activeDistrictCodes)
            ->when($statusFilter !== 'all', fn($q) => $q->where('stp.status', $statusFilter))
            ->when($selectedTaxType, fn($q) => $q->where('stp.ayat', $selectedTaxType->simpadu_code))
            ->when($search, fn($q) => $q->where(fn($sq) =>
                $sq->where('stp.nm_wp', 'like', "%{$search}%")
                   ->orWhere('stp.npwpd', 'like', "%{$search}%")
                   ->orWhere('stp.nop', 'like', "%{$search}%")
            ))
            ->groupBy('stp.npwpd', 'stp.nop', 'stp.nm_wp', 'stp.nm_op', 'stp.ayat', 'stp.status', 'tax_types.name')
            ->selectRaw('stp.npwpd, stp.nop, stp.nm_wp, stp.nm_op, stp.ayat, stp.status, tax_types.name as tax_type_name,
                SUM(stp.total_ketetapan) as total_ketetapan,
                LEAST(SUM(stp.total_bayar), SUM(stp.total_ketetapan)) as total_bayar,
                GREATEST(SUM(stp.total_ketetapan) - SUM(stp.total_bayar), 0) as total_tunggakan');

        if (isset($rawSortCols[$sortBy])) {
            $query->orderByRaw($rawSortCols[$sortBy] . ' ' . ($sortDir === 'asc' ? 'asc' : 'desc'));
        } else {
            $query->orderBy($plainSortCols[$sortBy] ?? 'total_tunggakan', $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $wpData = $query->paginate(15)->through(fn($r) => [
            'npwpd' => $r->npwpd, 'nop' => $r->nop, 'nm_wp' => $r->nm_wp,
            'tax_type_name' => $r->tax_type_name,
            'status_code' => (string) $r->status,
            'total_sptpd' => (float) $r->total_ketetapan,
            'total_bayar' => (float) $r->total_bayar,
            'selisih' => (float) ($r->total_bayar - $r->total_ketetapan),
            'tunggakan' => (float) max($r->total_tunggakan, 0),
        ]);

        return view('field-officer.target-achievement', [
            'summary' => [
                'total_ketetapan' => $stats->k,
                'total_bayar' => $stats->b,
                'total_tunggakan' => $stats->t,
                'persentase' => $pct,
            ],
            'wpData' => $wpData,
            'year' => $year,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'statusFilter' => $statusFilter,
            'availableYears' => $this->getAvailableYears(),
            'taxTypes' => $taxTypes,
            'taxTypeId' => $taxTypeId,
            'assignedDistricts' => $assignedDistricts,
            'districtId' => $districtId,
        ]);
    }

    public function realisasiBulanan(Request $request): View
    {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));

        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        if (empty($assignedDistrictCodes)) {
            return view('field-officer.monthly-realization', [
                'monthlyData' => collect(),
                'year' => $year,
                'availableYears' => $this->getAvailableYears(),
            ]);
        }

        $monthlyStats = DB::table('simpadu_sptpd_reports')
            ->where('year', $year)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->selectRaw('month, SUM(jml_sptpd) as total_sptpd')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        $monthlyData = collect(range(1, 12))->map(function ($month) use ($monthlyStats, $months) {
            $stat = $monthlyStats->firstWhere('month', $month);
            return [
                'bulan' => $months[$month],
                'nomor' => $month,
                'total_sptpd' => $stat?->total_sptpd ?? 0,
            ];
        });

        return view('field-officer.monthly-realization', [
            'monthlyData' => $monthlyData,
            'year' => $year,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    public function statusPembayaran(Request $request): View
    {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));
        $status = $request->query('status', 'semua');
        $search = $request->query('search');
        $districtId = $request->query('district_id');

        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        $assignedDistrictIds = $user->accessibleDistricts()
            ->pluck('id')
            ->toArray();

        $query = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->whereIn('kd_kecamatan', $assignedDistrictCodes);

        if ($status === 'lunas') {
            $query->where('total_tunggakan', '<=', 0);
        } elseif ($status === 'belum_lunas') {
            $query->where('total_tunggakan', '>', 0);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nm_wp', 'like', "%{$search}%")
                  ->orWhere('npwpd', 'like', "%{$search}%")
                  ->orWhere('nop', 'like', "%{$search}%");
            });
        }

        if ($districtId && in_array($districtId, $assignedDistrictIds)) {
            $districtCode = District::find($districtId)?->simpadu_code;
            if ($districtCode) {
                $query->where('kd_kecamatan', $districtCode);
            }
        }

        $taxpayers = $query->orderBy('nm_wp')->paginate(20);

        $districts = District::whereIn('simpadu_code', $assignedDistrictCodes)
            ->orderBy('name')
            ->get();

        // status-pembayaran view not yet renamed — keep as-is until confirmed
        return view('field-officer.arrears', [
            'taxpayers' => $taxpayers,
            'districts' => $districts,
            'year' => $year,
            'status' => $status,
            'search' => $search,
            'selectedDistrictId' => $districtId,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    public function pencarian(Request $request): View
    {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));
        $search = $request->query('search', '');
        $districtId = $request->query('district_id');

        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        $assignedDistrictIds = $user->accessibleDistricts()
            ->pluck('id')
            ->toArray();

        $query = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->whereIn('kd_kecamatan', $assignedDistrictCodes);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nm_wp', 'like', "%{$search}%")
                  ->orWhere('npwpd', 'like', "%{$search}%")
                  ->orWhere('nop', 'like', "%{$search}%")
                  ->orWhere('almt_op', 'like', "%{$search}%");
            });
        }

        if ($districtId && in_array($districtId, $assignedDistrictIds)) {
            $districtCode = District::find($districtId)?->simpadu_code;
            if ($districtCode) {
                $query->where('kd_kecamatan', $districtCode);
            }
        }

        $taxpayers = $query->orderBy('nm_wp')->paginate(20);

        $districts = District::whereIn('simpadu_code', $assignedDistrictCodes)
            ->orderBy('name')
            ->get();

        return view('field-officer.search', [
            'taxpayers' => $taxpayers,
            'districts' => $districts,
            'year' => $year,
            'search' => $search,
            'selectedDistrictId' => $districtId,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    public function wpTunggakan(Request $request): \Illuminate\Http\JsonResponse
    {
        $year  = $request->integer('year', (int) date('Y'));
        $npwpd = $request->query('npwpd');
        $nop   = $request->query('nop');

        $months = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('npwpd', $npwpd)->where('nop', $nop)
            ->where('month', '>', 0)
            ->orderBy('month')
            ->get(['month', 'total_ketetapan', 'total_bayar', 'total_tunggakan']);

        $bulanIndo = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        return response()->json(
            $months->filter(fn($r) => $r->total_ketetapan > 0)
                   ->map(fn($r) => [
                       'bulan' => $bulanIndo[(int)$r->month] ?? $r->month,
                       'total_ketetapan' => (float) $r->total_ketetapan,
                       'total_bayar' => (float) $r->total_bayar,
                       'total_tunggakan' => (float) max($r->total_tunggakan, 0),
                   ])->values()
        );
    }

    public function detailWp(Request $request, string $npwpd): View
    {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));

        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        $wp = DB::table('simpadu_tax_payers')
            ->where('npwpd', $npwpd)
            ->where('year', $year)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->first();

        if (!$wp) {
            abort(404, 'Data WP tidak ditemukan');
        }

        $reports = DB::table('simpadu_sptpd_reports')
            ->where('npwpd', $npwpd)
            ->where('year', $year)
            ->orderBy('month')
            ->get();

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('field-officer.taxpayer-detail', [
            'wp' => $wp,
            'reports' => $reports,
            'year' => $year,
            'months' => $months,
        ]);
    }

    private function getAvailableYears()
    {
        return TaxTarget::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->merge([date('Y')])
            ->unique()
            ->sortDesc()
            ->values();
    }
}
