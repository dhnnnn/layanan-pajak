<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\CreateTaxTargetAction;
use App\Actions\Tax\DeleteTaxTargetAction;
use App\Actions\Tax\ListTaxTargetsAction;
use App\Actions\Tax\UpdateTaxTargetAction;
use App\Exports\TaxTargetExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaxTargetRequest;
use App\Models\TaxTarget;
use App\Models\TaxType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaxTargetController extends Controller
{
    public function index(ListTaxTargetsAction $listTaxTargets): View
    {
        $search = request('search');
        $year = request()->filled('year') ? (int) request('year') : (int) date('Y');

        $result = $listTaxTargets($search, $year);

        return view('admin.tax-targets.index', [
            'taxTypes' => $result['taxTypes'],
            'targets' => $result['targets'],
            'availableYears' => $result['availableYears'],
            'year' => $result['year'],
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
            ->route('admin.tax-targets.index')
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
            ->route('admin.tax-targets.index')
            ->with('success', 'Target pajak berhasil diperbarui.');
    }

    public function destroy(TaxTarget $taxTarget, DeleteTaxTargetAction $deleteTaxTarget): RedirectResponse
    {
        $deleteTaxTarget($taxTarget);

        return redirect()
            ->route('admin.tax-targets.index')
            ->with('success', 'Target pajak berhasil dihapus.');
    }
}
