<?php

namespace App\Actions\FieldOfficer;

use App\Models\District;
use App\Models\TaxTarget;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetFieldOfficerArrearsAction
{
    /**
     * Get arrears/payment status data.
     *
     * @return array<string, mixed>
     */
    public function execute(User $user, array $params): array
    {
        $year = $params['year'] ?? (int) date('Y');
        $search = $params['search'] ?? null;
        $districtId = $params['district_id'] ?? null;
        $status = $params['status'] ?? 'belum_lunas'; // default for tunggakan

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

        if ($status === 'lunas') {
            $query->where('stp.total_tunggakan', '<=', 0);
        } elseif ($status === 'belum_lunas') {
            $query->where('stp.total_tunggakan', '>', 0);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('stp.nm_wp', 'like', "%{$search}%")
                    ->orWhere('stp.npwpd', 'like', "%{$search}%")
                    ->orWhere('stp.nop', 'like', "%{$search}%");
            });
        }

        if ($districtId && in_array($districtId, $assignedDistrictIds)) {
            $districtCode = District::find($districtId)?->simpadu_code;
            if ($districtCode) {
                $query->where('stp.kd_kecamatan', $districtCode);
            }
        }

        $taxpayers = $query->orderByDesc('stp.total_tunggakan')->paginate(20);

        $districts = District::whereIn('simpadu_code', $assignedDistrictCodes)
            ->orderBy('name')
            ->get();

        return [
            'taxpayers' => $taxpayers,
            'districts' => $districts,
            'year' => $year,
            'status' => $status,
            'search' => $search,
            'selectedDistrictId' => $districtId,
            'availableYears' => $this->getAvailableYears(),
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ],
        ];
    }

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
