<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateEmployeeAction;
use App\Actions\Admin\DeleteEmployeeAction;
use App\Actions\Admin\UpdateEmployeeAction;
use App\Actions\Employee\AssignEmployeeDistrictAction;
use App\Actions\Monitoring\ShowEmployeeMonitoringAction;
use App\Actions\Monitoring\ShowUptMonitoringAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignDistrictRequest;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\District;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request, ShowUptMonitoringAction $showUptMonitoring): View
    {
        $user = auth()->user();

        if ($user->isKepalaUpt() && $user->upt_id) {
            $year = $request->integer('year', (int) date('Y'));
            $month = $request->integer('month', (int) date('n'));
            $upt = $user->upt;

            $monitoringData = $showUptMonitoring($upt, $year, $month);

            return view('admin.employees.index', $monitoringData);
        }

        $search = $request->string('search')->trim();

        $employees = User::query()
            ->role('pegawai')
            ->with(['districts', 'upt'])
            ->when($user->hasRole('kepala_upt'), function ($q) use ($user) {
                $q->where('upt_id', $user->upt_id);
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
    ): RedirectResponse {
        $createEmployee($request->validated());

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function show(
        Request $request,
        User $employee,
        ShowEmployeeMonitoringAction $showEmployeeMonitoring,
    ): View {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'tunggakan');
        $sortDir = $request->query('sort_dir', 'desc');
        $taxTypeId = $request->query('tax_type_id');

        $upt = $employee->upt;

        if (! $upt) {
            $employee->load('districts');

            return view('admin.employees.show', [
                'employee' => $employee,
                'error' => 'Pegawai belum ditugaskan ke UPT mana pun.',
            ]);
        }

        $result = $showEmployeeMonitoring($upt, $employee, $year, $month, $search, $sortBy, $sortDir, $taxTypeId);

        return view('admin.employees.show', $result);
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
    ): RedirectResponse {
        $updateEmployee($request->validated(), $employee);

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
