<?php

namespace App\Actions\Simpadu;

use App\Models\User;

class ResolveDistrictCodesAction
{
    /**
     * Resolve district codes for export based on user role and selected district.
     *
     * @return list<string>|null
     */
    public function __invoke(string $selectedDistrict, User $user): ?array
    {
        if ($user->isKepalaUpt()) {
            $uptCodes = $user->upt()?->districts->pluck('simpadu_code')->toArray() ?? [];

            return ($selectedDistrict !== '' && in_array($selectedDistrict, $uptCodes))
                ? [$selectedDistrict]
                : $uptCodes;
        }

        if ($user->hasRole('pegawai')) {
            $assignedCodes = $user->accessibleDistricts()->pluck('simpadu_code')->filter()->toArray();

            return ($selectedDistrict !== '' && in_array($selectedDistrict, $assignedCodes))
                ? [$selectedDistrict]
                : $assignedCodes;
        }

        if ($selectedDistrict !== '') {
            $code = (is_numeric($selectedDistrict) && strlen($selectedDistrict) < 3)
                ? str_pad($selectedDistrict, 3, '0', STR_PAD_LEFT)
                : $selectedDistrict;

            return [$code];
        }

        return null;
    }
}
