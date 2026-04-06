<?php

namespace App\Actions\FieldOfficer;

use App\Models\District;
use App\Models\TaxTarget;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetFieldOfficerDashboardDataAction
{
    /**
     * Get dashboard data for field officer.
     *
     * @return array<string, mixed>
     */
    public function execute(User $user, int $year, int $complianceMonth): array
    {
        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        $districts = District::whereIn('simpadu_code', $assignedDistrictCodes)
            ->orderBy('name')
            ->get();

        if (empty($assignedDistrictCodes)) {
            return [
                'summary' => ['total_wp' => 0, 'total_tunggakan' => 0, 'total_ketetapan' => 0, 'total_bayar' => 0, 'persentase' => 0],
                'districts' => $districts,
                'topDelinquents' => collect(),
                'compliance' => ['month' => $complianceMonth, 'total' => 0, 'reported' => 0, 'percentage' => 0],
                'year' => $year,
                'complianceMonth' => $complianceMonth,
                'availableYears' => $this->getAvailableYears(),
            ];
        }

        // Summary totals
        $stats = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->where('month', 0)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->selectRaw('COUNT(*) as total_wp, COALESCE(SUM(total_tunggakan),0) as total_tunggakan, COALESCE(SUM(total_ketetapan),0) as total_ketetapan, COALESCE(SUM(total_bayar),0) as total_bayar')
            ->first();

        $percentage = $stats->total_ketetapan > 0 ? ($stats->total_bayar / $stats->total_ketetapan) * 100 : 0;

        // Top 5 tunggakan
        $topDelinquents = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('status', '1')
            ->where('month', 0)
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
            ->where('year', $year)
            ->where('status', '1')
            ->where('month', 0)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->distinct()
            ->count(DB::raw('CONCAT(npwpd, nop)'));

        $reportedWp = DB::table('simpadu_sptpd_reports')
            ->where('year', $year)
            ->where('month', $complianceMonth)
            ->whereIn('npwpd', function ($q) use ($assignedDistrictCodes, $year) {
                $q->select('npwpd')->from('simpadu_tax_payers')
                    ->where('year', $year)
                    ->whereIn('kd_kecamatan', $assignedDistrictCodes);
            })
            ->distinct()
            ->count(DB::raw('CONCAT(npwpd, nop)'));

        return [
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
        ];
    }

    /**
     * Get available years for filtering.
     *
     * @return Collection<int, int>
     */
    public function getAvailableYears(): Collection
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
