<?php

namespace App\Actions\Upt;

use App\Models\TaxTarget;
use App\Models\TaxType;
use App\Models\Upt;
use App\Models\UptComparison;
use Illuminate\Support\Collection;

class GetUptComparisonDataAction
{
    /**
     * @return array{
     *     upts: Collection<int, Upt>,
     *     taxTypes: Collection<int, TaxType>,
     *     targets: Collection<string, float>,
     *     availableYears: Collection<int, int>,
     *     year: int,
     *     uptId: ?string
     * }
     */
    public function __invoke(int $year, ?string $uptId = null): array
    {
        $upts = Upt::query()->orderBy('code')->get();

        $uptsWithTargets = UptComparison::query()
            ->where('year', $year)
            ->distinct()
            ->pluck('upt_id')
            ->toArray();

        $upts->each(function ($upt) use ($uptsWithTargets) {
            $upt->has_targets = in_array($upt->id, $uptsWithTargets);
        });

        if (! $uptId || ! $upts->contains('id', $uptId)) {
            $firstWithTargets = $upts->firstWhere('has_targets', true);
            $uptId = $firstWithTargets ? $firstWithTargets->id : ($upts->first()->id ?? null);
        }

        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('code')])
            ->orderBy('code')
            ->get();

        $targets = UptComparison::query()
            ->where('upt_id', $uptId)
            ->where('year', $year)
            ->pluck('target_amount', 'tax_type_id');

        // Pre-calculate parent totals
        $taxTypes->each(function ($taxType) use ($targets) {
            if ($taxType->children->isNotEmpty()) {
                $sum = $taxType->children->sum(fn ($child) => (float) ($targets[$child->id] ?? 0));
                $targets[$taxType->id] = $sum;
            }
        });

        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if ($availableYears->isEmpty()) {
            $availableYears = collect([(int) date('Y')]);
        }

        return [
            'upts' => $upts,
            'taxTypes' => $taxTypes,
            'targets' => $targets,
            'availableYears' => $availableYears,
            'year' => $year,
            'uptId' => $uptId,
        ];
    }
}
