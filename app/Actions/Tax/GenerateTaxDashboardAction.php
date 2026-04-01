<?php

namespace App\Actions\Tax;

use App\Models\SimpaduTarget;
use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxType;
use App\Models\Upt;
use Illuminate\Support\Collection;
use App\Actions\Simpadu\GetSimpaduRealizationAction;

class GenerateTaxDashboardAction
{
    public function __construct(
        private readonly CalculateTaxRealizationAction $calculateTaxRealization,
        private readonly CalculateAchievementPercentageAction $calculateAchievementPercentage,
        private readonly GetSimpaduRealizationAction $getSimpaduRealization,
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
                'children' => fn ($query) => $query->with([
                    'taxTargets' => fn ($q) => $q->where('year', $year),
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

        // 0. Fetch Simpadu realization data
        $simpaduResults = ($this->getSimpaduRealization)($year);
        $simpaduRealizations = collect($simpaduResults)->groupBy('ayat');


        // Determine relevant district IDs
        $filterDistrictIds = null;
        if ($districtId) {
            $filterDistrictIds = [$districtId];
        } elseif ($uptId) {
            $filterDistrictIds = Upt::find($uptId)?->districts->pluck('id')->toArray();
        }

        // Fetch Simpadu targets for the year to avoid N+1
        $simpaduTargets = SimpaduTarget::query()
            ->where('year', $year)
            ->get()
            ->keyBy('no_ayat');

        // 1. Fetch monthly realizations from TaxRealization (Legacy/Import source)
        $monthlyRealizations = TaxRealization::query()
            ->where('year', $year)
            ->when($filterDistrictIds, fn ($q) => $q->whereIn('district_id', $filterDistrictIds))
            ->get()
            ->groupBy('tax_type_id');

        // 2. Fetch all daily entries for the year (Direct officer input source)
        $monthSql = config('database.default') === 'sqlite'
            ? "strftime('%m', entry_date)"
            : 'MONTH(entry_date)';

        $dailyRealizations = TaxRealizationDailyEntry::query()
            ->whereYear('entry_date', $year)
            ->when($filterDistrictIds, fn ($q) => $q->whereIn('district_id', $filterDistrictIds))
            ->selectRaw("tax_type_id, {$monthSql} as month, SUM(amount) as total")
            ->groupBy(['tax_type_id', 'month'])
            ->get()
            ->groupBy('tax_type_id');

        $result = $taxTypes->flatMap(function (TaxType $parent) use ($year, $monthlyRealizations, $dailyRealizations, $simpaduRealizations, $simpaduTargets, $uptId, $districtId) {
            $parentData = $this->processTaxType($parent, $year, $monthlyRealizations, $dailyRealizations, $simpaduRealizations, $simpaduTargets, $uptId, $districtId);

            if ($parent->children->isEmpty()) {
                $parentData['is_parent'] = false;
                return [$parentData];
            }

            // Process children (Level 2)
            $childItems = $parent->children->map(function (TaxType $child) use ($year, $monthlyRealizations, $dailyRealizations, $simpaduRealizations, $simpaduTargets, $uptId, $districtId) {
                $childData = $this->processTaxType($child, $year, $monthlyRealizations, $dailyRealizations, $simpaduRealizations, $simpaduTargets, $uptId, $districtId);
                
                // CRITICAL: If Level 2 child (e.g. Hotel) has its own children (Level 3, e.g. Bintang Lima),
                // we must aggregate Level 3 into this Level 2 item, but NOT show Level 3 in the table.
                if ($child->children->isNotEmpty()) {
                    $subChildren = $child->children->map(fn($sc) => $this->processTaxType($sc, $year, $monthlyRealizations, $dailyRealizations, $simpaduRealizations, $simpaduTargets, $uptId, $districtId));
                    
                    // Sum targets
                    if ($childData['target_total'] <= 0) {
                        $childData['target_total'] = $subChildren->sum('target_total');
                    }
                    
                    // Sum realizations
                    $childData['total_realization'] += $subChildren->sum('total_realization');
                    $childData['more_less'] = $childData['total_realization'] - $childData['target_total'];
                    $childData['achievement_percentage'] = ($this->calculateAchievementPercentage)($childData['total_realization'], $childData['target_total']);

                    foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                        $childData['targets'][$q] = $childData['targets'][$q] ?: $subChildren->sum(fn($sc) => $sc['targets'][$q]);
                        $childData['realizations'][$q] += $subChildren->sum(fn($sc) => $sc['realizations'][$q]);
                        $childData['percentages'][$q] = ($this->calculateAchievementPercentage)($childData['realizations'][$q], $childData['targets'][$q]);
                    }
                }
                
                $childData['is_parent'] = false;
                return $childData;
            });

            // Aggregate Level 2 children into Parent (Level 1, e.g. PBJT)
            if ($parentData['target_total'] <= 0) {
                $parentData['target_total'] = $childItems->sum('target_total');
            }

            $parentData['total_realization'] += $childItems->sum('total_realization');
            $parentData['more_less'] = $parentData['total_realization'] - $parentData['target_total'];
            $parentData['achievement_percentage'] = ($this->calculateAchievementPercentage)($parentData['total_realization'], $parentData['target_total']);

            foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                $parentData['targets'][$q] = $parentData['targets'][$q] ?: $childItems->sum(fn($c) => $c['targets'][$q]);
                $parentData['realizations'][$q] += $childItems->sum(fn($c) => $c['realizations'][$q]);
                $parentData['percentages'][$q] = ($this->calculateAchievementPercentage)($parentData['realizations'][$q], $parentData['targets'][$q]);
            }

            $parentData['is_parent'] = true;

            // Return only Level 1 and Level 2 (Hide Level 3)
            return collect([$parentData])->concat($childItems);
        });

        // 147. Calculate Grand Totals
        // To avoid double counting, we sum from the Level 1 root items only
        $rootItems = $result->where('tax_type_parent_id', null);

        $grandTotalTarget = $rootItems->sum('target_total');
        $grandTotalRealization = $rootItems->sum('total_realization');
        $grandTotalMoreLess = $grandTotalRealization - $grandTotalTarget;
        $grandTotalPercentage = ($this->calculateAchievementPercentage)($grandTotalRealization, $grandTotalTarget);

        $quarterTotals = collect(['q1', 'q2', 'q3', 'q4'])->mapWithKeys(function ($q) use ($rootItems) {
            $t = $rootItems->sum(fn ($i) => (float) $i['targets'][$q]);
            $r = $rootItems->sum(fn ($i) => (float) $i['realizations'][$q]);

            return [$q => [
                'target' => $t,
                'realization' => $r,
                'percentage' => ($this->calculateAchievementPercentage)($r, $t),
            ]];
        })->toArray();

        // 160. Filter out items with 0 target AND 0 realization (User request to hide empty rows)
        $filteredResult = $result->reject(function ($item) {
            return $item['target_total'] <= 0 && $item['total_realization'] <= 0;
        });

        return [
            'data' => $filteredResult,
            'totals' => [
                'target' => $grandTotalTarget,
                'realization' => $grandTotalRealization,
                'more_less' => $grandTotalMoreLess,
                'percentage' => $grandTotalPercentage,
                'quarters' => $quarterTotals,
            ],
        ];
    }

