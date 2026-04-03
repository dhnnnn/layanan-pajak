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
use Illuminate\Support\Facades\DB;
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
        // Kepala UPT hanya boleh akses UPT-nya sendiri
        $user = auth()->user();
        if ($user->hasRole('kepala_upt') && $user->upt_id !== $upt->id) {
            abort(403, 'Anda tidak memiliki akses ke UPT ini.');
        }

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
        // Validasi: employee harus benar-benar berada di UPT yang ada di URL
        // Ini berlaku untuk semua role — mencegah URL manipulation
        if ($employee->upt_id !== $upt->id) {
            abort(404, 'Petugas tidak ditemukan di UPT ini.');
        }

        // Kepala UPT hanya boleh akses UPT-nya sendiri
        $user = auth()->user();
        if ($user->hasRole('kepala_upt') && $user->upt_id !== $upt->id) {
            abort(403, 'Anda tidak memiliki akses ke UPT ini.');
        }

        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'tunggakan');
        $sortDir = $request->query('sort_dir', 'desc');
        $taxTypeId = $request->query('tax_type_id');
        $statusFilter = $request->query('status_filter', '1');
        $districtId = $request->query('district_id');

        $result = $showEmployeeMonitoring(
            $upt, 
            $employee, 
            $year, 
            $month, 
            $search, 
            $sortBy, 
            $sortDir, 
            $taxTypeId, 
            $statusFilter,
            $districtId
        );

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
     * Return monthly tunggakan breakdown for a specific WP (for accordion).
     */
    public function wpTunggakan(Request $request, Upt $upt, User $employee): \Illuminate\Http\JsonResponse
    {
        $year  = $request->integer('year', (int) date('Y'));
        $npwpd = $request->query('npwpd');
        $nop   = $request->query('nop');

        $months = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('npwpd', $npwpd)
            ->where('nop', $nop)
            ->where('month', '>', 0)
            ->orderBy('month')
            ->get(['month', 'total_ketetapan', 'total_bayar', 'total_tunggakan']);

        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $result = $months->map(fn($r) => [
            'bulan'            => $bulanIndo[(int) $r->month] ?? $r->month,
            'total_ketetapan'  => (float) $r->total_ketetapan,
            'total_bayar'      => (float) $r->total_bayar,
            'total_tunggakan'  => (float) max($r->total_tunggakan, 0),
        ])->filter(fn($r) => $r['total_ketetapan'] > 0);

        return response()->json($result->values());
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
