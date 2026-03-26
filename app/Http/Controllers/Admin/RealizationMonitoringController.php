<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Monitoring\ListUptMonitoringAction;
use App\Actions\Monitoring\ShowEmployeeMonitoringAction;
use App\Actions\Monitoring\ShowUptMonitoringAction;
use App\Exports\UptRealizationExport;
use App\Http\Controllers\Controller;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Http\Request;
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
