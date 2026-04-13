<?php

namespace App\Actions\Admin;

use App\Models\Permission;
use Illuminate\Support\Facades\Log;

class CreatePermissionAction
{
    /**
     * @param  array{name: string, group: string}  $data
     */
    public function __invoke(array $data): Permission
    {
        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
            'group' => $data['group'],
        ]);

        Log::info('RBAC: permission created', [
            'admin' => auth()->id(),
            'permission' => $permission->name,
            'group' => $permission->group,
            'at' => now()->toIso8601String(),
        ]);

        return $permission;
    }
}
