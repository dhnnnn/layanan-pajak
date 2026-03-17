<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateDistrictAction;
use App\Actions\Admin\DeleteDistrictAction;
use App\Actions\Admin\UpdateDistrictAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDistrictRequest;
use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function index(): View
    {
        $search = request()->string('search')->trim();

        $districts = District::query()
            ->withCount('users')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('admin.districts.index', compact('districts'));
    }

    public function create(): View
    {
        return view('admin.districts.create');
    }

    public function store(StoreDistrictRequest $request, CreateDistrictAction $createDistrict): RedirectResponse
    {
        $createDistrict($request->validated());

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
        UpdateDistrictAction $updateDistrict,
    ): RedirectResponse {
        $updateDistrict($request->validated(), $district);

        return redirect()
            ->route('admin.districts.index')
            ->with('success', 'Kecamatan berhasil diperbarui.');
    }

    public function destroy(District $district, DeleteDistrictAction $deleteDistrict): RedirectResponse
    {
        $deleteDistrict($district);

        return redirect()
            ->route('admin.districts.index')
            ->with('success', 'Kecamatan berhasil dihapus.');
    }
}
