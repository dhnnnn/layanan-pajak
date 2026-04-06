<?php

namespace App\Actions\FieldOfficer;

use App\Models\District;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetFieldOfficerDistrictStatsAction
{
    public function execute(User $user, int $year): array
    {
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

        return [
            'districts' => $districts,
            'year' => $year,
            'availableYears' => (new GetAvailableYearsAction)->execute(),
        ];
    }
}
