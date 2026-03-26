<?php

namespace App\Actions\Upt;

use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxTarget;
use App\Models\TaxType;
use App\Models\Upt;
use App\Models\UptComparison;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GenerateComparisonReportAction
{
    /**
     * @return array{
     *     upts: Collection,
     *     taxTypes: LengthAwarePaginator,
     *     targets: Collection,
     *     uptTargets: array<string, array<string, float>>,
     *     uptRealizationTotals: array<string, array<string, float>>,
     *     grandTotalTarget: float,
     *     grandTotalUpt: array<string, float>,
     *     grandTotalUptTarget: array<string, float>,
     *     grandTotalAllUpt: float,
     *     availableYears: Collection,
     *     year: int,
     * }
     */
    public function __invoke(int $year, ?string $search = null): array
    {
        $search = $search ? trim($search) : null;

        $upts = Upt::query()->orderBy('code')->get();

        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('code')])
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('children', fn ($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            }))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        $targets = TaxTarget::query()
            ->where('year', $year)
            ->pluck('target_amount', 'tax_type_id');

        $uptTargets = UptComparison::query()
            ->where('year', $year)
            ->get()
            ->groupBy('upt_id')
            ->map(fn (Collection $rows): array => $rows->keyBy('tax_type_id')
                ->map(fn ($v) => (float) $v->target_amount)
                ->toArray())
            ->toArray();

        $uptRealizationTotals = $this->calculateUptRealizationTotals($upts, $year);

        $grandTotals = $this->calculateGrandTotals($upts, $uptRealizationTotals, $uptTargets, $year);

        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return [
            'upts' => $upts,
            'taxTypes' => $taxTypes,
            'targets' => $targets,
            'uptTargets' => $uptTargets,
            'uptRealizationTotals' => $uptRealizationTotals,
            'grandTotalTarget' => $grandTotals['target'],
            'grandTotalUpt' => $grandTotals['upt'],
            'grandTotalUptTarget' => $grandTotals['uptTarget'],
            'grandTotalAllUpt' => $grandTotals['allUpt'],
            'availableYears' => $availableYears,
            'year' => $year,
        ];
    }

    /**
     * @param  Collection<Upt>  $upts
     * @return array<string, array<string, float>>
     */
    private function calculateUptRealizationTotals(Collection $upts, int $year): array
    {
        $userIdsByUpt = $upts->mapWithKeys(
            fn (Upt $upt) => [$upt->id => $upt->users()->role('pegawai')->pluck('users.id')]
        );

        $allEntries = TaxRealizationDailyEntry::query()
            ->whereIn('user_id', $userIdsByUpt->flatten())
            ->whereYear('entry_date', $year)
            ->selectRaw('user_id, tax_type_id, SUM(amount) as total')
            ->groupBy(['user_id', 'tax_type_id'])
            ->get()
            ->groupBy('user_id');

        return $userIdsByUpt->mapWithKeys(function (Collection $userIds, string $uptId) use ($allEntries): array {
            $byTaxType = $userIds->flatMap(fn (string $userId): Collection => $allEntries->get($userId, collect()))
                ->groupBy('tax_type_id')
                ->map(fn (Collection $entries): float => (float) $entries->sum('total'));

            return [$uptId => $byTaxType->toArray()];
        })->toArray();
    }

    /**
     * @param  Collection<Upt>  $upts
     * @param  array<string, array<string, float>>  $uptRealizationTotals
     * @param  array<string, array<string, float>>  $uptTargets
     * @return array{
     *     target: float,
     *     upt: array<string, float>,
     *     uptTarget: array<string, float>,
     *     allUpt: float,
     * }
     */
    private function calculateGrandTotals(Collection $upts, array $uptRealizationTotals, array $uptTargets, int $year): array
    {
        $grandTotalTarget = (float) TaxTarget::query()->where('year', $year)->sum('target_amount');

        $grandTotalUpt = $upts->mapWithKeys(function (Upt $upt) use ($uptRealizationTotals): array {
            $total = array_sum($uptRealizationTotals[$upt->id] ?? []);

            return [$upt->id => $total];
        })->toArray();

        $grandTotalAllUpt = array_sum($grandTotalUpt);

        $grandTotalUptTarget = $upts->mapWithKeys(function (Upt $upt) use ($year): array {
            $total = (float) UptComparison::query()
                ->where('upt_id', $upt->id)
                ->where('year', $year)
                ->sum('target_amount');

            return [$upt->id => $total];
        })->toArray();

        return [
            'target' => $grandTotalTarget,
            'upt' => $grandTotalUpt,
            'uptTarget' => $grandTotalUptTarget,
            'allUpt' => $grandTotalAllUpt,
        ];
    }
}
