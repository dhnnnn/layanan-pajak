<?php

namespace App\Actions\Tax;

use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxType;
use Illuminate\Support\Collection;

class GetDistrictRealizationDetailsAction
{
    /**
     * @return array{
     *     taxTypes: Collection,
     *     realizations: Collection,
     *     yearlyTotals: Collection
     * }
     */
    public function __invoke(string $districtId, int $year): array
    {
        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        $realizations = TaxRealization::query()
            ->where('district_id', $districtId)
            ->where('year', $year)
            ->get();

        $yearlyTotals = TaxRealizationDailyEntry::query()
            ->where('district_id', $districtId)
            ->whereYear('entry_date', $year)
            ->selectRaw('tax_type_id, SUM(amount) as total')
            ->groupBy('tax_type_id')
            ->pluck('total', 'tax_type_id');

        return [
            'taxTypes' => $taxTypes,
            'realizations' => $realizations,
            'yearlyTotals' => $yearlyTotals,
        ];
    }
}
