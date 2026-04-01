<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Monitoring\ListUptMonitoringAction;
use App\Actions\Monitoring\ShowEmployeeMonitoringAction;
use App\Actions\Monitoring\ShowUptMonitoringAction;
use App\Exports\RealizationMonitoringExport;
use App\Exports\UptRealizationExport;
use App\Http\Controllers\Controller;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RealizationMonitoringController extends Controller
{
    public function index(Request $request, ListUptMonitoringAction $listUptMonitoring): View
    {
        $year = $request->integer('year', (int) date('Y'));

        $result = $listUptMonitoring($year);

        return view('admin.realization-monitoring.index', $result);
    }

    public function show(Request $request, Upt $upt, ShowUptMonitoringAction $showUptMonitoring): View
    {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));

        $result = $showUptMonitoring($upt, $year, $month);

        return view('admin.realization-monitoring.show', $result);
    }

    public function employeeDetail(
        Request $request,
        Upt $upt,
        User $employee,
        ShowEmployeeMonitoringAction $showEmployeeMonitoring,
    ): View {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));

        $result = $showEmployeeMonitoring($upt, $employee, $year, $month);

        return view('admin.realization-monitoring.employee', $result);
    }

    public function export(Request $request, Upt $upt): BinaryFileResponse
    {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));

        $monthName = strtolower(Carbon::createFromDate($year, $month, 1)->translatedFormat('F'));
        $filename = "realisasi-{$upt->code}-{$monthName}-{$year}.xlsx";

        return Excel::download(new UptRealizationExport($upt->id, $year, $month), $filename);
    }

    /**
     * Export all UPT realization data to a matrix-style Excel report.
     */
    public function exportAll(Request $request): BinaryFileResponse
    {
        $year = $request->integer('year', (int) date('Y'));
        // Filename requested: monitoring-ralisasi-upt-tahun.xlsx
        $filename = "monitoring-realisasi-upt-{$year}.xlsx";

        return Excel::download(new RealizationMonitoringExport($year), $filename);
    }
}
