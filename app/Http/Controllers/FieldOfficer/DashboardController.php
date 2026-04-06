<?php

namespace App\Http\Controllers\FieldOfficer;

use App\Actions\FieldOfficer\GetFieldOfficerDashboardDataAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FieldOfficer\DashboardRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the main field officer dashboard.
     */
    public function index(DashboardRequest $request, GetFieldOfficerDashboardDataAction $action): View
    {
        $year = $request->integer('year', (int) date('Y'));
        $complianceMonth = max(1, min(12, $request->integer('compliance_month', (int) date('n'))));

        return view('field-officer.dashboard', $action->execute(
            $request->user(),
            $year,
            $complianceMonth
        ));
    }
}
