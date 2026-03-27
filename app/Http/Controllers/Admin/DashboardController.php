<?php

namespace App\Http\Controllers\Admin;

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

        if (auth()->user()->isKepalaUpt()) {
            $uptId = auth()->user()->upt_id;
            $assignedDistricts = auth()->user()->accessibleDistricts()->orderBy('name')->get();

            // Handle district selection for kepala_upt
            if ($isAllDistricts) {
                $selectedDistrictId = null;
            } elseif ($selectedDistrictId === null || ! $assignedDistricts->contains('id', $selectedDistrictId)) {
                $selectedDistrictId = null; // Default to all if null or invalid for kepala_upt
                $isAllDistricts = true;
            }
        }

        $result = $generateDashboard($selectedYear, districtId: $selectedDistrictId, uptId: $uptId);

        $view = auth()->user()->isKepalaUpt() ? 'admin.dashboard_kepala_upt' : 'admin.dashboard';

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
