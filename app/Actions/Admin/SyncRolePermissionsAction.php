<?php

namespace App\Actions\Admin;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class SyncRolePermissionsAction
{
    /**
     * @param  array<string>  $permissionIds
     */
    public function __invoke(Role $role, array $permissionIds): void
    {
        DB::transaction(function () use ($role, $permissionIds): void {
            $role->syncPermissions($permissionIds);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            Log::info('RBAC: permissions synced', [
                'admin' => auth()->id(),
                'role' => $role->name,
                'permissions' => $permissionIds,
                'at' => now()->toIso8601String(),
            ]);
        });
    }
}
