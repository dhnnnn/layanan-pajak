<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaxTargetRequest;
use App\Models\TaxTarget;
use App\Models\TaxType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxTargetController extends Controller
{
    public function index(): View
    {
        $taxTargets = TaxTarget::query()
            ->with('taxType')
            ->orderByDesc('year')
            ->orderBy('tax_type_id')
            ->paginate(20);

        return view('admin.tax-targets.index', compact('taxTargets'));
    }

    public function create(): View
    {
        $taxTypes = TaxType::query()->orderBy('name')->get();

        return view('admin.tax-targets.create', compact('taxTypes'));
    }

    public function store(StoreTaxTargetRequest $request): RedirectResponse
    {
        TaxTarget::query()->create($request->validated());

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
    ): RedirectResponse {
        $taxTarget->update($request->validated());

        return redirect()
            ->route('admin.tax-targets.index')
            ->with('success', 'Target pajak berhasil diperbarui.');
    }

    public function destroy(TaxTarget $taxTarget): RedirectResponse
    {
        $taxTarget->delete();

        return redirect()
            ->route('admin.tax-targets.index')
            ->with('success', 'Target pajak berhasil dihapus.');
    }
}
