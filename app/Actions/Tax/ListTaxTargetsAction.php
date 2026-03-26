<?php

namespace App\Actions\Tax;

use App\Models\TaxTarget;
use App\Models\TaxType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListTaxTargetsAction
{
    /**
     * @return array{
     *     taxTypes: LengthAwarePaginator,
     *     targets: Collection,
     *     availableYears: Collection,
     *     year: int,
     * }
     */
    public function __invoke(?string $search = null, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        $search = $search ? trim($search) : null;

        $taxTypes = TaxType::query()
            ->with(['children' => fn ($q) => $q->orderBy('code')])
            ->whereNull('parent_id')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('children', fn ($q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                    );
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $targets = $this->aggregateTargets($taxTypes, $year);

        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return [
            'taxTypes' => $taxTypes,
            'targets' => $targets,
            'availableYears' => $availableYears,
            'year' => $year,
        ];
    }

    private function aggregateTargets(LengthAwarePaginator $taxTypes, int $year): Collection
    {
        $allTargets = TaxTarget::query()
            ->where('year', $year)
            ->get()
            ->keyBy('tax_type_id');

        $result = collect();

        foreach ($taxTypes as $taxType) {
            if ($taxType->children->isNotEmpty()) {
                // Sum up child targets (include children even without a target record)
                $childTargets = $taxType->children->map(
                    fn (TaxType $child) => $allTargets->get($child->id)
                );

                $totals = $childTargets->reduce(
                    fn (array $carry, ?TaxTarget $target) => [
                        'amount' => $carry['amount'] + (float) ($target?->target_amount ?? 0),
                        'q1' => $carry['q1'] + (float) ($target?->q1_target ?? ($target?->target_amount ?? 0) * 0.25),
                        'q2' => $carry['q2'] + (float) ($target?->q2_target ?? ($target?->target_amount ?? 0) * 0.50),
                        'q3' => $carry['q3'] + (float) ($target?->q3_target ?? ($target?->target_amount ?? 0) * 0.75),
                        'q4' => $carry['q4'] + (float) ($target?->q4_target ?? ($target?->target_amount ?? 0)),
                    ],
                    ['amount' => 0.0, 'q1' => 0.0, 'q2' => 0.0, 'q3' => 0.0, 'q4' => 0.0]
                );

                $aggregated = new TaxTarget([
                    'tax_type_id' => $taxType->id,
                    'year' => $year,
                    'target_amount' => $totals['amount'],
                    'q1_target' => $totals['q1'],
                    'q2_target' => $totals['q2'],
                    'q3_target' => $totals['q3'],
                    'q4_target' => $totals['q4'],
                ]);
                $aggregated->exists = true;

                $result[$taxType->id] = $aggregated;

                // Also include each child target individually ONLY if they exist
                foreach ($taxType->children as $child) {
                    if ($allTargets->has($child->id)) {
                        $result[$child->id] = $allTargets->get($child->id);
                    }
                }
            } else {
                // Root-level type without children: use existing target ONLY
                if ($allTargets->has($taxType->id)) {
                    $result[$taxType->id] = $allTargets->get($taxType->id);
                }
            }
        }

        return $result;
    }
}
