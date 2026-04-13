<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreatePermissionAction;
use App\Actions\Admin\DeletePermissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group');

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        return view('admin.permissions.create');
    }

    public function store(StorePermissionRequest $request, CreatePermissionAction $createPermission): RedirectResponse
    {
        $createPermission($request->validated());

        return redirect()->route('admin.permissions.index')->with('success', 'Permission berhasil ditambahkan.');
    }

    public function destroy(Permission $permission, DeletePermissionAction $deletePermission): RedirectResponse
    {
        $deletePermission($permission);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission berhasil dihapus.');
    }
}
