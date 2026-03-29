<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardRequest;
use App\Models\TaxTarget;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(
        DashboardRequest $request,
        GenerateTaxDashboardAction $generateDashboard,
    ): View {
        $user = auth()->user();
        $isKepalaUpt = $user->isKepalaUpt();

        $availableYears = TaxTarget::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $selectedYear = (int) $request->query(
            'year',
            $availableYears->first() ?? date('Y'),
        );

        $uptId = null;
        $assignedDistricts = collect();
        $selectedDistrictId = $request->query('district_id');
        $isAllDistricts = $selectedDistrictId === 'all';

        if ($isKepalaUpt) {
            $uptId = $user->upt_id;
            $assignedDistricts = $user->accessibleDistricts()->orderBy('name')->get();

            // Handle district selection for kepala_upt
            if ($isAllDistricts) {
                $selectedDistrictId = null;
            } elseif ($selectedDistrictId === null || ! $assignedDistricts->contains('id', $selectedDistrictId)) {
                $selectedDistrictId = null; // Default to all if null or invalid for kepala_upt
                $isAllDistricts = true;
            }
        }

        $result = $generateDashboard($selectedYear, districtId: $selectedDistrictId, uptId: $uptId);

        $view = $isKepalaUpt ? 'admin.dashboard_kepala_upt' : 'admin.dashboard';

        return view($view, [
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
