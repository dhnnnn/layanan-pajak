<?php

namespace App\Actions\Admin;

use App\Models\Role;
use Illuminate\Support\Facades\Log;

class UpdateRoleAction
{
    /**
     * @param  array{name: string}  $data
     */
    public function __invoke(Role $role, array $data): Role
    {
        $oldName = $role->name;

        $role->update(['name' => $data['name']]);

        Log::info('RBAC: role updated', [
            'admin' => auth()->id(),
            'role_id' => $role->id,
            'old_name' => $oldName,
            'new_name' => $role->name,
            'at' => now()->toIso8601String(),
        ]);

        return $role;
    }
}
