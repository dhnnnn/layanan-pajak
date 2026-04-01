<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Actions\Simpadu\GetSimpaduTaxPayersAction;
use App\Models\District;
use App\Models\OfficerTask;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxPayerMonitoringController extends Controller
{
    public function index(Request $request, GetSimpaduTaxPayersAction $getTaxPayers): View
    {
        $year = $request->integer('year', date('Y'));
        $districtId = $request->string('district_id');
        $search = $request->string('search');

        $district = $districtId ? District::find($districtId) : null;
        $districtCode = $district ? $district->simpadu_code : null;

        $taxPayers = $getTaxPayers($year, $districtCode, $search);
        
        // Get existing tasks to show status
        $existingTasks = OfficerTask::whereIn('tax_payer_id', $taxPayers->pluck('npwpd'))
            ->get()
            ->groupBy('tax_payer_id');

        $districts = District::whereNotNull('simpadu_code')->orderBy('name')->get();
        $officers = User::orderBy('name')->get(); // Adjust filter if there's a specific role

        return view('admin.monitoring.index', [
            'taxPayers' => $taxPayers,
            'districts' => $districts,
            'officers' => $officers,
            'selectedYear' => $year,
            'selectedDistrict' => $districtId,
            'existingTasks' => $existingTasks,
        ]);
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
