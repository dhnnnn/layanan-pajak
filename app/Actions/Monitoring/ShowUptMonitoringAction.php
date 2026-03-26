<?php

namespace App\Actions\Monitoring;

use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxTarget;
use App\Models\Upt;
use App\Models\UptComparison;
use Illuminate\Support\Collection;

class ShowUptMonitoringAction
{
    /**
     * @return array{
     *     upt: Upt,
     *     uptTarget: float,
     *     employeeData: Collection,
     *     uptYearlyTotal: float,
     *     availableYears: Collection,
     *     months: array<int, string>,
     *     year: int,
     *     month: int,
     * }
     */
    public function __invoke(Upt $upt, int $year, int $month): array
    {
        $upt->load(['users' => function ($q): void {
            $q->role('pegawai');
        }]);

        $uptTarget = (float) UptComparison::query()
            ->where('upt_id', $upt->id)
            ->where('year', $year)
            ->sum('target_amount');

        $districtIds = $upt->districts->pluck('id');

        // 1. Calculate UPT Total (Aggregated by District to avoid double-counting)
        $legacyByDistrict = TaxRealization::query()
            ->whereIn('district_id', $districtIds)
            ->where('year', $year)
            ->get()
            ->groupBy('district_id')
            ->map(fn (Collection $recs): float => (float) $recs->sum(
                fn ($r) => $r->january + $r->february + $r->march + $r->april
                + $r->may + $r->june + $r->july + $r->august
                + $r->september + $r->october + $r->november + $r->december
            ));

        $dailyByDistrict = TaxRealizationDailyEntry::query()
            ->whereIn('district_id', $districtIds)
            ->whereYear('entry_date', $year)
            ->selectRaw('district_id, SUM(amount) as total')
            ->groupBy('district_id')
            ->pluck('total', 'district_id')
            ->map(fn ($t) => (float) $t);

        $uptYearlyTotal = $legacyByDistrict->sum() + $dailyByDistrict->sum();

        // 2. Calculate Employee Contributions (Aggregated by User)
        $employeeIds = $upt->users->pluck('id');

        $legacyByUser = TaxRealization::query()
            ->whereIn('user_id', $employeeIds)
            ->where('year', $year)
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $recs): float => (float) $recs->sum(
                fn ($r) => $r->january + $r->february + $r->march + $r->april
                + $r->may + $r->june + $r->july + $r->august
                + $r->september + $r->october + $r->november + $r->december
            ));

        $dailyByUser = TaxRealizationDailyEntry::query()
            ->whereIn('user_id', $employeeIds)
            ->whereYear('entry_date', $year)
            ->selectRaw('user_id, SUM(amount) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->map(fn ($t) => (float) $t);

        $employeeData = $upt->users->map(function ($employee) use ($legacyByUser, $dailyByUser, $uptTarget): array {
            $contribution = (float) (($legacyByUser->get($employee->id) ?? 0) + ($dailyByUser->get($employee->id) ?? 0));

            return [
                'employee' => $employee,
                'yearly_total' => $contribution,
                'progress' => $uptTarget > 0 ? ($contribution / $uptTarget) * 100 : 0,
            ];
        });

        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return [
            'upt' => $upt,
            'uptTarget' => $uptTarget,
            'employeeData' => $employeeData,
            'uptYearlyTotal' => $uptYearlyTotal,
            'availableYears' => $availableYears,
            'months' => $months,
            'year' => $year,
            'month' => $month,
        ];
    }
}
