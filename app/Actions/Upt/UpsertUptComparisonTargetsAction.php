<?php

namespace App\Actions\Upt;

use App\Models\TaxType;
use App\Models\UptComparison;

class UpsertUptComparisonTargetsAction
{
    /**
     * @param  array<string, float>  $targets
     */
    public function __invoke(string $uptId, int $year, array $targets): void
    {
        // 1. Save all individual targets
        foreach ($targets as $taxTypeId => $amount) {
            UptComparison::query()->updateOrCreate(
                [
                    'tax_type_id' => $taxTypeId,
                    'upt_id' => $uptId,
                    'year' => $year,
                ],
                [
                    'target_amount' => $amount ?? 0,
                ]
            );
        }

        // 2. Identify and aggregate parent targets
        $parentTaxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->whereHas('children')
            ->with('children')
            ->get();

        $parentTaxTypes->each(function (TaxType $parent) use ($uptId, $year) {
            $sum = UptComparison::query()
                ->where('upt_id', $uptId)
                ->where('year', $year)
                ->whereIn('tax_type_id', $parent->children->pluck('id'))
                ->sum('target_amount');

            UptComparison::query()->updateOrCreate(
                [
                    'tax_type_id' => $parent->id,
                    'upt_id' => $uptId,
                    'year' => $year,
                ],
                [
                    'target_amount' => $sum ?? 0,
                ]
            );
        });
    }
}
