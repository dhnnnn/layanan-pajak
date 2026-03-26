<?php

namespace App\Actions\Tax;

use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxType;
use App\Models\Upt;
use Illuminate\Support\Collection;

class GenerateTaxDashboardAction
{
    public function __construct(
        private readonly CalculateTaxRealizationAction $calculateTaxRealization,
        private readonly CalculateAchievementPercentageAction $calculateAchievementPercentage,
    ) {}

    /**
     * @return array{
     *     data: Collection<int, array{
     *         tax_type_id: string,
     *         tax_type_name: string,
     *         tax_type_code: string,
     *         tax_type_parent_id: ?string,
     *         year: int,
     *         target_total: float,
     *         targets: array{q1: float, q2: float, q3: float, q4: float},
     *         realizations: array{q1: float, q2: float, q3: float, q4: float},
     *         percentages: array{q1: float, q2: float, q3: float, q4: float},
     *         total_realization: float,
     *         more_less: float,
     *         achievement_percentage: float,
     *         is_parent: bool,
     *     }>,
     *     totals: array{
     *         target: float,
     *         realization: float,
     *         more_less: float,
     *         percentage: float,
     *         quarters: array<string, array{target: float, realization: float, percentage: float}>
     *     }
     * }
     */
    public function __invoke(int $year, ?string $districtId = null, ?string $uptId = null, ?string $search = null): array
    {
        $taxTypes = TaxType::query()
            ->with([
                'taxTargets' => fn ($query) => $query->where('year', $year),
                'uptComparisons' => fn ($query) => $query->where('year', $year)
                    ->when($uptId, fn ($q) => $q->where('upt_id', $uptId)),
                'children' => fn ($query) => $query->with([
                    'taxTargets' => fn ($q) => $q->where('year', $year),
                    'uptComparisons' => fn ($q) => $q->where('year', $year)
                        ->when($uptId, fn ($q2) => $q2->where('upt_id', $uptId)),
                ]),
            ])
            ->whereNull('parent_id')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('children', fn ($q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                    );
            }))
            ->get();

        // Determine relevant district IDs
        $filterDistrictIds = null;
        if ($districtId) {
            $filterDistrictIds = [$districtId];
        } elseif ($uptId) {
            $filterDistrictIds = Upt::find($uptId)?->districts->pluck('id')->toArray();
        }

        // 1. Fetch monthly realizations from TaxRealization (Legacy/Import source)
        $monthlyRealizations = TaxRealization::query()
            ->where('year', $year)
            ->when($filterDistrictIds, fn ($q) => $q->whereIn('district_id', $filterDistrictIds))
            ->get()
            ->groupBy('tax_type_id');

        // 2. Fetch all daily entries for the year (Direct officer input source)
        $dailyRealizations = TaxRealizationDailyEntry::query()
            ->whereYear('entry_date', $year)
            ->when($filterDistrictIds, fn ($q) => $q->whereIn('district_id', $filterDistrictIds))
            ->selectRaw('tax_type_id, MONTH(entry_date) as month, SUM(amount) as total')
            ->groupBy(['tax_type_id', 'month'])
            ->get()
            ->groupBy('tax_type_id');

        $result = collect();

        foreach ($taxTypes as $parent) {
            $parentData = $this->processTaxType($parent, $year, $monthlyRealizations, $dailyRealizations, $uptId);

            // If parent has children, aggregate their values
            if ($parent->children->isNotEmpty()) {
                $childItems = collect();
                foreach ($parent->children as $child) {
                    $childData = $this->processTaxType($child, $year, $monthlyRealizations, $dailyRealizations, $uptId);
                    $childItems->push($childData);
                }

                // Ensure parent includes its own values PLUS children values
                // For targets, if the parent has its own target in TaxTarget, use it (it should be the official total).
                // If it doesn't, use the sum of children.
                if ($parentData['target_total'] <= 0) {
                    $parentData['target_total'] = $childItems->sum('target_total');
                }

                $parentData['total_realization'] += $childItems->sum('total_realization');
                $parentData['more_less'] = $parentData['total_realization'] - $parentData['target_total'];
                $parentData['achievement_percentage'] = ($this->calculateAchievementPercentage)(
                    $parentData['total_realization'],
                    $parentData['target_total']
                );

                foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                    // Pre-fix parents targets if they are in database
                    // For realizations, always sum
                    $parentData['targets'][$q] = $parentData['targets'][$q] ?: $childItems->sum(fn ($c) => $c['targets'][$q]);
                    $parentData['realizations'][$q] += $childItems->sum(fn ($c) => $c['realizations'][$q]);
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

        // Calculate Grand Totals
        // Ensure we only sum items that have NO parent (Induk / Root)
        $parentItems = $result->where('tax_type_parent_id', null);

        $grandTotalTarget = $parentItems->sum('target_total');
        $grandTotalRealization = $parentItems->sum('total_realization');
        $grandTotalMoreLess = $grandTotalRealization - $grandTotalTarget;
        $grandTotalPercentage = ($this->calculateAchievementPercentage)($grandTotalRealization, $grandTotalTarget);

        $quarterTotals = [];
        foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
            $t = $parentItems->sum(fn ($i) => $i['targets'][$q]);
            $r = $parentItems->sum(fn ($i) => $i['realizations'][$q]);
            $quarterTotals[$q] = [
                'target' => $t,
                'realization' => $r,
                'percentage' => ($this->calculateAchievementPercentage)($r, $t),
            ];
        }

        return [
            'data' => $result,
            'totals' => [
                'target' => $grandTotalTarget,
                'realization' => $grandTotalRealization,
                'more_less' => $grandTotalMoreLess,
                'percentage' => $grandTotalPercentage,
                'quarters' => $quarterTotals,
            ],
        ];
    }

    private function processTaxType(TaxType $taxType, int $year, Collection $monthlyRealizations, Collection $dailyRealizations, ?string $uptId = null): array
    {
        // Get quarterly sums from TaxRealization table (legacy/import source)
        $quarterlyFromMonthly = $monthlyRealizations
            ->get($taxType->id, collect())
            ->reduce(function (array $carry, $rec): array {
                $calculated = ($this->calculateTaxRealization)($rec);
                foreach (['q1', 'q2', 'q3', 'q4'] as $quarter) {
                    $carry[$quarter] += $calculated[$quarter];
                }

                return $carry;
            }, ['q1' => 0.0, 'q2' => 0.0, 'q3' => 0.0, 'q4' => 0.0]);

        // Add daily entry totals, grouped into quarters (direct officer input source)
        $quarterlyFromDaily = $dailyRealizations
            ->get($taxType->id, collect())
            ->groupBy(fn ($daily) => match (true) {
                (int) $daily->month <= 3 => 'q1',
                (int) $daily->month <= 6 => 'q2',
                (int) $daily->month <= 9 => 'q3',
                default => 'q4',
            })
            ->map(fn ($group) => (float) $group->sum('total'));

        $rq1 = $quarterlyFromMonthly['q1'] + (float) ($quarterlyFromDaily['q1'] ?? 0);
        $rq2 = $quarterlyFromMonthly['q2'] + (float) ($quarterlyFromDaily['q2'] ?? 0);
        $rq3 = $quarterlyFromMonthly['q3'] + (float) ($quarterlyFromDaily['q3'] ?? 0);
        $rq4 = $quarterlyFromMonthly['q4'] + (float) ($quarterlyFromDaily['q4'] ?? 0);

        $target = $taxType->taxTargets->first();

        // Use UPT-specific target if uptId is provided, otherwise fall back to global target
        $uptTarget = $uptId ? $taxType->uptComparisons->where('upt_id', $uptId)->first() : null;
        $targetTotal = $uptTarget ? (float) $uptTarget->target_amount : ($target ? (float) $target->target_amount : 0.0);

        // For quarterly targets, if UPT target exists, we'll distribute it using global ratios or equally
        // If global target exists, compute its non-cumulative quarterly targets
        $gtTotal = $target ? (float) $target->target_amount : 0.0;
        $tq1 = 0.0;
        $tq2 = 0.0;
        $tq3 = 0.0;
        $tq4 = 0.0;

        if ($uptTarget && $gtTotal > 0) {
            // Apply global target ratios to the UPT target (keeping it cumulative)
            $tq1 = ($target->q1_target / $gtTotal) * $targetTotal;
            $tq2 = ($target->q2_target / $gtTotal) * $targetTotal;
            $tq3 = ($target->q3_target / $gtTotal) * $targetTotal;
            $tq4 = ($target->q4_target / $gtTotal) * $targetTotal;
        } elseif ($uptTarget) {
            // Standard cumulative distribution
            $tq1 = $targetTotal * 0.25;
            $tq2 = $targetTotal * 0.50;
            $tq3 = $targetTotal * 0.75;
            $tq4 = $targetTotal;
        } elseif ($target) {
            // Use cumulative targets from DB directly, fallback to distribution if 0
            $tq1 = (float) $target->q1_target ?: $targetTotal * 0.25;
            $tq2 = (float) $target->q2_target ?: $targetTotal * 0.50;
            $tq3 = (float) $target->q3_target ?: $targetTotal * 0.75;
            $tq4 = (float) $target->q4_target ?: $targetTotal;
        } else {
            // No target record, use default distribution
            $tq1 = $targetTotal * 0.25;
            $tq2 = $targetTotal * 0.50;
            $tq3 = $targetTotal * 0.75;
            $tq4 = $targetTotal;
        }

        // Calculate cumulative realizations
        $cq1 = $rq1;
        $cq2 = $cq1 + $rq2;
        $cq3 = $cq2 + $rq3;
        $cq4 = $cq3 + $rq4;

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
                'q1' => $cq1,
                'q2' => $cq2,
                'q3' => $cq3,
                'q4' => $cq4,
            ],
            'percentages' => [
                'q1' => ($this->calculateAchievementPercentage)($cq1, $tq1),
                'q2' => ($this->calculateAchievementPercentage)($cq2, $tq2),
                'q3' => ($this->calculateAchievementPercentage)($cq3, $tq3),
                'q4' => ($this->calculateAchievementPercentage)($cq4, $tq4),
            ],
            'total_realization' => $totalRealization,
            'more_less' => $totalRealization - $targetTotal,
            'achievement_percentage' => ($this->calculateAchievementPercentage)($totalRealization, $targetTotal),
        ];
    }
}
