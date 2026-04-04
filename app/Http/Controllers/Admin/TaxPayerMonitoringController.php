<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Simpadu\BuildTaxPayerFilterAction;
use App\Actions\Simpadu\GetTaxPayerMatrixAction;
use App\Actions\Simpadu\GetTaxPayerMatrixAllAction;
use App\Exports\TaxPayerMonitoringExport;
use App\Http\Controllers\Controller;
use App\Models\OfficerTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
