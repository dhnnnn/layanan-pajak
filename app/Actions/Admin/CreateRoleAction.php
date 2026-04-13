<?php

namespace App\Actions\Admin;

use App\Models\Role;
use Illuminate\Support\Facades\Log;

class CreateRoleAction
{
    /**
     * @param  array{name: string}  $data
     */
    public function __invoke(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        Log::info('RBAC: role created', [
            'admin' => auth()->id(),
            'role' => $role->name,
            'at' => now()->toIso8601String(),
        ]);

        return $role;
    }
}
