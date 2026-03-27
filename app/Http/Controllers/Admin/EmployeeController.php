<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateEmployeeAction;
use App\Actions\Admin\DeleteEmployeeAction;
use App\Actions\Admin\UpdateEmployeeAction;
use App\Actions\Employee\AssignEmployeeDistrictAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignDistrictRequest;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\District;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $search = request()->string('search')->trim();

        $employees = User::query()
            ->role('pegawai')
            ->with(['districts', 'upt'])
            ->when(auth()->user()->hasRole('kepala_upt'), function ($q) {
                $q->where('upt_id', auth()->user()->upt_id);
            })
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.employees.index', compact('employees'));
    }

    public function create(): View
    {
        $districts = District::query()->orderBy('name')->get();
        $upts = Upt::query()->with('districts')->orderBy('code')->get();

        return view('admin.employees.create', compact('districts', 'upts'));
    }

    public function store(
        StoreEmployeeRequest $request,
        CreateEmployeeAction $createEmployee,
        AssignEmployeeDistrictAction $assignDistricts,
    ): RedirectResponse {
        $employee = $createEmployee([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'upt_id' => $request->filled('upt_id') ? $request->string('upt_id')->toString() : null,
        ]);

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
        $upts = Upt::query()->with('districts')->orderBy('code')->get();
        $assignedIds = $employee->districts()->pluck('districts.id')->all();

        return view(
            'admin.employees.edit',
            compact('employee', 'districts', 'upts', 'assignedIds'),
        );
    }

    public function update(
        UpdateEmployeeRequest $request,
        User $employee,
        UpdateEmployeeAction $updateEmployee,
        AssignEmployeeDistrictAction $assignDistricts,
    ): RedirectResponse {
        $updateEmployee([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'upt_id' => $request->filled('upt_id') ? $request->string('upt_id')->toString() : null,
            'password' => $request->filled('password') ? $request->string('password')->toString() : null,
        ], $employee);

        $assignDistricts($employee, $request->array('district_ids', []));

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(User $employee, DeleteEmployeeAction $deleteEmployee): RedirectResponse
    {
        $deleteEmployee($employee);

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

        if ($employee->upt_id) {
            return redirect()
                ->route('admin.upts.show', $employee->upt_id)
                ->with('success', 'Kecamatan berhasil diperbarui.');
        }

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', 'Kecamatan berhasil diperbarui.');
    }
}
