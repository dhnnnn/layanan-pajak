<?php

namespace App\Actions\FieldOfficer;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetTaxpayerDetailAction
{
    public function execute(User $user, string $npwpd, int $year): array
    {
        $assignedDistrictCodes = $user->accessibleDistricts()
            ->pluck('simpadu_code')
            ->filter()
            ->toArray();

        $wp = DB::table('simpadu_tax_payers')
            ->where('npwpd', $npwpd)
            ->where('year', $year)
            ->whereIn('kd_kecamatan', $assignedDistrictCodes)
            ->first();

        if (! $wp) {
            return [];
        }

        $reports = DB::table('simpadu_sptpd_reports')
            ->where('npwpd', $npwpd)
            ->where('year', $year)
            ->orderBy('month')
            ->get();

        return [
            'wp' => $wp,
            'reports' => $reports,
            'year' => $year,
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ],
        ];
    }
}
