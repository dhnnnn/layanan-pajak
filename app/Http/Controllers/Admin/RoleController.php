<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateRoleAction;
use App\Actions\Admin\DeleteRoleAction;
use App\Actions\Admin\SyncRolePermissionsAction;
use App\Actions\Admin\UpdateRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.create');
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $createRole): RedirectResponse
    {
        $createRole($request->validated());

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function show(Role $role): View
    {
        $role->load('users');
        $allPermissions = Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group');

        // Flat map untuk lookup cepat di view: ['nama-permission' => Permission]
        $permissionMap = Permission::query()->get()->keyBy('name');

        $featureMatrix = $this->buildFeatureMatrix();
        $rolePermissionNames = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.show', compact(
            'role',
            'allPermissions',
            'permissionMap',
            'featureMatrix',
            'rolePermissionNames',
        ));
    }

    /**
     * Mapping fitur ke permission dalam format tabel.
     * Kolom: lihat, kelola (create+update), hapus
     *
     * @return array<int, array{group: string, features: array<int, array{label: string, permissions: array{lihat: string|null, kelola: string|null, hapus: string|null}}>}>
     */
    private function buildFeatureMatrix(): array
    {
        return [
            [
                'group' => 'Master Data',
                'features' => [
                    ['label' => 'Kategori Pajak',      'permissions' => ['lihat' => 'view tax-types',    'kelola' => 'manage tax-types',          'hapus' => 'delete tax-types']],
                    ['label' => 'Data Wilayah',         'permissions' => ['lihat' => 'view districts',    'kelola' => 'manage districts',          'hapus' => 'delete districts']],
                    ['label' => 'Data Petugas',         'permissions' => ['lihat' => 'view employees',    'kelola' => 'manage employees',          'hapus' => 'delete employees']],
                    ['label' => 'Unit Pelayanan (UPP)', 'permissions' => ['lihat' => 'view upts',         'kelola' => 'manage upts',               'hapus' => 'delete upts']],
                    ['label' => 'Target Tambahan APBD', 'permissions' => ['lihat' => 'view additional-targets', 'kelola' => 'manage additional-targets', 'hapus' => 'delete additional-targets']],
                ],
            ],
            [
                'group' => 'Laporan & Pantauan',
                'features' => [
                    ['label' => 'Potensi Wajib Pajak', 'permissions' => ['lihat' => 'view maps-discovery',          'kelola' => 'manage maps-discovery',        'hapus' => null]],
                    ['label' => 'Prediksi Penerimaan',  'permissions' => ['lihat' => 'view forecasting',             'kelola' => null,                          'hapus' => null]],
                    ['label' => 'Laporan Anggaran',     'permissions' => ['lihat' => 'view tax-targets',             'kelola' => 'manage tax-targets',           'hapus' => null]],
                    ['label' => 'Realisasi Penerimaan', 'permissions' => ['lihat' => 'view realization-monitoring',  'kelola' => 'export realization-monitoring', 'hapus' => null]],
                    ['label' => 'Import Data',          'permissions' => ['lihat' => null,                           'kelola' => 'import data',                  'hapus' => null]],
                ],
            ],
            [
                'group' => 'Manajemen Akses',
                'features' => [
                    ['label' => 'Role',          'permissions' => ['lihat' => 'view roles',        'kelola' => 'manage roles',       'hapus' => 'delete roles']],
                    ['label' => 'Permission',    'permissions' => ['lihat' => 'view permissions',  'kelola' => 'manage permissions', 'hapus' => null]],
                    ['label' => 'Kelola User',   'permissions' => ['lihat' => 'view rbac-users',   'kelola' => 'manage rbac-users',  'hapus' => 'delete rbac-users']],
                    ['label' => 'Monitor Akses', 'permissions' => ['lihat' => 'view access-monitoring', 'kelola' => null,            'hapus' => null]],
                ],
            ],
            [
                'group' => 'Petugas Lapangan',
                'features' => [
                    ['label' => 'Akses Petugas', 'permissions' => ['lihat' => 'view field-officer', 'kelola' => null, 'hapus' => null]],
                ],
            ],
        ];
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', compact('role'));
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $updateRole): RedirectResponse
    {
        try {
            $updateRole($role, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role, DeleteRoleAction $deleteRole): RedirectResponse
    {
        try {
            $deleteRole($role);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil dihapus.');
    }

    public function syncPermissions(
        SyncRolePermissionsRequest $request,
        Role $role,
        SyncRolePermissionsAction $syncPermissions,
    ): RedirectResponse {
        $syncPermissions($role, $request->input('permissions', []));

        return redirect()->route('admin.roles.show', $role)->with('success', 'Permission role berhasil diperbarui.');
    }
}
