<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Employee\AssignEmployeeDistrictAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignDistrictRequest;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Models\District;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = User::query()
            ->role('pegawai')
            ->with('districts')
            ->latest()
            ->paginate(15);

        return view('admin.employees.index', compact('employees'));
    }

    public function create(): View
    {
        $districts = District::query()->orderBy('name')->get();

        return view('admin.employees.create', compact('districts'));
    }

    public function store(
        StoreEmployeeRequest $request,
        AssignEmployeeDistrictAction $assignDistricts,
    ): RedirectResponse {
        $employee = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
        ]);

        $employee->assignRole('pegawai');

        if ($request->filled('district_ids')) {
            $assignDistricts($employee, $request->array('district_ids'));
        }

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function show(User $employee): View
    {
        $employee->load(
            'districts',
            'taxRealizations.taxType',
            'taxRealizations.district',
        );

        return view('admin.employees.show', compact('employee'));
    }

    public function edit(User $employee): View
    {
        $districts = District::query()->orderBy('name')->get();
        $assignedIds = $employee->districts()->pluck('districts.id')->all();

        return view(
            'admin.employees.edit',
            compact('employee', 'districts', 'assignedIds'),
        );
    }

    public function update(
        StoreEmployeeRequest $request,
        User $employee,
        AssignEmployeeDistrictAction $assignDistricts,
    ): RedirectResponse {
        $data = [
            'name' => $request->string('name'),
            'email' => $request->string('email'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->string('password'));
        }

        $employee->update($data);

        $assignDistricts($employee, $request->array('district_ids', []));

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai berhasil dihapus.');
    }

    public function assignDistricts(
        AssignDistrictRequest $request,
        User $employee,
        AssignEmployeeDistrictAction $assignDistricts,
    ): RedirectResponse {
        $assignDistricts($employee, $request->array('district_ids'));

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', 'Kecamatan berhasil diperbarui.');
    }
}
