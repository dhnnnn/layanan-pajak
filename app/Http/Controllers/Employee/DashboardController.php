<?php

namespace App\Http\Controllers\Employee;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\DashboardRequest;
use App\Models\TaxTarget;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(
        DashboardRequest $request,
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
            $availableYears->first() ?? (int) date('Y'),
        );

        // Handle district selection
        $selectedDistrictId = $request->query('district_id');

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
