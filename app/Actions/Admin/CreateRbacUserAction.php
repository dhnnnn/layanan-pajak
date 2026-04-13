<?php

namespace App\Actions\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class CreateRbacUserAction
{
    /**
     * @param array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     roles: list<string>,
     *     upt_id?: string|null,
     * } $data
     */
    public function __invoke(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles($data['roles']);

        // Set role_id to first assigned role
        $firstRole = $user->roles->first();
        if ($firstRole) {
            $user->updateQuietly(['role_id' => $firstRole->id]);
        }

        // If kepala_upt role assigned, sync UPT
        $hasKepalaUpt = collect($data['roles'])->contains(function (string $roleId): bool {
            $role = Role::find($roleId);

            return $role?->name === 'kepala_upt';
        });

        if ($hasKepalaUpt && ! empty($data['upt_id'])) {
            $user->upts()->sync([$data['upt_id']]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Log::info('RBAC: user created', [
            'admin' => auth()->id(),
            'user_id' => $user->id,
            'roles' => $data['roles'],
            'at' => now()->toIso8601String(),
        ]);

        return $user;
    }
}
