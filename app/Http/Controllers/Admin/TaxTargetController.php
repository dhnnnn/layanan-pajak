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
        $query = TaxTarget::query()->with('taxType');

        // Search functionality
        if (request()->filled('search')) {
            $search = request('search');
            $query->whereHas('taxType', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Year filter
        if (request()->filled('year')) {
            $query->where('year', request('year'));
        }

        $taxTargets = $query
            ->orderByDesc('year')
            ->orderBy('tax_type_id')
            ->paginate(20)
            ->withQueryString();

        // Get available years for filter dropdown
        $availableYears = TaxTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('admin.tax-targets.index', compact('taxTargets', 'availableYears'));
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
