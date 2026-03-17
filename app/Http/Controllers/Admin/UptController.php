<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignUptDistrictRequest;
use App\Http\Requests\Admin\StoreUptRequest;
use App\Models\District;
use App\Models\Upt;
use App\Models\User;
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

        return view('admin.upts.show', compact('upt'));
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

    public function manageEmployees(Upt $upt): View
    {
        $search = request()->string('search')->trim();

        $allEmployees = User::query()
            ->role('pegawai')
            ->with('upt')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // IDs of employees already in this UPT (needed to preserve checked state across pages)
        $assignedIds = User::query()
            ->where('upt_id', $upt->id)
            ->pluck('id');

        return view('admin.upts.manage-employees', compact('upt', 'allEmployees', 'assignedIds'));
    }

    public function assignEmployeeDistricts(Upt $upt, User $employee): View
    {
        $upt->load('districts');
        $assignedDistrictIds = $employee->districts()->pluck('districts.id');

        return view('admin.upts.assign-employee-districts', compact('upt', 'employee', 'assignedDistrictIds'));
    }

    public function storeEmployees(Upt $upt): RedirectResponse
    {
        $validated = request()->validate([
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $userIds = $validated['user_ids'] ?? [];

        // Remove employees that were unassigned from this UPT
        User::query()
            ->where('upt_id', $upt->id)
            ->whereNotIn('id', $userIds)
            ->update(['upt_id' => null]);

        // Assign selected employees to this UPT
        if (! empty($userIds)) {
            User::query()
                ->whereIn('id', $userIds)
                ->update(['upt_id' => $upt->id]);
        }

        return redirect()
            ->route('admin.upts.show', $upt)
            ->with('success', 'Pegawai UPT berhasil diperbarui.');
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
