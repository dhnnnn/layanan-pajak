<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UptRealizationExport;
use App\Http\Controllers\Controller;
use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\TaxTarget;
use App\Models\Upt;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RealizationMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $year = $request->integer('year', (int) date('Y'));

        $upts = Upt::query()
            ->withCount('users')
            ->with(['users' => function ($q): void {
                $q->role('pegawai')->with('districts');
            }])
            ->orderBy('code')
            ->get();

        // Total realization per UPT for the year
        $uptTotals = [];
        foreach ($upts as $upt) {
            $userIds = $upt->users->pluck('id');
            $total = TaxRealization::query()
                ->whereIn('user_id', $userIds)
                ->where('year', $year)
                ->selectRaw('SUM(january+february+march+april+may+june+july+august+september+october+november+december) as total')
                ->value('total') ?? 0;
            $uptTotals[$upt->id] = (float) $total;
        }

        $totalTarget = (float) TaxTarget::query()->where('year', $year)->sum('target_amount');

        $availableYears = TaxTarget::query()
            ->select('year')->distinct()->orderByDesc('year')->pluck('year');

        return view('admin.realization-monitoring.index', compact('upts', 'uptTotals', 'totalTarget', 'year', 'availableYears'));
    }

    public function show(Request $request, Upt $upt): View
    {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));

        $upt->load(['users' => function ($q): void {
            $q->role('pegawai')->with('districts');
        }]);

        $totalTarget = (float) TaxTarget::query()->where('year', $year)->sum('target_amount');

        // Per-employee realization totals for the year
        $employeeData = [];
        foreach ($upt->users as $employee) {
            $yearlyTotal = (float) TaxRealization::query()
                ->where('user_id', $employee->id)
                ->where('year', $year)
                ->selectRaw('SUM(january+february+march+april+may+june+july+august+september+october+november+december) as total')
                ->value('total') ?? 0;

            // Daily entries this month
            $monthlyEntries = TaxRealizationDailyEntry::query()
                ->where('user_id', $employee->id)
                ->whereYear('entry_date', $year)
                ->whereMonth('entry_date', $month)
                ->with(['taxType', 'district'])
                ->orderByDesc('entry_date')
                ->get();

            $monthlyTotal = $monthlyEntries->sum('amount');

            $employeeData[] = [
                'employee' => $employee,
                'yearly_total' => $yearlyTotal,
                'monthly_total' => (float) $monthlyTotal,
                'monthly_entries' => $monthlyEntries,
                'progress' => $totalTarget > 0 ? ($yearlyTotal / $totalTarget) * 100 : 0,
            ];
        }

        $uptYearlyTotal = collect($employeeData)->sum('yearly_total');

        $availableYears = TaxTarget::query()
            ->select('year')->distinct()->orderByDesc('year')->pluck('year');

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('admin.realization-monitoring.show', compact(
            'upt', 'year', 'month', 'months', 'employeeData',
            'uptYearlyTotal', 'totalTarget', 'availableYears'
        ));
    }

    public function export(Request $request, Upt $upt): BinaryFileResponse
    {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthName = strtolower(str_replace(' ', '-', $months[$month]));
        $filename = "realisasi-{$upt->code}-{$monthName}-{$year}.xlsx";

        return Excel::download(new UptRealizationExport($upt->id, $year, $month), $filename);
    }
}
