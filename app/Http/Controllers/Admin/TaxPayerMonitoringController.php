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
    public function index(Request $request, GetTaxPayerMatrixAction $getMatrix): View
    {
        $year = $request->integer('year', (int) date('Y'));
        $monthFrom = $request->integer('month_from', 1);
        $monthTo = $request->integer('month_to', (int) date('n'));
        $search = $request->string('search');

        $districtCodes = null;
        if (auth()->user()->isKepalaUpt()) {
            $districtCodes = auth()->user()->upt->districts->pluck('simpadu_code')->toArray();
        }

        $taxPayers = $getMatrix($year, $monthFrom, $monthTo, (string) $search, $districtCodes);
        
        // Get existing tasks to show status
        $existingTasks = OfficerTask::whereIn('tax_payer_id', $taxPayers->pluck('npwpd'))
            ->get()
            ->groupBy('tax_payer_id');

        $officers = User::orderBy('name')->get(); // Adjust filter if there's a specific role

        return view('admin.monitoring.index', [
            'taxPayers' => $taxPayers,
            'officers' => $officers,
            'selectedYear' => $year,
            'selectedMonthFrom' => $monthFrom,
            'selectedMonthTo' => $monthTo,
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
