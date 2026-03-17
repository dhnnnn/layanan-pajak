<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignUptDistrictRequest;
use App\Http\Requests\Admin\StoreUptRequest;
use App\Models\District;
use App\Models\Upt;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UptController extends Controller
{
    public function index(): View
    {
        $upts = Upt::query()
            ->withCount(['users', 'districts'])
            ->orderBy('code')
            ->paginate(20);

        return view('admin.upts.index', compact('upts'));
    }

    public function create(): View
    {
        return view('admin.upts.create');
    }

    public function show(Upt $upt): View
    {
        $upt->load(['districts', 'users.districts']);

        // Get available districts (districts in this UPT that are not assigned to other UPTs)
        $allDistricts = District::query()->orderBy('name')->get();
        $assignedToOtherUpts = District::query()
            ->whereHas('upts', function ($query) use ($upt) {
                $query->where('upts.id', '!=', $upt->id);
            })
            ->pluck('id')
            ->toArray();

        return view('admin.upts.show', compact('upt', 'allDistricts', 'assignedToOtherUpts'));
    }

    public function store(StoreUptRequest $request): RedirectResponse
    {
        Upt::query()->create($request->validated());

        return redirect()
            ->route('admin.upts.index')
            ->with('success', 'UPT berhasil ditambahkan.');
    }

    public function edit(Upt $upt): View
    {
        return view('admin.upts.edit', compact('upt'));
    }

    public function update(StoreUptRequest $request, Upt $upt): RedirectResponse
    {
        $upt->update($request->validated());

        return redirect()
            ->route('admin.upts.index')
            ->with('success', 'UPT berhasil diperbarui.');
    }

    public function destroy(Upt $upt): RedirectResponse
    {
        $upt->delete();

        return redirect()
            ->route('admin.upts.index')
            ->with('success', 'UPT berhasil dihapus.');
    }

    public function assignDistricts(Upt $upt): View
    {
        $districts = District::query()->orderBy('name')->get();
        $assignedDistrictIds = $upt->districts()->pluck('districts.id')->toArray();

        return view('admin.upts.assign-districts', compact('upt', 'districts', 'assignedDistrictIds'));
    }

    public function storeDistricts(
        AssignUptDistrictRequest $request,
        Upt $upt,
    ): RedirectResponse {
        $upt->districts()->sync($request->validated('district_ids'));

        return redirect()
            ->route('admin.upts.index')
            ->with('success', 'Wilayah UPT berhasil diperbarui.');
    }
}
