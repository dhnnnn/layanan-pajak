<?php

namespace App\Actions\Monitoring;

use App\Models\TaxRealization;
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

        $userIdsByUpt = $upts->mapWithKeys(
            fn (Upt $upt) => [$upt->id => $upt->users->pluck('id')]
        );

        $allRealizationTotals = TaxRealization::query()
            ->whereIn('user_id', $userIdsByUpt->flatten())
            ->where('year', $year)
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $realizations) => $realizations->sum(
                fn ($r) => (float) ($r->january + $r->february + $r->march + $r->april
                    + $r->may + $r->june + $r->july + $r->august
                    + $r->september + $r->october + $r->november + $r->december)
            ));

        $uptTotals = $userIdsByUpt->map(
            fn (Collection $ids) => $allRealizationTotals->only($ids->toArray())->sum()
        );

        $uptTargetIds = $upts->pluck('id');
        $uptTargetsData = UptComparison::query()
            ->whereIn('upt_id', $uptTargetIds)
            ->where('year', $year)
            ->get()
            ->groupBy('upt_id')
            ->map(fn (Collection $rows) => $rows->sum('target_amount'));

        $uptTargets = $uptTargetIds->mapWithKeys(
            fn (string $id) => [$id => (float) ($uptTargetsData->get($id) ?? 0)]
        );

        $totalTarget = (float) TaxTarget::query()->where('year', $year)->sum('target_amount');

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
            'availableYears' => $availableYears,
            'year' => $year,
        ];
    }
}
