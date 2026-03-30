<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\CreateTaxTargetAction;
use App\Actions\Tax\DeleteTaxTargetAction;
use App\Actions\Tax\GenerateTaxDashboardAction;
use App\Actions\Tax\ListTaxTargetsAction;
use App\Actions\Tax\ProcessTaxTargetImportAction;
use App\Actions\Tax\UpdateTaxTargetAction;
use App\Exports\TaxTargetExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaxTargetRequest;
use App\Imports\TaxTargetImport;
use App\Models\TaxTarget;
use App\Models\TaxType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaxTargetController extends Controller
{
    public function index(): View
    {
        return view('admin.tax-targets.index');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $file = $request->file('file');
        $year = $request->integer('year');

        if ($file === null) {
            return redirect()->back()->with('error', 'File tidak ditemukan.');
        }

        $import = new TaxTargetImport(previewOnly: true, year: $year);
        Excel::import($import, $file);

        return view('admin.tax-targets.preview', [
            'storedPath' => $file->store('imports/tax-targets/pending', 'local'),
            'previewData' => $import->getPreviewData(),
            'fileName' => $file->getClientOriginalName(),
            'year' => $year,
        ]);
    }

    public function storeImport(Request $request, ProcessTaxTargetImportAction $processImport): RedirectResponse
    {
        $storedPath = $request->string('stored_path')->toString();
        $year = $request->integer('year');

        $import = new TaxTargetImport(year: $year);
        Excel::import($import, $storedPath, 'local');

        $processImport($import->getPreviewData());

        Storage::disk('local')->delete($storedPath);

        return redirect()
            ->route('admin.tax-targets.manage')
            ->with('success', 'Import Target APBD berhasil diselesaikan.');
    }

    public function manage(ListTaxTargetsAction $listTaxTargets): View
    {
        $search = request('search');
        $year = request()->filled('year') ? (int) request('year') : (int) date('Y');

        $result = $listTaxTargets($search, $year);

        return view('admin.tax-targets.manage', [
            'taxTypes' => $result['taxTypes'],
            'availableYears' => $result['availableYears'],
            'year' => $result['year'],
        ]);
    }

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
        GenerateTaxDashboardAction $generateDashboard
    ): View {
        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');
        $search = $request->query('search');
        $selectedDistrict = $request->query('district');
        
        // Get summarized data for the header (Consistency with dashboard)
        $dashboard = $generateDashboard(year: $year);
        $summary = collect($dashboard['data'])->firstWhere('tax_type_id', $taxType->id);

        // Get all descendant IDs recursively to aggregate WP data
        $allTaxTypeIds = $this->getAllDescendantIds($taxType);

        $query = \App\Models\SimpaduTaxPayerRealization::query()
            ->select('npwpd', 'nm_wp', DB::raw('SUM(total_realization) as total_realization'), DB::raw('MAX(last_sync_at) as last_sync_at'))
            ->whereIn('tax_type_id', $allTaxTypeIds)
            ->where('year', $year);

        // Search Filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nm_wp', 'like', "%{$search}%")
                  ->orWhere('npwpd', 'like', "%{$search}%");
            });
        }

        // District Filter
        if ($selectedDistrict) {
            $query->where('kd_kecamatan', $selectedDistrict);
        }

        $payers = $query->groupBy('npwpd', 'nm_wp')
            ->orderByDesc('total_realization')
            ->paginate(15)
            ->withQueryString();

        $districts = \App\Models\District::query()->orderBy('name')->get();

        return view('admin.tax-targets.show', [
            'taxType' => $taxType,
            'year' => $year,
            'summary' => $summary,
            'payers' => $payers,
            'districts' => $districts,
            'search' => $search,
            'selectedDistrict' => $selectedDistrict,
        ]);
    }

    private function getAllDescendantIds(TaxType $taxType): array
    {
        $ids = [$taxType->id];

        foreach ($taxType->children as $child) {
            $ids = array_merge($ids, $this->getAllDescendantIds($child));
        }

        return $ids;
    }

    public function export(): BinaryFileResponse
    {
        $year = request()->integer('year') ?: (int) date('Y');
        $filename = "target-pajak-{$year}.xlsx";

        return Excel::download(new TaxTargetExport($year), $filename);
    }

    public function create(Request $request): View
    {
        $tax_type_id = $request->string('tax_type_id')->toString();
        $year = $request->integer('year') ?: (int) date('Y');
        
        $taxTypes = TaxType::query()->orderBy('name')->get();
        $baselineAmount = 0;
        $q1_baseline = 0;
        $q2_baseline = 0;
        $q3_baseline = 0;
        $q4_baseline = 0;

        if ($tax_type_id) {
            $taxType = TaxType::find($tax_type_id);
            if ($taxType) {
                $sTarget = \App\Models\SimpaduTarget::where('no_ayat', $taxType->simpadu_code)
                    ->where('year', $year)
                    ->first();
                
                if ($sTarget) {
                    $baselineAmount = (float) $sTarget->total_target;
                    // Calculate quarterly amounts based on baseline percentages
                    $q1_baseline = $baselineAmount * ($sTarget->q1_pct / 100);
                    $q2_baseline = $baselineAmount * ($sTarget->q2_pct / 100);
                    $q3_baseline = $baselineAmount * ($sTarget->q3_pct / 100);
                    $q4_baseline = $baselineAmount * ($sTarget->q4_pct / 100);
                }
            }
        }

        return view('admin.tax-targets.create', compact(
            'taxTypes', 
            'baselineAmount', 
            'year',
            'q1_baseline',
            'q2_baseline',
            'q3_baseline',
            'q4_baseline'
        ));
    }

    public function store(StoreTaxTargetRequest $request, CreateTaxTargetAction $createTaxTarget): RedirectResponse
    {
        $createTaxTarget($request->validated());

        return redirect()
            ->route('admin.tax-targets.manage', ['year' => $request->year])
            ->with('success', 'Target pajak berhasil ditambahkan.');
    }

    public function edit(TaxTarget $taxTarget): View
    {
        $taxTypes = TaxType::query()->orderBy('name')->get();

        return view('admin.tax-targets.edit', compact('taxTarget', 'taxTypes'));
    }

    public function update(
        StoreTaxTargetRequest $request,
        TaxTarget $taxTarget,
        UpdateTaxTargetAction $updateTaxTarget,
    ): RedirectResponse {
        $updateTaxTarget($request->validated(), $taxTarget);

        return redirect()
            ->route('admin.tax-targets.manage', ['year' => $taxTarget->year])
            ->with('success', 'Target pajak berhasil diperbarui.');
    }

    public function destroy(TaxTarget $taxTarget, DeleteTaxTargetAction $deleteTaxTarget): RedirectResponse
    {
        $year = $taxTarget->year;
        $deleteTaxTarget($taxTarget);
 
        return redirect()
            ->route('admin.tax-targets.manage', ['year' => $year])
            ->with('success', 'Target pajak berhasil dihapus.');
    }
}
