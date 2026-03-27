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

        $assignedDistricts = $user->accessibleDistricts()->orderBy('name')->get();

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

        // 'all' means no district filter; otherwise validate it belongs to the user
        if ($selectedDistrictId === 'all') {
            $selectedDistrictId = null;
        } elseif (
            $selectedDistrictId === null ||
            ! $assignedDistricts->contains('id', $selectedDistrictId)
        ) {
            $selectedDistrictId = $assignedDistricts->first()?->id;
        }

        $isAllDistricts = $request->query('district_id') === 'all' || $assignedDistricts->isEmpty();

        $result = $generateDashboard($selectedYear, $selectedDistrictId, $user->upt_id);

        return view('employee.dashboard', [
            'dashboard' => $result['data'],
            'totals' => $result['totals'],
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'assignedDistricts' => $assignedDistricts,
            'selectedDistrictId' => $selectedDistrictId,
            'isAllDistricts' => $isAllDistricts,
        ]);
    }
}
