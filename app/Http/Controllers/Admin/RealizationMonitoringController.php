<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Monitoring\GetDistrictForecastAction;
use App\Actions\Monitoring\ListUptMonitoringAction;
use App\Actions\Monitoring\ShowEmployeeMonitoringAction;
use App\Actions\Monitoring\ShowUptMonitoringAction;
use App\Exports\EmployeeMonitoringExport;
use App\Exports\RealizationMonitoringExport;
use App\Exports\UptRealizationExport;
use App\Http\Controllers\Controller;
use App\Models\Upt;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        if ($user->hasRole('kepala_upt') && $user->upt()?->id !== $upt->id) {
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
        // Gunakan pivot upt_users karena kolom upt_id di users bisa kosong
        if (! $upt->users()->where('users.id', $employee->id)->exists()) {
            abort(404, 'Petugas tidak ditemukan di UPT ini.');
        }

        // Kepala UPT hanya boleh akses UPT-nya sendiri
        $user = auth()->user();
        if ($user->hasRole('kepala_upt') && $user->upt()?->id !== $upt->id) {
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
        $filename = "realisasi-{$upt->code}-{$year}.xlsx";

        return Excel::download(new UptRealizationExport($upt->id, $year, 0), $filename);
    }

    /**
     * Export UPT WP tunggakan to PDF (untuk kepala UPT).
     */
    public function exportUptPdf(Request $request, Upt $upt): Response
    {
        $year = $request->integer('year', (int) date('Y'));
        $upt->load('districts');
        $districtCodes = $upt->districts->pluck('simpadu_code')->filter()->toArray();

        $summaryRaw = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('month', 0)->where('status', '1')
            ->whereIn('kd_kecamatan', $districtCodes)
            ->selectRaw('SUM(total_ketetapan) as total_sptpd, SUM(total_bayar) as total_bayar, SUM(CASE WHEN total_tunggakan > 0 THEN total_tunggakan ELSE 0 END) as total_tunggakan')
            ->first();

        $summary = [
            'total_sptpd' => (float) ($summaryRaw->total_sptpd ?? 0),
            'total_bayar' => (float) ($summaryRaw->total_bayar ?? 0),
            'total_tunggakan' => (float) ($summaryRaw->total_tunggakan ?? 0),
        ];

        $wpList = DB::table('simpadu_tax_payers as stp')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
            ->where('stp.year', $year)->where('stp.month', 0)->where('stp.status', '1')
            ->whereIn('stp.kd_kecamatan', $districtCodes)
            ->where('stp.total_tunggakan', '>', 0)
            ->selectRaw('stp.npwpd, stp.nop, stp.nm_wp, stp.kd_kecamatan, stp.ayat, tax_types.name as jenis_pajak, SUM(stp.total_ketetapan) as total_ketetapan, SUM(stp.total_bayar) as total_bayar, SUM(stp.total_tunggakan) as total_tunggakan')
            ->groupBy('stp.npwpd', 'stp.nop', 'stp.nm_wp', 'stp.kd_kecamatan', 'stp.ayat', 'tax_types.name')
            ->orderByDesc('total_tunggakan')
            ->get();

        $monthlyData = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('month', '>', 0)->where('status', '1')
            ->whereIn('kd_kecamatan', $districtCodes)
            ->where('total_ketetapan', '>', 0)
            ->get()
            ->groupBy(fn ($r) => $r->npwpd.'|'.$r->nop);

        // Gunakan employee sebagai placeholder untuk nama UPT
        $employee = (object) ['name' => $upt->name, 'districts' => $upt->districts];

        $pdf = Pdf::loadView('admin.realization-monitoring.employee-pdf', compact(
            'upt', 'employee', 'year', 'summary', 'wpList', 'monthlyData'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("monitoring-realisasi-{$upt->code}-{$year}.pdf");
    }

    /**
     * Export employee WP tunggakan to Excel.
     */
    public function exportEmployee(Request $request, Upt $upt, User $employee): BinaryFileResponse
    {
        $year = $request->integer('year', (int) date('Y'));
        $filename = "tunggakan-{$employee->name}-{$year}.xlsx";

        return Excel::download(
            new EmployeeMonitoringExport($upt, $employee, $year),
            $filename
        );
    }

    /**
     * Export employee WP tunggakan to PDF (download).
     */
    public function exportEmployeePdf(Request $request, Upt $upt, User $employee): Response
    {
        if (! $upt->users()->where('users.id', $employee->id)->exists()) {
            abort(404);
        }

        $year = $request->integer('year', (int) date('Y'));
        $employee->load('districts');
        $districtCodes = $employee->districts->pluck('simpadu_code')->filter()->toArray();

        $summaryRaw = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('month', 0)->where('status', '1')
            ->whereIn('kd_kecamatan', $districtCodes)
            ->selectRaw('SUM(total_ketetapan) as total_sptpd, SUM(total_bayar) as total_bayar, SUM(CASE WHEN total_tunggakan > 0 THEN total_tunggakan ELSE 0 END) as total_tunggakan')
            ->first();

        $summary = [
            'total_sptpd' => (float) ($summaryRaw->total_sptpd ?? 0),
            'total_bayar' => (float) ($summaryRaw->total_bayar ?? 0),
            'total_tunggakan' => (float) ($summaryRaw->total_tunggakan ?? 0),
        ];

        $wpList = DB::table('simpadu_tax_payers as stp')
            ->leftJoin('tax_types', 'tax_types.simpadu_code', '=', 'stp.ayat')
            ->where('stp.year', $year)->where('stp.month', 0)->where('stp.status', '1')
            ->whereIn('stp.kd_kecamatan', $districtCodes)
            ->where('stp.total_tunggakan', '>', 0)
            ->selectRaw('stp.npwpd, stp.nop, stp.nm_wp, stp.kd_kecamatan, stp.ayat, tax_types.name as jenis_pajak, SUM(stp.total_ketetapan) as total_ketetapan, SUM(stp.total_bayar) as total_bayar, SUM(stp.total_tunggakan) as total_tunggakan')
            ->groupBy('stp.npwpd', 'stp.nop', 'stp.nm_wp', 'stp.kd_kecamatan', 'stp.ayat', 'tax_types.name')
            ->orderByDesc('total_tunggakan')
            ->get();

        $monthlyData = DB::table('simpadu_tax_payers')
            ->where('year', $year)->where('month', '>', 0)->where('status', '1')
            ->whereIn('kd_kecamatan', $districtCodes)
            ->where('total_ketetapan', '>', 0)
            ->get()
            ->groupBy(fn ($r) => $r->npwpd.'|'.$r->nop);

        $pdf = Pdf::loadView('admin.realization-monitoring.employee-pdf', compact(
            'upt', 'employee', 'year', 'summary', 'wpList', 'monthlyData'
        ))->setPaper('a4', 'portrait');

        $filename = 'monitoring-realisasi-'.str_replace(' ', '-', strtolower($employee->name))."-{$year}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Return monthly tunggakan breakdown for a specific WP (for accordion).
     */
    public function wpTunggakan(Request $request, Upt $upt, User $employee): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2099',
            'npwpd' => 'required|string|max:50',
            'nop' => 'required|string|max:50',
        ]);

        $year = $validated['year'] ?? (int) date('Y');
        $npwpd = $validated['npwpd'];
        $nop = $validated['nop'];

        $months = DB::table('simpadu_tax_payers')
            ->where('year', $year)
            ->where('npwpd', $npwpd)
            ->where('nop', $nop)
            ->where('month', '>', 0)
            ->orderBy('month')
            ->get(['month', 'total_ketetapan', 'total_bayar', 'total_tunggakan']);

        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $result = $months->map(fn ($r) => [
            'bulan' => $bulanIndo[(int) $r->month] ?? $r->month,
            'total_ketetapan' => (float) $r->total_ketetapan,
            'total_bayar' => (float) $r->total_bayar,
            'total_tunggakan' => (float) max($r->total_tunggakan, 0),
        ])->filter(fn ($r) => $r['total_ketetapan'] > 0);

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

    /**
     * Endpoint AJAX: forecast realisasi per kecamatan untuk satu UPT.
     * district_id=all → aggregate semua kecamatan di UPT.
     */
    public function districtForecast(Request $request, Upt $upt, GetDistrictForecastAction $getForecast): JsonResponse
    {
        $districtId = $request->query('district_id');
        $noAyat = $request->query('tax_type_id'); // Using no_ayat as the filter

        Log::info('District forecast request', [
            'upt' => $upt->name,
            'district_id' => $districtId,
            'tax_type_id' => $noAyat,
        ]);

        if ($noAyat === 'all') {
            $noAyat = null;
        }

        if ($districtId === 'all') {
            // Aggregate semua kecamatan di UPT
            $districts = $upt->districts()->get();
            if ($districts->isEmpty()) {
                Log::warning('No districts found for UPT', ['upt' => $upt->name]);

                return response()->json(['error' => 'Tidak ada kecamatan di UPT ini.'], 404);
            }

            $codes = $districts->pluck('simpadu_code')->filter()->toArray();

            Log::info('Fetching data for districts', ['codes' => $codes]);

            $rows = DB::table('simpadu_tax_payers')
                ->whereIn('kd_kecamatan', $codes)
                ->when($noAyat, fn ($q) => $q->where('ayat', $noAyat))
                ->where('status', '1')
                ->where('month', '>', 0)
                ->selectRaw('year, month, SUM(total_bayar) as total_bayar, SUM(total_ketetapan) as total_ketetapan')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            Log::info('Data rows fetched', ['count' => $rows->count()]);

            if ($rows->count() < 2) {
                return response()->json([
                    'error' => 'Data historis tidak cukup. Minimal 2 bulan data berurutan diperlukan untuk prediksi.',
                    'data_count' => $rows->count(),
                ], 503);
            }

            // Potong data di gap pertama agar ARIMA tidak terpengaruh data acak
            $validRows = collect();
            $prevYear = null;
            $prevMonth = null;

            foreach ($rows as $r) {
                if ((float) $r->total_bayar <= 0) {
                    if ($prevYear !== null) {
                        break;
                    }

                    continue;
                }

                if ($prevYear !== null) {
                    $expectedYear = $prevMonth === 12 ? $prevYear + 1 : $prevYear;
                    $expectedMonth = $prevMonth === 12 ? 1 : $prevMonth + 1;
                    if ((int) $r->year !== $expectedYear || (int) $r->month !== $expectedMonth) {
                        break;
                    }
                }

                $validRows->push($r);
                $prevYear = (int) $r->year;
                $prevMonth = (int) $r->month;
            }

            // Potong bulan-bulan di akhir yang nilainya < 50% rata-rata historis
            if ($validRows->count() >= 2) {
                $avg = $validRows->avg(fn ($r) => (float) $r->total_bayar);
                while ($validRows->count() >= 2 && (float) $validRows->last()->total_bayar < $avg * 0.50) {
                    $validRows->pop();
                }
            }

            $historisData = $validRows->map(fn ($r) => [
                'periode' => sprintf('%d-%02d', $r->year, $r->month),
                'nilai' => (float) $r->total_bayar,
            ])->values()->toArray();

            $ketetapanData = $validRows->filter(fn ($r) => (float) $r->total_ketetapan > 0)
                ->map(fn ($r) => [
                    'periode' => sprintf('%d-%02d', $r->year, $r->month),
                    'nilai' => (float) $r->total_ketetapan,
                ])->values()->toArray();

            if (count($historisData) < 2) {
                return response()->json(['error' => 'Data realisasi tidak tersedia.'], 503);
            }

            try {
                $response = Http::timeout(config('forecasting.timeout', 60))
                    ->post(config('forecasting.url').'/forecast/from-data', [
                        'jenis_pajak' => 'realisasi_all'.($noAyat ? "_{$noAyat}" : ''),
                        'data' => $historisData,
                        'horizon' => 12,
                    ]);

                if (! $response->successful()) {
                    return response()->json(['error' => 'Forecasting service error.'], 503);
                }

                return response()->json(array_merge($response->json(), [
                    'kecamatan' => 'Semua Kecamatan',
                    'total_ketetapan' => $ketetapanData,
                ]));
            } catch (\Exception $e) {
                return response()->json(['error' => 'Forecasting service tidak dapat dijangkau.'], 503);
            }
        }

        $district = $upt->districts()->find($districtId);

        if (! $district) {
            return response()->json(['error' => 'Kecamatan tidak ditemukan.'], 404);
        }

        $result = $getForecast($district, $noAyat);

        if ($result === null) {
            return response()->json(['error' => 'Data tidak tersedia atau forecasting service tidak dapat dijangkau.'], 503);
        }

        return response()->json($result);
    }
}
