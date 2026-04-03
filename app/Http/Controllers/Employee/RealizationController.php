<?php

namespace App\Http\Controllers\Employee;

use App\Actions\Tax\GetDistrictRealizationDetailsAction;
use App\Actions\Tax\GetEmployeeRealizationIndexAction;
use App\Actions\Tax\StoreTaxRealizationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreTaxRealizationRequest;
use App\Models\Month;
use App\Models\TaxRealization;
use App\Models\TaxTarget;
use App\Models\TaxType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RealizationController extends Controller
{
    public function index(Request $request, GetEmployeeRealizationIndexAction $getIndexData): View
    {
        $year = $request->integer('year', (int) date('Y'));
        $search = $request->string('search')->trim();

        $result = $getIndexData($request->user(), $year, $search);

        return view('employee.realizations.index', array_merge($result, [
            'year' => $year,
            'search' => $search,
        ]));
    }

    public function create(): View
    {
        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        $districts = auth()->user()->accessibleDistricts()->orderBy('name')->get();
        $months = Month::query()->orderBy('number')->get();

        $availableYears = TaxTarget::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view(
            'employee.realizations.create',
            compact('taxTypes', 'districts', 'months', 'availableYears'),
        );
    }

    public function store(
        StoreTaxRealizationRequest $request,
        StoreTaxRealizationAction $storeRealization,
    ): RedirectResponse {
        $storeRealization($request->validated(), $request->user());

        return redirect()
            ->route('field-officer.realizations.index')
            ->with('success', 'Data realisasi pajak berhasil disimpan.');
    }

    public function show(TaxRealization $realization): View
    {
        $this->authorize('view', $realization);

        $realization->load(['taxType', 'district']);
        $months = Month::query()->orderBy('number')->get();

        return view(
            'employee.realizations.show',
            compact('realization', 'months'),
        );
    }

    public function edit(TaxRealization $realization): View
    {
        $this->authorize('update', $realization);

        $user = auth()->user();

        $taxTypes = TaxType::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        $districts = $user->accessibleDistricts()->orderBy('name')->get();
        $months = Month::query()->orderBy('number')->get();

        $availableYears = TaxTarget::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view(
            'employee.realizations.edit',
            compact(
                'realization',
                'taxTypes',
                'districts',
                'months',
                'availableYears',
            ),
        );
    }

    public function update(
        StoreTaxRealizationRequest $request,
        TaxRealization $realization,
        StoreTaxRealizationAction $storeRealization,
    ): RedirectResponse {
        $this->authorize('update', $realization);

        $storeRealization($request->validated(), $request->user());

        return redirect()
            ->route('field-officer.realizations.index')
            ->with('success', 'Data realisasi pajak berhasil diperbarui.');
    }

    public function getTaxTypesByDistrict(
        Request $request,
        string $districtId,
        GetDistrictRealizationDetailsAction $getDetails
    ): JsonResponse {
        $user = $request->user();
        $year = $request->integer('year', (int) date('Y'));

        if (! $user->accessibleDistricts()->where('districts.id', $districtId)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $getDetails($districtId, $year);

        return response()->json($result);
    }
}
