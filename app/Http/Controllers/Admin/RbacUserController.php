<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateRbacUserAction;
use App\Actions\Admin\DeleteRbacUserAction;
use App\Actions\Admin\UpdateRbacUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRbacUserRequest;
use App\Http\Requests\Admin\UpdateRbacUserRequest;
use App\Models\Role;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RbacUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim();

        $users = User::query()
            ->with('roles')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.rbac-users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::query()->orderBy('name')->get();
        $upts = Upt::query()->orderBy('code')->get();

        return view('admin.rbac-users.create', compact('roles', 'upts'));
    }

    public function store(StoreRbacUserRequest $request, CreateRbacUserAction $createUser): RedirectResponse
    {
        $createUser($request->validated());

        return redirect()->route('admin.rbac-users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $rbacUser): View
    {
        $roles = Role::query()->orderBy('name')->get();
        $upts = Upt::query()->orderBy('code')->get();
        $assignedRoleIds = $rbacUser->roles->pluck('id')->toArray();
        $assignedUptId = $rbacUser->upt()?->id;

        return view('admin.rbac-users.edit', compact('rbacUser', 'roles', 'upts', 'assignedRoleIds', 'assignedUptId'));
    }

    public function update(UpdateRbacUserRequest $request, User $rbacUser, UpdateRbacUserAction $updateUser): RedirectResponse
    {
        $updateUser($rbacUser, $request->validated());

        return redirect()->route('admin.rbac-users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $rbacUser, DeleteRbacUserAction $deleteUser): RedirectResponse
    {
        try {
            $deleteUser($rbacUser);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.rbac-users.index')->with('success', 'User berhasil dihapus.');
    }
}
