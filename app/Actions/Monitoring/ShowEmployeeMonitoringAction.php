<?php

namespace App\Actions\Monitoring;

use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxTarget;
use App\Models\Upt;
use App\Models\UptComparison;
use App\Models\User;
use Illuminate\Support\Collection;

class ShowEmployeeMonitoringAction
{
    /**
     * @return array{
     *     upt: Upt,
     *     employee: User,
     *     uptTarget: float,
     *     yearlyTotal: float,
     *     monthlyEntries: Collection,
     *     monthlyTotal: float,
     *     progress: float,
     *     availableYears: Collection,
     *     months: array<int, string>,
     *     year: int,
     *     month: int,
     * }
     */
    public function __invoke(Upt $upt, User $employee, int $year, int $month): array
    {
        $employee->load('districts');

        $uptTarget = (float) UptComparison::query()
            ->where('upt_id', $upt->id)
            ->where('year', $year)
            ->sum('target_amount');

        $assignedDistrictIds = $employee->districts->pluck('id');

        $legacyTotal = (float) TaxRealization::query()
            ->whereIn('district_id', $assignedDistrictIds)
            ->where('year', $year)
            ->selectRaw('SUM(january+february+march+april+may+june+july+august+september+october+november+december) as total')
            ->value('total') ?? 0;

        $dailyTotal = (float) TaxRealizationDailyEntry::query()
            ->whereIn('district_id', $assignedDistrictIds)
            ->whereYear('entry_date', $year)
            ->sum('amount');

        $yearlyTotal = $legacyTotal + $dailyTotal;

        $monthlyEntries = TaxRealizationDailyEntry::query()
            ->where('user_id', $employee->id)
            ->whereYear('entry_date', $year)
            ->whereMonth('entry_date', $month)
            ->with(['taxType', 'district'])
            ->orderByDesc('entry_date')
            ->get();

        $monthlyTotal = (float) $monthlyEntries->sum('amount');
        $progress = $uptTarget > 0 ? ($yearlyTotal / $uptTarget) * 100 : 0;

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
            'employee' => $employee,
            'uptTarget' => $uptTarget,
            'yearlyTotal' => $yearlyTotal,
            'monthlyEntries' => $monthlyEntries,
            'monthlyTotal' => $monthlyTotal,
            'progress' => $progress,
            'availableYears' => $availableYears,
            'months' => $months,
            'year' => $year,
            'month' => $month,
        ];
    }
}
