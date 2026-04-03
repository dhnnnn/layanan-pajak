<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Actions\Simpadu\BuildTaxPayerFilterAction;
use App\Actions\Simpadu\GetTaxPayerMatrixAction;
use App\Models\OfficerTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxPayerMonitoringController extends Controller
{
    public function index(Request $request, BuildTaxPayerFilterAction $buildFilter): View
    {
        $data = $buildFilter->execute($request, app(GetTaxPayerMatrixAction::class));
        if ($request->ajax()) {
            return view('admin.monitoring._table', $data)->render();
        }
        return view('admin.monitoring.index', $data);
    }

    /**
     * Field officer version — uses field-officer layout with same data but filtered to assigned districts.
     */
    public function fieldOfficerIndex(Request $request, BuildTaxPayerFilterAction $buildFilter): View
    {
        $data = $buildFilter->execute($request, app(GetTaxPayerMatrixAction::class));
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
