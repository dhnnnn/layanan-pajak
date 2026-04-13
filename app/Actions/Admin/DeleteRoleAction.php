<?php

namespace App\Actions\Admin;

use App\Models\Role;
use Illuminate\Support\Facades\Log;

class DeleteRoleAction
{
    public function __invoke(Role $role): void
    {
        if ($role->isSystemRole()) {
            throw new \RuntimeException("Role '{$role->name}' adalah role bawaan sistem dan tidak dapat dihapus.");
        }

        $userCount = $role->users()->count();
        if ($userCount > 0) {
            throw new \RuntimeException("Role '{$role->name}' masih digunakan oleh {$userCount} pengguna dan tidak dapat dihapus.");
        }

        $roleName = $role->name;
        $role->delete();

        Log::info('RBAC: role deleted', [
            'admin' => auth()->id(),
            'role' => $roleName,
            'at' => now()->toIso8601String(),
        ]);
    }
}
