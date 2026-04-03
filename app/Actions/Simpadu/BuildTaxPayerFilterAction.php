<?php

namespace App\Actions\Simpadu;

use App\Models\District;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Extracts district filtering logic and view data building from TaxPayerMonitoringController.
 * Resolves district codes based on authenticated user role.
 */
class BuildTaxPayerFilterAction
{
    public function execute(Request $request, GetTaxPayerMatrixAction $getMatrix): array
    {
        $year           = $request->integer('year', (int) date('Y'));
        $monthFrom      = $request->integer('month_from', 1);
        $monthTo        = $request->integer('month_to', (int) date('n'));
        $search         = $request->string('search')->trim();
        $selectedDistrict = $request->string('district');
        $statusFilter   = $request->string('status_filter', '1')->toString();
        $selectedAyat   = $request->string('ayat')->toString();

        $districtCodes = $this->resolveDistrictCodes($selectedDistrict->toString());

        $taxPayers = $getMatrix($year, $monthFrom, $monthTo, (string) $search, $districtCodes, $statusFilter, $selectedAyat ?: null);

        $officers = User::orderBy('name')->get();
        $districts = $this->resolveDistrictsQuery()->get();

        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->whereNotNull('simpadu_code')
            ->orderBy('name')
            ->get(['id', 'name', 'simpadu_code']);

        return [
            'taxPayers'        => $taxPayers,
            'officers'         => $officers,
            'districts'        => $districts,
            'taxTypes'         => $taxTypes,
            'selectedYear'     => $year,
            'selectedMonthFrom' => $monthFrom,
            'selectedMonthTo'  => $monthTo,
            'selectedDistrict' => (string) $selectedDistrict,
            'selectedAyat'     => $selectedAyat,
            'statusFilter'     => $statusFilter,
            'availableYears'   => range(date('Y'), date('Y') - 5),
        ];
    }

    private function resolveDistrictCodes(string $selectedDistrict): ?array
    {
        $user = auth()->user();

        if ($user->isKepalaUpt()) {
            $uptCodes = $user->upt->districts->pluck('simpadu_code')->toArray();
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

    private function resolveDistrictsQuery()
    {
        $user  = auth()->user();
        $query = District::orderBy('name');

        if ($user->isKepalaUpt()) {
            $query->whereIn('id', $user->upt->districts->pluck('id'));
        } elseif ($user->hasRole('pegawai')) {
            $query->whereIn('id', $user->accessibleDistricts()->pluck('id'));
        }

        return $query;
    }
}
