<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateTaxSubtypeAction;
use App\Actions\Admin\CreateTaxTypeAction;
use App\Actions\Admin\DeleteTaxTypeAction;
use App\Actions\Admin\UpdateTaxSubtypeAction;
use App\Actions\Admin\UpdateTaxTypeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubtypeRequest;
use App\Http\Requests\Admin\StoreTaxTypeRequest;
use App\Models\TaxType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaxTypeController extends Controller
{
    public function index(): View
    {
        $search = request()->string('search')->trim();

        $taxTypes = TaxType::query()
            ->with(['children' => fn ($q) => $q->withCount('taxTargets')->orderBy('code')])
            ->withCount('taxTargets')
            ->whereNull('parent_id')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('children', fn ($q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                    );
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.tax-types.index', compact('taxTypes'));
    }

    public function create(): View
    {
        $parentTaxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.tax-types.create', compact('parentTaxTypes'));
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
        $taxType->load('children');

        return view('admin.tax-types.edit', compact('taxType'));
    }

    public function update(
        StoreTaxTypeRequest $request,
        TaxType $taxType,
        UpdateTaxTypeAction $updateTaxType,
    ): RedirectResponse {
        $updateTaxType($request->validated(), $taxType);

        return redirect()
            ->route('admin.tax-types.edit', $taxType)
            ->with('success', 'Jenis pajak berhasil diperbarui.');
    }

    public function storeSubtype(
        StoreSubtypeRequest $request,
        TaxType $taxType,
        CreateTaxSubtypeAction $createSubtype,
    ): RedirectResponse {
        $createSubtype($request->validated(), $taxType);

        return redirect()
            ->route('admin.tax-types.edit', $taxType)
            ->with('success', 'Subbab berhasil ditambahkan.');
    }

    public function updateSubtype(
        StoreSubtypeRequest $request,
        TaxType $taxType,
        TaxType $subtype,
        UpdateTaxSubtypeAction $updateSubtype,
    ): RedirectResponse {
        $updateSubtype($request->validated(), $subtype);

        return redirect()
            ->route('admin.tax-types.edit', $taxType)
            ->with('success', 'Subbab berhasil diperbarui.');
    }

    public function destroy(TaxType $taxType, DeleteTaxTypeAction $deleteTaxType): RedirectResponse
    {
        try {
            $deleteTaxType($taxType);
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->with('error', $e->errors()['delete'][0]);
        }

        return redirect()
            ->route('admin.tax-types.index')
            ->with('success', 'Jenis pajak berhasil dihapus.');
    }
}
