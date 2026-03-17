<?php

namespace App\Actions\Tax;

use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use Illuminate\Support\Carbon;

class DeleteDailyEntryAction
{
    public function execute(TaxRealizationDailyEntry $dailyEntry): void
    {
        $taxTypeId = $dailyEntry->tax_type_id;
        $districtId = $dailyEntry->district_id;
        $date = $dailyEntry->entry_date;

        $dailyEntry->delete();

        $columnName = match ((int) Carbon::parse($date)->month) {
            1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april',
            5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august',
            9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december',
        };

        $monthlyTotal = TaxRealizationDailyEntry::query()
            ->where('tax_type_id', $taxTypeId)
            ->where('district_id', $districtId)
            ->whereYear('entry_date', Carbon::parse($date)->year)
            ->whereMonth('entry_date', Carbon::parse($date)->month)
            ->sum('amount');

        TaxRealization::query()
            ->where('tax_type_id', $taxTypeId)
            ->where('district_id', $districtId)
            ->where('year', Carbon::parse($date)->year)
            ->update([$columnName => $monthlyTotal]);
    }
}
