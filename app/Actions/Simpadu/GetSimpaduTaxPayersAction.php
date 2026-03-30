<?php

namespace App\Actions\Simpadu;

use Illuminate\Support\Facades\DB;

class GetSimpaduTaxPayersAction
{
    /**
     * Fetch Tax Payers monitoring data from simpadunew.
     */
    public function __invoke(int $year, ?string $districtCode = null, ?string $search = null)
    {
        return DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->when($districtCode, fn ($q) => $q->where('kd_kecamatan', $districtCode))
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nm_wp', 'like', "%{$search}%")
                  ->orWhere('npwpd', 'like', "%{$search}%")
                  ->orWhere('nm_op', 'like', "%{$search}%");
            }))
            ->get();
    }
}
