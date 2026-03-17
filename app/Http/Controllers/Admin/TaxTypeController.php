<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateTaxTypeAction;
use App\Actions\Admin\DeleteTaxTypeAction;
use App\Actions\Admin\UpdateTaxTypeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaxTypeRequest;
use App\Models\TaxType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxTypeController extends Controller
{
    public function index(): View
    {
        $taxTypes = TaxType::query()
            ->withCount(['taxTargets', 'taxRealizations'])
            ->latest()
            ->paginate(15);

        return view('admin.tax-types.index', compact('taxTypes'));
    }

    public function create(): View
    {
        return view('admin.tax-types.create');
    }

    public function store(StoreTaxTypeRequest $request, CreateTaxTypeAction $createTaxType): RedirectResponse
    {
        $createTaxType($request->validated());

        return redirect()
            ->route('admin.tax-types.index')
            ->with('success', 'Jenis pajak berhasil ditambahkan.');
    }

    public function show(TaxType $taxType): View
    {
        $taxType->load(['taxTargets' => fn ($q) => $q->orderByDesc('year')]);

        return view('admin.tax-types.show', compact('taxType'));
    }

    public function edit(TaxType $taxType): View
    {
        return view('admin.tax-types.edit', compact('taxType'));
    }

    public function update(
        StoreTaxTypeRequest $request,
        TaxType $taxType,
        UpdateTaxTypeAction $updateTaxType,
    ): RedirectResponse {
        $updateTaxType($request->validated(), $taxType);

        return redirect()
            ->route('admin.tax-types.index')
            ->with('success', 'Jenis pajak berhasil diperbarui.');
    }

    public function destroy(TaxType $taxType, DeleteTaxTypeAction $deleteTaxType): RedirectResponse
    {
        $deleteTaxType($taxType);

        return redirect()
            ->route('admin.tax-types.index')
            ->with('success', 'Jenis pajak berhasil dihapus.');
    }
}
