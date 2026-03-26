<?php

namespace App\Actions\Monitoring;

use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxTarget;
use App\Models\Upt;
use App\Models\UptComparison;
use Illuminate\Support\Collection;

class ListUptMonitoringAction
{
    /**
     * @return array{
     *     upts: Collection,
     *     uptTotals: Collection,
     *     uptTargets: Collection,
     *     totalTarget: float,
     *     availableYears: Collection,
     *     year: int,
     * }
     */
    public function __invoke(int $year): array
    {
        $upts = Upt::query()
            ->withCount('users')
            ->with(['users' => function ($q): void {
                $q->role('pegawai')->with('districts');
            }])
            ->orderBy('code')
            ->get();

        $districtIdsByUpt = $upts->mapWithKeys(
            fn (Upt $upt) => [$upt->id => $upt->districts->pluck('id')]
        );

        $legacyTotals = TaxRealization::query()
            ->whereIn('district_id', $districtIdsByUpt->flatten())
            ->where('year', $year)
            ->get()
            ->groupBy('district_id')
            ->map(fn (Collection $recs) => $recs->sum(
                fn ($r) => (float) ($r->january + $r->february + $r->march + $r->april
                    + $r->may + $r->june + $r->july + $r->august
                    + $r->september + $r->october + $r->november + $r->december)
            ));

        $dailyTotals = TaxRealizationDailyEntry::query()
            ->whereIn('district_id', $districtIdsByUpt->flatten())
            ->whereYear('entry_date', $year)
            ->selectRaw('district_id, SUM(amount) as total')
            ->groupBy('district_id')
            ->pluck('total', 'district_id')
            ->map(fn ($total) => (float) $total);

        $uptTotals = $districtIdsByUpt->map(function (Collection $ids) use ($legacyTotals, $dailyTotals) {
            $idsArray = $ids->toArray();

            return (float) ($legacyTotals->only($idsArray)->sum() + $dailyTotals->only($idsArray)->sum());
        });

        $uptTargetIds = $upts->pluck('id');
        $uptTargetsData = UptComparison::query()
            ->with('taxType')
            ->whereIn('upt_id', $uptTargetIds)
            ->where('year', $year)
            ->get()
            ->groupBy('upt_id')
            ->map(function (Collection $rows) {
                $typeIds = $rows->pluck('tax_type_id')->toArray();

                return (float) $rows->filter(function ($row) use ($typeIds) {
                    // Only include if its parent is NOT in the same set of comparisons for this UPT
                    return ! in_array($row->taxType->parent_id, $typeIds);
                })->sum('target_amount');
            });

        $uptTargets = $uptTargetIds->mapWithKeys(
            fn (string $id) => [$id => (float) ($uptTargetsData->get($id) ?? 0)]
        );

        $totalTarget = (float) TaxTarget::query()
            ->where('year', $year)
            ->whereHas('taxType', fn ($q) => $q->whereNull('parent_id'))
            ->sum('target_amount');

        $totalUptTarget = (float) $uptTargets->sum();
        $totalRealization = (float) $uptTotals->sum();

        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return [
            'upts' => $upts,
            'uptTotals' => $uptTotals,
            'uptTargets' => $uptTargets,
            'totalTarget' => $totalTarget,
            'totalUptTarget' => $totalUptTarget,
            'totalRealization' => $totalRealization,
            'availableYears' => $availableYears,
            'year' => $year,
        ];
    }
}
