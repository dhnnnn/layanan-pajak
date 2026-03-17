<?php

namespace App\Http\Controllers\Employee;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Models\TaxTarget;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(
        Request $request,
        GenerateTaxDashboardAction $generateDashboard,
    ): View {
        $user = $request->user();

        $assignedDistricts = $user->districts()->orderBy('name')->get();

        $availableYears = TaxTarget::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $selectedYear = (int) $request->query(
            'year',
            $availableYears->first() ?? date('Y'),
        );

        // Auto-select first district if not specified or invalid
        $selectedDistrictId = $request->filled('district_id')
            ? $request->string('district_id')->toString()
            : null;

        // If no district selected or invalid, auto-select first assigned district
        if (
            $selectedDistrictId === null ||
            ! $assignedDistricts->contains('id', $selectedDistrictId)
        ) {
            $selectedDistrictId = $assignedDistricts->first()?->id;
        }

        $dashboard = $generateDashboard($selectedYear, $selectedDistrictId);

        return view('employee.dashboard', [
            'dashboard' => $dashboard,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'assignedDistricts' => $assignedDistricts,
            'selectedDistrictId' => $selectedDistrictId,
        ]);
    }
}
