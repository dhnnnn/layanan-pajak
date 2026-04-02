<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardRequest;
use App\Models\TaxTarget;
use Illuminate\Support\Facades\DB;
use App\Models\OfficerTask;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(
        DashboardRequest $request,
        GenerateTaxDashboardAction $generateDashboard,
    ): View {
        $user = auth()->user();
        $isKepalaUpt = $user->isKepalaUpt();

        $availableYears = TaxTarget::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $selectedYear = (int) $request->query(
            'year',
            $availableYears->first() ?? date('Y'),
        );

        $uptId = null;
        $assignedDistricts = collect();
        $selectedDistrictId = $request->query('district_id');
        $isAllDistricts = $selectedDistrictId === 'all';

        if ($isKepalaUpt) {
            $uptId = $user->upt_id;
            $assignedDistricts = $user->accessibleDistricts()->orderBy('name')->get();

            // Handle district selection for kepala_upt
            if ($isAllDistricts) {
                $selectedDistrictId = null;
            } elseif ($selectedDistrictId === null || ! $assignedDistricts->contains('id', $selectedDistrictId)) {
                $selectedDistrictId = null; // Default to all if null or invalid for kepala_upt
                $isAllDistricts = true;
            }
        }

        $result = $generateDashboard($selectedYear, districtId: $selectedDistrictId, uptId: $uptId);

        $view = $isKepalaUpt ? 'admin.dashboard_kepala_upt' : 'admin.dashboard';

        $compliance = null;
        $topDelinquents = collect();
        $officerStats = collect();

        if ($isKepalaUpt && $user->upt) {
            $currentMonth = date('n');
            $districtCodes = $user->upt->districts->pluck('simpadu_code')->toArray();
            
            // 1. Kepatuhan Pelaporan (Bulan Berjalan)
            $totalWp = DB::table('simpadu_tax_payers')
                ->where('year', $selectedYear)
                ->whereIn('kd_kecamatan', $districtCodes)
                ->where('status', '1')
                ->distinct(['npwpd', 'nop'])
                ->count(['npwpd', 'nop', 'year']);

            $reportedWp = DB::table('simpadu_sptpd_reports')
                ->where('year', $selectedYear)
                ->where('month', $currentMonth)
                ->whereIn('npwpd', function($q) use ($districtCodes, $selectedYear) {
                    $q->select('npwpd')
                      ->from('simpadu_tax_payers')
                      ->where('year', $selectedYear)
                      ->whereIn('kd_kecamatan', $districtCodes);
                })
                ->distinct(['npwpd', 'nop'])
                ->count(['npwpd', 'nop', 'year', 'month']);

            $compliance = [
                'total' => $totalWp,
                'reported' => $reportedWp,
                'percentage' => $totalWp > 0 ? ($reportedWp / $totalWp) * 100 : 0,
            ];

            // 2. Prioritas Penagihan (Top 5 Tunggakan)
            $topDelinquents = DB::table('simpadu_tax_payers')
                ->select([
                    'npwpd', 'nm_wp', 'nm_op', 'kd_kecamatan',
                    DB::raw('SUM(total_ketetapan) as target'),
                    DB::raw('SUM(total_bayar) as realization'),
                    DB::raw('SUM(total_ketetapan - total_bayar) as debt')
                ])
                ->where('year', $selectedYear)
                ->whereIn('kd_kecamatan', $districtCodes)
                ->groupBy(['npwpd', 'nm_wp', 'nm_op', 'kd_kecamatan'])
                ->orderByDesc('debt')
                ->limit(5)
                ->get();

            // 3. Kinerja Petugas (Hanya Petugas di UPT ini)
            $officerStats = User::role('pegawai')
                ->where('upt_id', $user->upt_id)
                ->whereHas('tasks')
                ->withCount(['tasks as total_tasks'])
                ->withCount(['tasks as completed_tasks' => fn($q) => $q->where('status', 'completed')])
                ->get()
                ->map(function($officer) {
                    $officer->performance = $officer->total_tasks > 0 ? ($officer->completed_tasks / $officer->total_tasks) * 100 : 0;
                    return $officer;
                })
                ->sortByDesc('performance');
        }

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
            'officerStats' => $officerStats,
        ]);
    }
}
