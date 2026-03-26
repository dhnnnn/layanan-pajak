<?php

namespace App\Actions\Tax;

use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxType;
use Illuminate\Support\Collection;

class GenerateTaxDashboardAction
{
    public function __construct(
        private readonly CalculateTaxRealizationAction $calculateTaxRealization,
        private readonly CalculateAchievementPercentageAction $calculateAchievementPercentage,
    ) {}

    /**
     * @return Collection<int, array{
     *     tax_type_id: string,
     *     tax_type_name: string,
     *     tax_type_code: string,
     *     tax_type_parent_id: ?string,
     *     year: int,
     *     target_total: float,
     *     targets: array{q1: float, q2: float, q3: float, q4: float},
     *     realizations: array{q1: float, q2: float, q3: float, q4: float},
     *     percentages: array{q1: float, q2: float, q3: float, q4: float},
     *     total_realization: float,
     *     more_less: float,
     *     is_parent: bool,
     * }>
     */
    public function __invoke(int $year): Collection
    {
        $taxTypes = TaxType::query()
            ->with([
                'taxTargets' => fn ($query) => $query->where('year', $year),
                'children' => fn ($query) => $query->with([
                    'taxTargets' => fn ($q) => $q->where('year', $year),
                ]),
            ])
            ->whereNull('parent_id')
            ->get();

        // 1. Fetch monthly realizations from TaxRealization (Legacy/Import source)
        $monthlyRealizations = TaxRealization::query()
            ->where('year', $year)
            ->get()
            ->groupBy('tax_type_id');

        // 2. Fetch all daily entries for the year (Direct officer input source)
        $dailyRealizations = TaxRealizationDailyEntry::query()
            ->whereYear('entry_date', $year)
            ->selectRaw('tax_type_id, MONTH(entry_date) as month, SUM(amount) as total')
            ->groupBy(['tax_type_id', 'month'])
            ->get()
            ->groupBy('tax_type_id');

        $result = collect();

        foreach ($taxTypes as $parent) {
            $parentData = $this->processTaxType($parent, $year, $monthlyRealizations, $dailyRealizations);

            // If parent has children, aggregate their values
            if ($parent->children->isNotEmpty()) {
                $childItems = collect();
                foreach ($parent->children as $child) {
                    $childData = $this->processTaxType($child, $year, $monthlyRealizations, $dailyRealizations);
                    $childItems->push($childData);
                }

                // Sum children values into parent
                $parentData['target_total'] = $childItems->sum('target_total');
                $parentData['total_realization'] = $childItems->sum('total_realization');
                $parentData['more_less'] = $childItems->sum('more_less');

                foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                    $parentData['targets'][$q] = $childItems->sum(fn ($c) => $c['targets'][$q]);
                    $parentData['realizations'][$q] = $childItems->sum(fn ($c) => $c['realizations'][$q]);
                    $parentData['percentages'][$q] = ($this->calculateAchievementPercentage)(
                        $parentData['realizations'][$q],
                        $parentData['targets'][$q]
                    );
                }

                $parentData['is_parent'] = true;
                $result->push($parentData);

                foreach ($childItems as $childData) {
                    $childData['is_parent'] = false;
                    $result->push($childData);
                }
            } else {
                $parentData['is_parent'] = false; // it's a root type without children
                $result->push($parentData);
            }
        }

        return $result;
    }

    private function processTaxType(TaxType $taxType, int $year, Collection $monthlyRealizations, Collection $dailyRealizations): array
    {
        // Get quarterly sums from TaxRealization table
        $monthlyRecs = $monthlyRealizations->get($taxType->id, collect());
        $rq1 = 0.0;
        $rq2 = 0.0;
        $rq3 = 0.0;
        $rq4 = 0.0;
        foreach ($monthlyRecs as $rec) {
            $calculated = ($this->calculateTaxRealization)($rec);
            $rq1 += $calculated['q1'];
            $rq2 += $calculated['q2'];
            $rq3 += $calculated['q3'];
            $rq4 += $calculated['q4'];
        }

        // ADD monthly sums from TaxRealizationDailyEntry table
        $dailyRecs = $dailyRealizations->get($taxType->id, collect());
        foreach ($dailyRecs as $daily) {
            $month = (int) $daily->month;
            $amount = (float) $daily->total;
            if ($month <= 3) {
                $rq1 += $amount;
            } elseif ($month <= 6) {
                $rq2 += $amount;
            } elseif ($month <= 9) {
                $rq3 += $amount;
            } else {
                $rq4 += $amount;
            }
        }

        $target = $taxType->taxTargets->first();
        $targetTotal = $target ? (float) $target->target_amount : 0.0;

        // Use non-cumulative quarterly targets
        $tq1 = $target ? (float) $target->q1_target : 0.0;
        $tq2 = $target ? max(0, (float) $target->q2_target - (float) $target->q1_target) : 0.0;
        $tq3 = $target ? max(0, (float) $target->q3_target - (float) $target->q2_target) : 0.0;
        $tq4 = $target ? max(0, (float) $target->q4_target - (float) $target->q3_target) : 0.0;

        $totalRealization = $rq1 + $rq2 + $rq3 + $rq4;

        return [
            'tax_type_id' => $taxType->id,
            'tax_type_name' => $taxType->name,
            'tax_type_code' => $taxType->code,
            'tax_type_parent_id' => $taxType->parent_id,
            'year' => $year,
            'target_total' => $targetTotal,
            'targets' => [
                'q1' => $tq1,
                'q2' => $tq2,
                'q3' => $tq3,
                'q4' => $tq4,
            ],
            'realizations' => [
                'q1' => $rq1,
                'q2' => $rq2,
                'q3' => $rq3,
                'q4' => $rq4,
            ],
            'percentages' => [
                'q1' => ($this->calculateAchievementPercentage)($rq1, $tq1),
                'q2' => ($this->calculateAchievementPercentage)($rq2, $tq2),
                'q3' => ($this->calculateAchievementPercentage)($rq3, $tq3),
                'q4' => ($this->calculateAchievementPercentage)($rq4, $tq4),
            ],
            'total_realization' => $totalRealization,
            'more_less' => $totalRealization - $targetTotal,
        ];
    }
}
