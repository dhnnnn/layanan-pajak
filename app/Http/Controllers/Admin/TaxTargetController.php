<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tax\CreateTaxTargetAction;
use App\Actions\Tax\DeleteTaxTargetAction;
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
    public function index(): View
    {
        $search = request('search');
        $year = request()->filled('year') ? (int) request('year') : (int) date('Y');

        $taxTypes = TaxType::query()
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        // Pre-load targets for the selected year, keyed by tax_type_id
        $targets = TaxTarget::query()
            ->where('year', $year)
            ->get()
            ->keyBy('tax_type_id');

        // Get available years for filter dropdown
        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('admin.tax-targets.index', compact('taxTypes', 'targets', 'availableYears', 'year'));
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
