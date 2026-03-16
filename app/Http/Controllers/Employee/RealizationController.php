<?php

namespace App\Http\Controllers\Employee;

use App\Actions\Tax\StoreTaxRealizationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreTaxRealizationRequest;
use App\Models\Month;
use App\Models\TaxRealization;
use App\Models\TaxTarget;
use App\Models\TaxType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RealizationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $realizations = TaxRealization::query()
            ->with(['taxType', 'district'])
            ->where('user_id', $user->id)
            ->orderByDesc('year')
            ->orderBy('tax_type_id')
            ->paginate(15);

        return view('employee.realizations.index', compact('realizations'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $taxTypes = TaxType::query()->orderBy('name')->get();
        $districts = $user->districts()->orderBy('name')->get();
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
            ->route('pegawai.realizations.index')
            ->with('success', 'Data realisasi pajak berhasil disimpan.');
    }

    public function show(TaxRealization $realization): View
    {
        $this->authorizeRealization($realization);

        $realization->load(['taxType', 'district']);
        $months = Month::query()->orderBy('number')->get();

        return view(
            'employee.realizations.show',
            compact('realization', 'months'),
        );
    }

    public function edit(Request $request, TaxRealization $realization): View
    {
        $this->authorizeRealization($realization);

        $user = $request->user();

        $taxTypes = TaxType::query()->orderBy('name')->get();
        $districts = $user->districts()->orderBy('name')->get();
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
        $this->authorizeRealization($realization);

        $storeRealization($request->validated(), $request->user());

        return redirect()
            ->route('pegawai.realizations.index')
            ->with('success', 'Data realisasi pajak berhasil diperbarui.');
    }

    private function authorizeRealization(TaxRealization $realization): void
    {
        abort_if(
            $realization->user_id !== auth()->id(),
            403,
            'Anda tidak memiliki akses ke data realisasi ini.',
        );
    }
}
