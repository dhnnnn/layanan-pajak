<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\View\View;

class AccessMonitoringController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $groups = Permission::query()
            ->select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        return view('admin.access-monitoring.index', compact('roles', 'groups'));
    }

    public function show(Role $role): View
    {
        $role->load(['permissions', 'users']);
        $permissionsByGroup = $role->permissions->groupBy('group');

        return view('admin.access-monitoring.show', compact('role', 'permissionsByGroup'));
    }
}
