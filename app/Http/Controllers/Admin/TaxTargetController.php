<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Actions\Tax\ShowTaxTargetDetailAction;
use App\Exports\TaxTargetExport;
use App\Http\Controllers\Controller;
use App\Models\TaxTarget;
use App\Models\TaxType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaxTargetController extends Controller
{
    public function report(
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

        $search = $request->query('search');

        $result = $generateDashboard(
            year: $selectedYear,
            search: $search
        );

        return view('admin.tax-targets.report', [
            'dashboard' => $result['data'],
            'totals' => $result['totals'],
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
        ]);
    }

    public function show(
        TaxType $taxType, 
        Request $request, 
        ShowTaxTargetDetailAction $showDetail
    ): View {
        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');
        $search = $request->query('search');
        $selectedDistrict = $request->query('district');
        
        $result = $showDetail($taxType, $year, $search, $selectedDistrict);

        return view('admin.tax-targets.show', [
            'taxType' => $result['taxType'],
            'year' => $year,
            'summary' => $result['summary'],
            'payers' => $result['payers'],
            'districts' => $result['districts'],
            'search' => $search,
            'selectedDistrict' => $selectedDistrict,
        ]);
    }

    public function export(): BinaryFileResponse
    {
        $year = request()->integer('year', (int) date('Y'));
        $filename = "target-pajak-{$year}.xlsx";

        return Excel::download(new TaxTargetExport($year), $filename);
    }
}
