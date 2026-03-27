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
        if (auth()->user()->hasRole('kepala_upt')) {
            $uptId = auth()->user()->upt_id;
        }

        $result = $generateDashboard($selectedYear, uptId: $uptId);

        return view('admin.dashboard', [
            'dashboard' => $result['data'],
            'totals' => $result['totals'],
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
        ]);
    }
}
