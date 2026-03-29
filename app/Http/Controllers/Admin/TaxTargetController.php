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
            'targets' => $result['targets'],
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

    public function export(): BinaryFileResponse
    {
        $year = request()->integer('year') ?: (int) date('Y');
        $filename = "target-pajak-{$year}.xlsx";

        return Excel::download(new TaxTargetExport($year), $filename);
    }

    public function create(): View
    {
        $taxTypes = TaxType::query()->orderBy('name')->get();

        return view('admin.tax-targets.create', compact('taxTypes'));
    }

    public function store(StoreTaxTargetRequest $request, CreateTaxTargetAction $createTaxTarget): RedirectResponse
    {
        $createTaxTarget($request->validated());

        return redirect()
            ->route('admin.tax-targets.manage')
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
            ->route('admin.tax-targets.manage')
            ->with('success', 'Target pajak berhasil diperbarui.');
    }

    public function destroy(TaxTarget $taxTarget, DeleteTaxTargetAction $deleteTaxTarget): RedirectResponse
    {
        $deleteTaxTarget($taxTarget);

        return redirect()
            ->route('admin.tax-targets.manage')
            ->with('success', 'Target pajak berhasil dihapus.');
    }
}
