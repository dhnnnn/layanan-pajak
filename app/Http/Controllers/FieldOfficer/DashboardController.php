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

        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        if (empty($assignedDistrictCodes)) {
            return view('field-officer.dashboard', [
                'summary' => [
                    'total_wp' => 0,
                    'total_tunggakan' => 0,
                    'total_ketetapan' => 0,
                    'total_bayar' => 0,
                    'persentase' => 0,
                ],
                'districts' => collect(),
                'year' => $year,
                'availableYears' => $this->getAvailableYears(),
            ]);
        }

        $stats = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->selectRaw('
                COUNT(*) as total_wp,
                COALESCE(SUM(total_tunggakan), 0) as total_tunggakan,
                COALESCE(SUM(total_ketetapan), 0) as total_ketetapan,
                COALESCE(SUM(total_bayar), 0) as total_bayar
            ')
            ->first();

        $percentage = $stats->total_ketetapan > 0
            ? ($stats->total_bayar / $stats->total_ketetapan) * 100
            : 0;

        $districts = District::whereIn('simpadu_code', $assignedDistrictCodes)
            ->orderBy('name')
            ->get();

        return view('field-officer.dashboard', [
            'summary' => [
                'total_wp' => $stats->total_wp,
                'total_tunggakan' => $stats->total_tunggakan,
                'total_ketetapan' => $stats->total_ketetapan,
                'total_bayar' => $stats->total_bayar,
                'persentase' => $percentage,
            ],
            'districts' => $districts,
            'year' => $year,
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

        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        if (empty($assignedDistrictCodes)) {
            return view('field-officer.target-achievement', [
                'targetData' => collect(),
                'year' => $year,
                'availableYears' => $this->getAvailableYears(),
            ]);
        }

        $wpStats = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->selectRaw('SUM(total_ketetapan) as total_ketetapan, SUM(total_bayar) as total_bayar')
            ->first();

        $taxTargets = TaxTarget::where('year', $year)->with('taxType')->get();

        $targetData = $taxTargets->map(function ($target) {
            return [
                'jenis_pajak' => $target->taxType?->name ?? 'Unknown',
                'target' => $target->target_amount,
                'realisasi' => 0,
                'persentase' => 0,
            ];
        });

        $totalTarget = $taxTargets->sum('target_amount');
        $totalRealisasi = $wpStats->total_bayar ?? 0;
        $totalPersentase = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

        return view('field-officer.target-achievement', [
            'targetData' => $targetData,
            'totalTarget' => $totalTarget,
            'totalRealisasi' => $totalRealisasi,
            'totalPersentase' => $totalPersentase,
            'year' => $year,
            'availableYears' => $this->getAvailableYears(),
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
