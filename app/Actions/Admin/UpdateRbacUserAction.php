<?php

namespace App\Actions\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class UpdateRbacUserAction
{
    /**
     * @param array{
     *     name: string,
     *     email: string,
     *     password?: string|null,
     *     roles: list<string>,
     *     upt_id?: string|null,
     * } $data
     */
    public function __invoke(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        $user->syncRoles($data['roles']);

        // Update role_id to first assigned role
        $firstRole = $user->roles->first();
        $user->updateQuietly(['role_id' => $firstRole?->id]);

        // Sync UPT if kepala_upt
        $hasKepalaUpt = collect($data['roles'])->contains(function (string $roleId): bool {
            $role = Role::find($roleId);

            return $role?->name === 'kepala_upt';
        });

        if ($hasKepalaUpt && ! empty($data['upt_id'])) {
            $user->upts()->sync([$data['upt_id']]);
        } elseif (! $hasKepalaUpt) {
            $user->upts()->detach();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Log::info('RBAC: user updated', [
            'admin' => auth()->id(),
            'user_id' => $user->id,
            'roles' => $data['roles'],
            'at' => now()->toIso8601String(),
        ]);

        return $user;
    }
}
