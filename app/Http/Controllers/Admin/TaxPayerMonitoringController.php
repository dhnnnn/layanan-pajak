<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Actions\Simpadu\GetTaxPayerMatrixAction;
use App\Models\District;
use App\Models\OfficerTask;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxPayerMonitoringController extends Controller
{
    public function index(Request $request, GetTaxPayerMatrixAction $getMatrix)
    {
        $data = $this->buildData($request, $getMatrix);
        if ($request->ajax()) {
            return view('admin.monitoring._table', $data)->render();
        }
        return view('admin.monitoring.index', $data);
    }

    private function buildData(Request $request, GetTaxPayerMatrixAction $getMatrix): array
    {
        $year = $request->integer('year', (int) date('Y'));
        $monthFrom = $request->integer('month_from', 1);
        $monthTo = $request->integer('month_to', (int) date('n'));
        $search = $request->string('search')->trim();
        $selectedDistrict = $request->string('district');
        $statusFilter = $request->string('status_filter', '1')->toString();
        $selectedAyat = $request->string('ayat')->toString();

        $districtCodes = null;
        if (auth()->user()->isKepalaUpt()) {
            $uptDistricts = auth()->user()->upt->districts->pluck('simpadu_code')->toArray();
            if ($selectedDistrict->isNotEmpty() && in_array($selectedDistrict->toString(), $uptDistricts)) {
                $districtCodes = [$selectedDistrict->toString()];
            } else {
                $districtCodes = $uptDistricts;
            }
        } elseif (auth()->user()->hasRole('pegawai')) {
            $assignedCodes = auth()->user()->accessibleDistricts()->pluck('simpadu_code')->filter()->toArray();
            if ($selectedDistrict->isNotEmpty() && in_array($selectedDistrict->toString(), $assignedCodes)) {
                $districtCodes = [$selectedDistrict->toString()];
            } else {
                $districtCodes = $assignedCodes;
            }
        } elseif ($selectedDistrict->isNotEmpty()) {
            $districtCode = $selectedDistrict->toString();
            if (is_numeric($districtCode) && strlen($districtCode) < 3) {
                $districtCode = str_pad($districtCode, 3, '0', STR_PAD_LEFT);
            }
            $districtCodes = [$districtCode];
        }

        $taxPayers = $getMatrix($year, $monthFrom, $monthTo, (string) $search, $districtCodes, $statusFilter, $selectedAyat ?: null);

        $officers = User::orderBy('name')->get();

        $districtsQuery = District::orderBy('name');
        if (auth()->user()->isKepalaUpt()) {
            $districtsQuery->whereIn('id', auth()->user()->upt->districts->pluck('id'));
        } elseif (auth()->user()->hasRole('pegawai')) {
            $districtsQuery->whereIn('id', auth()->user()->accessibleDistricts()->pluck('id'));
        }
        $districts = $districtsQuery->get();

        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->whereNotNull('simpadu_code')
            ->orderBy('name')
            ->get(['id', 'name', 'simpadu_code']);

        return [
            'taxPayers' => $taxPayers,
            'officers' => $officers,
            'districts' => $districts,
            'taxTypes' => $taxTypes,
            'selectedYear' => $year,
            'selectedMonthFrom' => $monthFrom,
            'selectedMonthTo' => $monthTo,
            'selectedDistrict' => (string) $selectedDistrict,
            'selectedAyat' => $selectedAyat,
            'statusFilter' => $statusFilter,
            'availableYears' => range(date('Y'), date('Y') - 5),
        ];
    }

    /**
     * Field officer version — uses field-officer layout with same data but filtered to assigned districts.
     */
    public function fieldOfficerIndex(Request $request, GetTaxPayerMatrixAction $getMatrix): View
    {
        $data = $this->buildData($request, $getMatrix);
        if ($request->ajax()) {
            return view('admin.monitoring._table', $data)->render();
        }
        return view('field-officer.tax-payers', $data);
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
