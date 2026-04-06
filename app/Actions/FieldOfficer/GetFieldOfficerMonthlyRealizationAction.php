<?php

namespace App\Actions\FieldOfficer;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetFieldOfficerMonthlyRealizationAction
{
    public function execute(User $user, int $year): array
    {
        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        if (empty($assignedDistrictCodes)) {
            return [
                'monthlyData' => collect(),
                'year' => $year,
                'availableYears' => (new GetAvailableYearsAction)->execute(),
            ];
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

        return [
            'monthlyData' => $monthlyData,
            'year' => $year,
            'availableYears' => (new GetAvailableYearsAction)->execute(),
        ];
    }
}
