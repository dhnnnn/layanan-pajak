<?php

namespace App\Actions\Monitoring;

use App\Models\TaxRealization;
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

        $employeeIds = $upt->users->pluck('id');

        $yearlyTotals = TaxRealization::query()
            ->whereIn('user_id', $employeeIds)
            ->where('year', $year)
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $realizations): float => (float) $realizations->sum(
                fn ($r) => $r->january + $r->february + $r->march + $r->april
                    + $r->may + $r->june + $r->july + $r->august
                    + $r->september + $r->october + $r->november + $r->december
            ));

        $employeeData = $upt->users->map(function ($employee) use ($yearlyTotals, $uptTarget): array {
            $yearlyTotal = $yearlyTotals->get($employee->id, 0.0);

            return [
                'employee' => $employee,
                'yearly_total' => $yearlyTotal,
                'progress' => $uptTarget > 0 ? ($yearlyTotal / $uptTarget) * 100 : 0,
            ];
        });

        $uptYearlyTotal = $employeeData->sum('yearly_total');

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
