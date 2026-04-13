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
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.show', compact('role', 'allPermissions', 'rolePermissionIds'));
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