    private function processTaxType(TaxType $taxType, int $year, Collection $monthlyRealizations, Collection $dailyRealizations, Collection $simpaduRealizations, Collection $simpaduTargets, ?string $uptId = null, ?string $districtId = null): array
    {
        // 0. Filter Simpadu data for this tax type and optionally for specific district
        $simpaduItems = $simpaduRealizations->get($taxType->simpadu_code, collect());
        
        if ($districtId) {
            $district = \App\Models\District::find($districtId);
            if ($district && $district->simpadu_code) {
                $simpaduItems = $simpaduItems->where('kd_kecamatan', $district->simpadu_code);
            }
        } elseif ($uptId) {
            $uptDistricts = \App\Models\Upt::find($uptId)?->districts->pluck('simpadu_code')->filter()->toArray();
            $simpaduItems = $simpaduItems->whereIn('kd_kecamatan', $uptDistricts);
        }

        $simpaduTotal = (float) $simpaduItems->sum('total_bayar');

        // Group Simpadu data into quarters
        $quarterlyFromSimpadu = $simpaduItems->groupBy(fn ($item) => match (true) {
            (int) $item->bulan <= 3 => 'q1',
            (int) $item->bulan <= 6 => 'q2',
            (int) $item->bulan <= 9 => 'q3',
            default => 'q4',
        })->map(fn ($group) => (float) $group->sum('total_bayar'));

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

        // Combine all sources (Simpadu + Local Monthly + Local Daily)
        $rq1 = (float) ($quarterlyFromSimpadu['q1'] ?? 0) + $quarterlyFromMonthly['q1'] + (float) ($quarterlyFromDaily['q1'] ?? 0);
        $rq2 = (float) ($quarterlyFromSimpadu['q2'] ?? 0) + $quarterlyFromMonthly['q2'] + (float) ($quarterlyFromDaily['q2'] ?? 0);
        $rq3 = (float) ($quarterlyFromSimpadu['q3'] ?? 0) + $quarterlyFromMonthly['q3'] + (float) ($quarterlyFromDaily['q3'] ?? 0);
        $rq4 = (float) ($quarterlyFromSimpadu['q4'] ?? 0) + $quarterlyFromMonthly['q4'] + (float) ($quarterlyFromDaily['q4'] ?? 0);

        $target = $taxType->taxTargets->first();
        
        // 1. Try to get Simpadu target from pre-fetched collection
        $sTarget = $simpaduTargets->get($taxType->simpadu_code);

        $targetTotal = 0.0;
        if ($target) {
            $targetTotal = (float) $target->target_amount;
        } elseif ($sTarget) {
            $targetTotal = (float) $sTarget->total_target;
        }

        $tq1 = 0.0;
        $tq2 = 0.0;
        $tq3 = 0.0;
        $tq4 = 0.0;

        if ($target) {
            // Use cumulative targets from local DB directly (Manual Override)
            $tq1 = (float) $target->q1_target ?: $targetTotal * 0.25;
            $tq2 = (float) $target->q2_target ?: $targetTotal * 0.50;
            $tq3 = (float) $target->q3_target ?: $targetTotal * 0.75;
            $tq4 = (float) $target->q4_target ?: $targetTotal;
        } elseif ($sTarget) {
            // Use percentages from Simpadu baseline
            $tq1 = $targetTotal * ($sTarget->q1_pct / 100);
            $tq2 = $targetTotal * ($sTarget->q2_pct / 100);
            $tq3 = $targetTotal * ($sTarget->q3_pct / 100);
            $tq4 = $targetTotal * ($sTarget->q4_pct / 100);
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
            'tax_target_id' => $target?->id, // Add this for management links
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
            'simpadu_realization' => $simpaduTotal,
            'more_less' => $totalRealization - $targetTotal,
            'achievement_percentage' => ($this->calculateAchievementPercentage)($totalRealization, $targetTotal),
            'simpadu_achievement_percentage' => ($this->calculateAchievementPercentage)($simpaduTotal, $targetTotal),
        ];
    }

}
