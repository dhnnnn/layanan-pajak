<?php

namespace App\Actions\Tax;

use App\Models\TaxRealization;
use App\Models\TaxType;
use App\Models\UptComparison;
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
     *     year: int,
     *     target_amount: float,
     *     q1: float,
     *     q2: float,
     *     q3: float,
     *     q4: float,
     *     total_realization: float,
     *     remaining_target: float,
     *     achievement_percentage: float,
     * }>
     */
    public function __invoke(int $year, ?string $districtId = null, ?string $uptId = null): Collection
    {
        $taxTypes = TaxType::query()
            ->with([
                'taxTargets' => fn ($query) => $query->where('year', $year),
            ])
            ->get();

        // If uptId provided, load UPT-specific targets keyed by tax_type_id
        $uptTargets = collect();
        if ($uptId !== null) {
            $uptTargets = UptComparison::query()
                ->where('upt_id', $uptId)
                ->where('year', $year)
                ->get()
                ->keyBy('tax_type_id');
        }

        // Preload all realizations for this year in one query, then group by
        // tax_type_id to avoid N+1 inside the map loop.
        $realizationQuery = TaxRealization::query()->where('year', $year);

        if ($districtId !== null) {
            $realizationQuery->where('district_id', $districtId);
        }

        /** @var Collection<int, Collection<int, TaxRealization>> $realizationsByType */
        $realizationsByType = $realizationQuery->get()->groupBy('tax_type_id');

        return $taxTypes->map(function (TaxType $taxType) use (
            $year,
            $realizationsByType,
            $uptTargets,
        ): array {
            $realizations = $realizationsByType->get($taxType->id, collect());

            $q1 = 0.0;
            $q2 = 0.0;
            $q3 = 0.0;
            $q4 = 0.0;

            foreach ($realizations as $realization) {
                $calculated = ($this->calculateTaxRealization)($realization);
                $q1 += $calculated['q1'];
                $q2 += $calculated['q2'];
                $q3 += $calculated['q3'];
                $q4 += $calculated['q4'];
            }

            $totalRealization = $q1 + $q2 + $q3 + $q4;

            // Use UPT-specific target if available, otherwise fall back to global target
            if ($uptTargets->has($taxType->id)) {
                $targetAmount = (float) $uptTargets->get($taxType->id)->target_amount;
            } else {
                $target = $taxType->taxTargets->first();
                $targetAmount = $target ? (float) $target->target_amount : 0.0;
            }

            return [
                'tax_type_id' => $taxType->id,
                'tax_type_name' => $taxType->name,
                'tax_type_code' => $taxType->code,
                'year' => $year,
                'target_amount' => $targetAmount,
                'q1' => $q1,
                'q2' => $q2,
                'q3' => $q3,
                'q4' => $q4,
                'total_realization' => $totalRealization,
                'remaining_target' => max(
                    0.0,
                    $targetAmount - $totalRealization,
                ),
                'achievement_percentage' => ($this->calculateAchievementPercentage)(
                    $totalRealization,
                    $targetAmount,
                ),
            ];
        });
    }
}
