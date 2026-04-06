<?php

namespace App\Actions\FieldOfficer;

use App\Models\TaxTarget;
use Illuminate\Support\Collection;

class GetAvailableYearsAction
{
    /**
     * Get available years for filtering.
     *
     * @return Collection<int, int>
     */
    public function execute(): Collection
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
