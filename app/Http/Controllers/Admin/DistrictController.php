<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDistrictRequest;
use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function index(): View
    {
        $districts = District::query()
            ->withCount('users')
            ->orderBy('code')
            ->paginate(20);

        return view('admin.districts.index', compact('districts'));
    }

    public function create(): View
    {
        return view('admin.districts.create');
    }

    public function store(StoreDistrictRequest $request): RedirectResponse
    {
        District::query()->create($request->validated());

        return redirect()
            ->route('admin.districts.index')
            ->with('success', 'Kecamatan berhasil ditambahkan.');
    }

    public function edit(District $district): View
    {
        return view('admin.districts.edit', compact('district'));
    }

    public function update(
        StoreDistrictRequest $request,
        District $district,
    ): RedirectResponse {
        $district->update($request->validated());

        return redirect()
            ->route('admin.districts.index')
            ->with('success', 'Kecamatan berhasil diperbarui.');
    }

    public function destroy(District $district): RedirectResponse
    {
        $district->delete();

        return redirect()
            ->route('admin.districts.index')
            ->with('success', 'Kecamatan berhasil dihapus.');
    }
}
