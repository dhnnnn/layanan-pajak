<?php

namespace App\Actions\Admin;

use App\Models\Permission;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class DeletePermissionAction
{
    public function __invoke(Permission $permission): void
    {
        $permissionName = $permission->name;

        $permission->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Log::info('RBAC: permission deleted', [
            'admin' => auth()->id(),
            'permission' => $permissionName,
            'at' => now()->toIso8601String(),
        ]);
    }
}
