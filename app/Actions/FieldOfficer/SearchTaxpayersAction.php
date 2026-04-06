<?php

namespace App\Actions\FieldOfficer;

use App\Models\District;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SearchTaxpayersAction
{
    public function execute(User $user, array $params): array
    {
        $year = $params['year'] ?? (int) date('Y');
        $search = $params['search'] ?? '';
        $districtId = $params['district_id'] ?? null;

        $assignedDistricts = $user->accessibleDistricts()->get();
        $assignedDistrictCodes = $assignedDistricts->pluck('simpadu_code')->filter()->toArray();
        $assignedDistrictIds = $assignedDistricts->pluck('id')->toArray();

        $query = DB::table('simpadu_tax_payers as stp')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
            ->where('stp.year', $year)
            ->where('stp.status', '1')
            ->where('stp.month', 0)
            ->whereIn('stp.kd_kecamatan', $assignedDistrictCodes)
            ->select('stp.*', 'tax_types.name as tax_type_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('stp.nm_wp', 'like', "%{$search}%")
                    ->orWhere('stp.npwpd', 'like', "%{$search}%")
                    ->orWhere('stp.nop', 'like', "%{$search}%")
                    ->orWhere('stp.almt_op', 'like', "%{$search}%");
            });
        }

        if ($districtId && in_array($districtId, $assignedDistrictIds)) {
            $districtCode = District::find($districtId)?->simpadu_code;
            if ($districtCode) {
                $query->where('stp.kd_kecamatan', $districtCode);
            }
        }

        $taxpayers = $query->orderBy('stp.nm_wp')->paginate(20);

        return [
            'taxpayers' => $taxpayers,
            'districts' => District::whereIn('simpadu_code', $assignedDistrictCodes)->orderBy('name')->get(),
            'year' => $year,
            'search' => $search,
            'selectedDistrictId' => $districtId,
            'availableYears' => (new GetAvailableYearsAction)->execute(),
        ];
    }
}
