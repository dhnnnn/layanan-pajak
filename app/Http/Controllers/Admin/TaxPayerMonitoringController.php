<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Simpadu\BuildTaxPayerFilterAction;
use App\Actions\Simpadu\GetTaxPayerMatrixAction;
use App\Actions\Simpadu\GetTaxPayerMatrixAllAction;
use App\Actions\Simpadu\GetWpDetailAction;
use App\Exports\TaxPayerMonitoringExport;
use App\Exports\WpDetailExport;
use App\Http\Controllers\Controller;
use App\Models\OfficerTask;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaxPayerMonitoringController extends Controller
{
    public function index(Request $request, BuildTaxPayerFilterAction $buildFilter): View|Response
    {
        $data = $buildFilter->execute($request, app(GetTaxPayerMatrixAction::class));
        if ($request->ajax()) {
            return response(view('admin.monitoring._table', $data)->render());
        }

        return view('admin.monitoring.index', $data);
    }

    /**
     * Field officer version — uses field-officer layout with same data but filtered to assigned districts.
     */
    public function fieldOfficerIndex(Request $request, BuildTaxPayerFilterAction $buildFilter): View|Response
    {
        $data = $buildFilter->execute($request, app(GetTaxPayerMatrixAction::class));
        if ($request->ajax()) {
            return response(view('admin.monitoring._table', $data)->render());
        }

        return view('field-officer.tax-payers', $data);
    }

    public function exportExcel(Request $request, GetTaxPayerMatrixAllAction $getAll): BinaryFileResponse
    {
        // Increase limits for large dataset export
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $year = $request->integer('year', (int) date('Y'));
        $monthFrom = $request->integer('month_from', 1);
        $monthTo = $request->integer('month_to', (int) date('n'));
        $search = $request->string('search')->trim()->toString();
        $statusFilter = $request->string('status_filter', '1')->toString();
        $ayat = $request->string('ayat')->toString() ?: null;

        $selectedDistrict = $request->string('district')->toString();
        $districtCodes = $this->resolveDistrictCodesForExport($selectedDistrict);

        $taxPayers = $getAll($year, $monthFrom, $monthTo, $search ?: null, $districtCodes, $statusFilter, $ayat);

        $filename = "pemantau-wp-{$year}.xlsx";

        return Excel::download(new TaxPayerMonitoringExport($taxPayers, $year, $monthFrom, $monthTo), $filename);
    }

    private function resolveDistrictCodesForExport(string $selectedDistrict): ?array
    {
        $user = auth()->user();

        if ($user->isKepalaUpt()) {
            $uptCodes = $user->upt->districts->pluck('simpadu_code')->toArray();

            return ($selectedDistrict !== '' && in_array($selectedDistrict, $uptCodes))
                ? [$selectedDistrict]
                : $uptCodes;
        }

        if ($user->hasRole('pegawai')) {
            $assignedCodes = $user->accessibleDistricts()->pluck('simpadu_code')->filter()->toArray();

            return ($selectedDistrict !== '' && in_array($selectedDistrict, $assignedCodes))
                ? [$selectedDistrict]
                : $assignedCodes;
        }

        if ($selectedDistrict !== '') {
            $code = (is_numeric($selectedDistrict) && strlen($selectedDistrict) < 3)
                ? str_pad($selectedDistrict, 3, '0', STR_PAD_LEFT)
                : $selectedDistrict;

            return [$code];
        }

        return null;
    }

    public function wpDetailExportExcel(Request $request, string $npwpd, string $nop): BinaryFileResponse
    {
        $year = $request->integer('year', (int) date('Y'));
        $monthFrom = max(1, min(12, $request->integer('month_from', 1)));
        $monthTo = max($monthFrom, min(12, $request->integer('month_to', (int) date('n'))));
        $multiYear = max(1, min(5, $request->integer('multi_year', 1)));

        $filename = "detail-wp-{$npwpd}-{$year}.xlsx";

        return Excel::download(new WpDetailExport($npwpd, $nop, $year, $monthFrom, $monthTo, $multiYear), $filename);
    }

    public function wpDetailExportPdf(Request $request, string $npwpd, string $nop, GetWpDetailAction $getDetail): Response
    {
        $year = $request->integer('year', (int) date('Y'));
        $monthFrom = max(1, min(12, $request->integer('month_from', 1)));
        $monthTo = max($monthFrom, min(12, $request->integer('month_to', (int) date('n'))));
        $multiYear = max(1, min(5, $request->integer('multi_year', 1)));

        $data = $getDetail($npwpd, $nop, $year, $monthFrom, $monthTo, $multiYear);

        $years = $data['years'];
        $yearLabel = count($years) > 1 ? min($years).' – '.max($years) : (string) $years[0];

        $allRows = collect($data['tableData'])->flatten(1);
        $totalSptpdAll = $allRows->sum('total_ketetapan');
        $totalBayarAll = $allRows->sum('total_bayar');
        $totalTunggakanAll = $allRows->sum('total_tunggakan');
        $pctAll = $totalSptpdAll > 0 ? ($totalBayarAll / $totalSptpdAll) * 100 : 0;

        $pdf = Pdf::loadView('admin.monitoring.wp-detail-pdf', array_merge($data, [
            'npwpd' => $npwpd,
            'nop' => $nop,
            'selectedYear' => $year,
            'selectedMonthFrom' => $monthFrom,
            'selectedMonthTo' => $monthTo,
            'yearLabel' => $yearLabel,
            'totalSptpdAll' => $totalSptpdAll,
            'totalBayarAll' => $totalBayarAll,
            'totalTunggakanAll' => $totalTunggakanAll,
            'pctAll' => $pctAll,
        ]))->setPaper('a4', 'portrait');

        $wpName = $data['wpInfo']?->nm_wp ?? $npwpd;
        $safeWpName = preg_replace('/[^a-zA-Z0-9\-_]/', '-', strtolower($wpName));
        $safeWpName = preg_replace('/-+/', '-', trim($safeWpName, '-'));
        $filename = "detail-wp-{$safeWpName}-{$year}.pdf";

        return $pdf->download($filename);
    }

    public function wpDetail(Request $request, string $npwpd, string $nop, GetWpDetailAction $getDetail): View
    {
        $year = $request->integer('year', (int) date('Y'));
        $monthFrom = max(1, min(12, $request->integer('month_from', 1)));
        $monthTo = max($monthFrom, min(12, $request->integer('month_to', (int) date('n'))));
        $multiYear = max(1, min(5, $request->integer('multi_year', 1)));

        // Validasi akses kecamatan untuk pegawai
        $user = auth()->user();
        if ($user->hasRole('pegawai')) {
            $assignedCodes = $user->accessibleDistricts()->pluck('simpadu_code')->filter()->toArray();
            $wpKecamatan = DB::table('simpadu_tax_payers')
                ->where('npwpd', $npwpd)->where('nop', $nop)->where('month', 0)
                ->value('kd_kecamatan');

            if ($wpKecamatan && ! in_array($wpKecamatan, $assignedCodes)) {
                abort(403, 'WP ini tidak berada di wilayah Anda.');
            }
        }

        $data = $getDetail($npwpd, $nop, $year, $monthFrom, $monthTo, $multiYear);

        $isFieldOfficer = $user->hasRole('pegawai');
        $backRoute = $isFieldOfficer
            ? route('field-officer.monitoring.tax-payers', array_filter(['year' => $year, 'month_from' => $monthFrom, 'month_to' => $monthTo]))
            : route('admin.monitoring.index', array_filter(['year' => $year, 'month_from' => $monthFrom, 'month_to' => $monthTo]));

        return view('admin.monitoring.wp-detail', array_merge($data, [
            'selectedYear' => $year,
            'selectedMonthFrom' => $monthFrom,
            'selectedMonthTo' => $monthTo,
            'multiYear' => $multiYear,
            'npwpd' => $npwpd,
            'nop' => $nop,
            'backRoute' => $backRoute,
            'isFieldOfficer' => $isFieldOfficer,
        ]));
    }

    public function wpChart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'npwpd' => 'required|string|max:50',
            'nop' => 'required|string|max:50',
            'year' => 'required|integer|min:2000|max:2099',
            'month_from' => 'required|integer|min:1|max:12',
            'month_to' => 'required|integer|min:1|max:12',
            'multi_year' => 'nullable|boolean',
        ]);

        $npwpd = $validated['npwpd'];
        $nop = $validated['nop'];
        $year = (int) $validated['year'];
        $monthFrom = (int) $validated['month_from'];
        $monthTo = (int) $validated['month_to'];
        $multiYear = filter_var($validated['multi_year'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $years = $multiYear ? [$year, $year - 1, $year - 2] : [$year];

        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $labels = array_map(fn ($m) => $bulanIndo[$m], range($monthFrom, $monthTo));

        $datasets = [];
        $colors = [
            ['border' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.08)'],
            ['border' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.08)'],
            ['border' => '#10b981', 'bg' => 'rgba(16,185,129,0.08)'],
        ];

        foreach ($years as $i => $y) {
            $rows = DB::table('simpadu_tax_payers')
                ->where('year', $y)
                ->where('npwpd', $npwpd)
                ->where('nop', $nop)
                ->whereBetween('month', [$monthFrom, $monthTo])
                ->orderBy('month')
                ->get(['month', 'total_ketetapan', 'total_bayar'])
                ->keyBy('month');

            $sptpd = [];
            $bayar = [];
            for ($m = $monthFrom; $m <= $monthTo; $m++) {
                $row = $rows->get($m);
                $sptpd[] = $row ? (float) $row->total_ketetapan : 0;
                $bayar[] = $row ? (float) $row->total_bayar : 0;
            }

            $c = $colors[$i] ?? $colors[0];
            $datasets[] = [
                'label' => "SPTPD {$y}",
                'data' => $sptpd,
                'borderColor' => $c['border'],
                'backgroundColor' => $c['bg'],
                'tension' => 0.4,
                'fill' => true,
                'pointRadius' => 4,
                'borderWidth' => 2,
            ];
            $datasets[] = [
                'label' => "Bayar {$y}",
                'data' => $bayar,
                'borderColor' => $c['border'],
                'backgroundColor' => 'transparent',
                'borderDash' => [5, 4],
                'tension' => 0.4,
                'fill' => false,
                'pointRadius' => 3,
                'borderWidth' => 1.5,
            ];
        }

        return response()->json(['labels' => $labels, 'datasets' => $datasets]);
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tax_payer_id' => 'required|string',
            'tax_payer_name' => 'required|string',
            'tax_payer_address' => 'nullable|string',
            'officer_id' => 'required|exists:users,id',
            'district_id' => 'required|exists:districts,id',
            'amount_sptpd' => 'required|numeric',
            'amount_paid' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $task = OfficerTask::create([
            ...$validated,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        return back()->with('success', "Petugas berhasil ditugaskan untuk WP {$task->tax_payer_name}");
    }
}
