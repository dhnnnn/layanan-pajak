<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Actions\Simpadu\GetTaxPayerMatrixAction;
use App\Models\District;
use App\Models\OfficerTask;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxPayerMonitoringController extends Controller
{
    public function index(Request $request, GetTaxPayerMatrixAction $getMatrix)
    {
        $year = $request->integer('year', (int) date('Y'));
        $monthFrom = $request->integer('month_from', 1);
        $monthTo = $request->integer('month_to', (int) date('n'));
        $search = $request->string('search')->trim();
        $selectedDistrict = $request->string('district');

        // Determine which district codes to filter by
        $districtCodes = null;
        if (auth()->user()->isKepalaUpt()) {
            // Limited to UPT's districts
            $uptDistricts = auth()->user()->upt->districts->pluck('simpadu_code')->toArray();
            
            if ($selectedDistrict->isNotEmpty()) {
                // If a specific district is selected, check if it's within UPT's districts
                if (in_array($selectedDistrict->toString(), $uptDistricts)) {
                    $districtCodes = [$selectedDistrict->toString()];
                } else {
                    // Fallback to all UPT districts if invalid
                    $districtCodes = $uptDistricts;
                }
            } else {
                $districtCodes = $uptDistricts;
            }
        } elseif ($selectedDistrict->isNotEmpty()) {
            // Admin can filter by any district
            $districtCode = $selectedDistrict->toString();
            if (is_numeric($districtCode) && strlen($districtCode) < 3) {
                $districtCode = str_pad($districtCode, 3, '0', STR_PAD_LEFT);
            }
            $districtCodes = [$districtCode];
        }

        $taxPayers = $getMatrix($year, $monthFrom, $monthTo, (string) $search, $districtCodes);
        
        $officers = User::orderBy('name')->get(); 

        // Fetch districts for the filter dropdown
        $districtsQuery = District::orderBy('name');
        if (auth()->user()->isKepalaUpt()) {
            $districtsQuery->whereIn('id', auth()->user()->upt->districts->pluck('id'));
        }
        $districts = $districtsQuery->get();

        $data = [
            'taxPayers' => $taxPayers,
            'officers' => $officers,
            'districts' => $districts,
            'selectedYear' => $year,
            'selectedMonthFrom' => $monthFrom,
            'selectedMonthTo' => $monthTo,
            'selectedDistrict' => (string) $selectedDistrict,
            'availableYears' => range(date('Y'), date('Y') - 5),
        ];

        if ($request->ajax()) {
            return view('admin.monitoring._table', $data)->render();
        }

        return view('admin.monitoring.index', $data);
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
