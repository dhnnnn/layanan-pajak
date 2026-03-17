<?php

namespace App\Actions\Tax;

use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\User;
use Illuminate\Support\Carbon;

class StoreDailyEntryAction
{
    /**
     * Store a daily entry and sync the monthly total into TaxRealization.
     *
     * @param array{
     *     tax_type_id: int,
     *     district_id: int,
     *     entry_date: string,
     *     amount: float,
     *     note?: string|null,
     * } $data
     */
    public function __invoke(array $data, User $user): TaxRealizationDailyEntry
    {
        $entry = TaxRealizationDailyEntry::query()->create([
            'tax_type_id' => $data['tax_type_id'],
            'district_id' => $data['district_id'],
            'user_id' => $user->id,
            'entry_date' => $data['entry_date'],
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
        ]);

        $this->syncMonthlyTotal($entry, $user);

        return $entry;
    }

    private function syncMonthlyTotal(TaxRealizationDailyEntry $entry, User $user): void
    {
        $date = Carbon::parse($entry->entry_date);
        $year = $date->year;
        $columnName = $this->monthColumn($date->month);

        // Sum all daily entries for this tax_type + district + year + month
        $monthlyTotal = TaxRealizationDailyEntry::query()
            ->where('tax_type_id', $entry->tax_type_id)
            ->where('district_id', $entry->district_id)
            ->whereYear('entry_date', $year)
            ->whereMonth('entry_date', $date->month)
            ->sum('amount');

        TaxRealization::query()->updateOrCreate(
            [
                'tax_type_id' => $entry->tax_type_id,
                'district_id' => $entry->district_id,
                'year' => $year,
            ],
            [
                'user_id' => $user->id,
                $columnName => $monthlyTotal,
            ],
        );
    }

    private function monthColumn(int $month): string
    {
        return match ($month) {
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
            6 => 'june',
            7 => 'july',
            8 => 'august',
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
        };
    }
}
