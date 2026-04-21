<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardRequest;
use App\Models\SimpaduMonthlyRealization;
use App\Models\SimpaduTarget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(
        DashboardRequest $request,
        GenerateTaxDashboardAction $generateDashboard,
    ): View {
        $user = auth()->user();
        $isKepalaUpt = $user->isKepalaUpt();

        // Gabungkan tahun dari simpadu_targets + simpadu_monthly_realizations
        // agar dropdown mencakup semua tahun yang ada datanya
        $yearsFromTargets = SimpaduTarget::query()
            ->distinct()
            ->pluck('year');

        $yearsFromRealizations = SimpaduMonthlyRealization::query()
            ->distinct()
            ->pluck('year');

        $availableYears = $yearsFromTargets
            ->merge($yearsFromRealizations)
            ->unique()
            ->sortDesc()
            ->values();

        // Default ke tahun ini, bukan tahun pertama di list
        $selectedYear = (int) $request->query('year', date('Y'));

        $uptId = null;
        $assignedDistricts = collect();
        $selectedDistrictId = $request->query('district_id');
        $isAllDistricts = $selectedDistrictId === 'all';
        $priorityDistrictId = $request->query('priority_district_id'); // filter khusus prioritas penagihan

        if ($isKepalaUpt) {
            $uptId = $user->upt()?->id;
            $assignedDistricts = $user->accessibleDistricts()->orderBy('name')->get();

            // Handle district selection for kepala_upt
            if ($isAllDistricts) {
                $selectedDistrictId = null;
            } elseif ($selectedDistrictId === null || ! $assignedDistricts->contains('id', $selectedDistrictId)) {
                $selectedDistrictId = null; // Default to all if null or invalid for kepala_upt
                $isAllDistricts = true;
            }
        }

        $result = $generateDashboard($selectedYear, search: $request->query('search'));

        $view = $isKepalaUpt ? 'admin.upt-head.dashboard' : 'admin.dashboard';

        $compliance = null;
        $topDelinquents = collect();
        $officerStats = collect();

        if ($isKepalaUpt && $user->upt()) {
            $currentMonth = (int) $request->query('compliance_month', date('n'));
            // Clamp to valid month range
            $currentMonth = max(1, min(12, $currentMonth));
            $districtCodes = $user->upt()->districts->pluck('simpadu_code')->toArray();

            // Find current simpadu_code if district filter is set
            $filterCodes = $districtCodes;
            if ($selectedDistrictId && ! $isAllDistricts) {
                $targetDist = $assignedDistricts->firstWhere('id', $selectedDistrictId);
                if ($targetDist && $targetDist->simpadu_code) {
                    $filterCodes = [$targetDist->simpadu_code];
                }
            }

            // Deteksi apakah ada data month=0 (summary tahunan) untuk tahun ini
            // Jika tidak ada (data historis), aggregate dari semua bulan (1-12)
            $hasMonthZero = DB::table('simpadu_tax_payers')
                ->where('year', $selectedYear)
                ->where('status', '1')
                ->where('month', 0)
                ->exists();

            // Calculate global totals from simpadu_tax_payers based on filter
            // Use status='1' (active only) to match ShowUptMonitoringAction
            $simpaduTotals = DB::table('simpadu_tax_payers')
                ->where('year', $selectedYear)
                ->where('status', '1')
                ->when($hasMonthZero, fn ($q) => $q->where('month', 0), fn ($q) => $q->where('month', '>', 0))
                ->whereIn('kd_kecamatan', $filterCodes)
                ->select([
                    DB::raw('SUM(total_ketetapan) as target'),
                    DB::raw('SUM(total_bayar) as realization'),
                ])
                ->first();

            $result['totals']['target'] = $simpaduTotals->target ?? 0;
            $result['totals']['realization'] = $simpaduTotals->realization ?? 0;
            $result['totals']['percentage'] = ($result['totals']['target'] > 0)
                ? ($result['totals']['realization'] / $result['totals']['target']) * 100
                : 0;

            // 1. Kepatuhan Pelaporan (Bulan Berjalan)
            $totalWp = DB::table('simpadu_tax_payers')
                ->where('year', $selectedYear)
                ->whereIn('kd_kecamatan', $districtCodes)
                ->where('status', '1')
                ->when($hasMonthZero, fn ($q) => $q->where('month', 0), fn ($q) => $q->where('month', '>', 0)->distinct(['npwpd', 'nop']))
                ->distinct(['npwpd', 'nop'])
                ->count(['npwpd', 'nop', 'year']);

            $reportedWp = DB::table('simpadu_sptpd_reports')
                ->where('year', $selectedYear)
                ->where('month', $currentMonth)
                ->whereIn('npwpd', function ($q) use ($districtCodes, $selectedYear) {
                    $q->select('npwpd')
                        ->from('simpadu_tax_payers')
                        ->where('year', $selectedYear)
                        ->whereIn('kd_kecamatan', $districtCodes);
                })
                ->distinct(['npwpd', 'nop'])
                ->count(['npwpd', 'nop', 'year', 'month']);

            $compliance = [
                'month' => $currentMonth,
                'total' => $totalWp,
                'reported' => $reportedWp,
                'percentage' => $totalWp > 0 ? ($reportedWp / $totalWp) * 100 : 0,
            ];

            // 2. Prioritas Penagihan (Top 5 Tunggakan) — filter per wilayah terpisah
            $priorityCodes = $districtCodes; // default semua wilayah UPT
            $selectedPriorityDistrict = null;
            if ($priorityDistrictId && $priorityDistrictId !== 'all') {
                $targetDist = $assignedDistricts->firstWhere('id', $priorityDistrictId);
                if ($targetDist && $targetDist->simpadu_code) {
                    $priorityCodes = [$targetDist->simpadu_code];
                    $selectedPriorityDistrict = $targetDist;
                }
            }

            $topDelinquents = DB::table('simpadu_tax_payers')
                ->select([
                    'npwpd', 'nm_wp', 'nm_op', 'kd_kecamatan',
                    DB::raw('SUM(total_ketetapan) as target'),
                    DB::raw('SUM(total_bayar) as realization'),
                    DB::raw('SUM(total_ketetapan - total_bayar) as debt'),
                ])
                ->where('year', $selectedYear)
                ->where('status', '1')
                ->when($hasMonthZero, fn ($q) => $q->where('month', 0), fn ($q) => $q->where('month', '>', 0))
                ->whereIn('kd_kecamatan', $priorityCodes)
                ->where('total_tunggakan', '>', 0)
                ->groupBy(['npwpd', 'nm_wp', 'nm_op', 'kd_kecamatan'])
                ->orderByDesc('debt')
                ->limit(10)
                ->get();

            // 3. Kinerja Petugas (Top 5 berdasarkan attainment % wilayahnya)
            $districtStats = DB::table('simpadu_tax_payers')
                ->where('year', $selectedYear)
                ->where('status', '1')
                ->when($hasMonthZero, fn ($q) => $q->where('month', 0), fn ($q) => $q->where('month', '>', 0))
                ->whereIn('kd_kecamatan', $districtCodes)
                ->select([
                    'kd_kecamatan',
                    DB::raw('SUM(total_ketetapan) as total_target'),
                    DB::raw('SUM(total_bayar) as total_realization'),
                ])
                ->groupBy('kd_kecamatan')
                ->get()
                ->keyBy('kd_kecamatan');

            $employeeDashboardData = User::query()->role('pegawai')
                ->whereHas('upts', fn ($q) => $q->where('upts.id', $user->upt()?->id))
                ->with('districts')
                ->get()
                ->map(function ($employee) use ($districtStats) {
                    $empDistricts = $employee->districts;
                    $totalTarget = 0;
                    $totalRealization = 0;

                    foreach ($empDistricts as $d) {
                        $stats = $districtStats->get($d->simpadu_code);
                        if ($stats) {
                            $totalTarget += $stats->total_target;
                            $totalRealization += $stats->total_realization;
                        }
                    }

                    return [
                        'employee' => $employee,
                        'sptpd_total' => $totalTarget,
                        'pay_total' => $totalRealization,
                        'remaining' => max(0, $totalTarget - $totalRealization),
                        'attainment_pct' => $totalTarget > 0 ? ($totalRealization / $totalTarget) * 100 : 0,
                        'districts_count' => $empDistricts->count(),
                    ];
                })
                ->sortByDesc('attainment_pct')
                ->take(5);
        }

        $availableAyat = SimpaduTarget::query()
            ->select('no_ayat', 'keterangan', 'year')
            ->orderByDesc('year')
            ->get()
            ->unique('no_ayat')
            ->sortBy('no_ayat')
            ->mapWithKeys(fn ($t) => [$t->no_ayat => $t->keterangan]);

        return view($view, [
            'dashboard' => $result['data'],
            'totals' => $result['totals'],
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'assignedDistricts' => $assignedDistricts,
            'selectedDistrictId' => $selectedDistrictId,
            'isAllDistricts' => $isAllDistricts,
            'compliance' => $compliance,
            'topDelinquents' => $topDelinquents,
            'employeeDashboardData' => $employeeDashboardData ?? collect(),
            'priorityDistrictId' => $priorityDistrictId,
            'selectedPriorityDistrict' => $selectedPriorityDistrict ?? null,
            'upt' => $isKepalaUpt ? $user->upt() : null,
            'availableAyat' => $availableAyat,
        ]);
    }
}
