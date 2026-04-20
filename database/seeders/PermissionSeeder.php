<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * @return array<string, list<string>>
     */
    private function permissions(): array
    {
        return [
            'master-data' => [
                'view tax-types',
                'manage tax-types',
                'delete tax-types',
                'view districts',
                'manage districts',
                'delete districts',
                'view employees',
                'manage employees',
                'delete employees',
                'view upts',
                'manage upts',
                'delete upts',
                'view additional-targets',
                'manage additional-targets',
                'delete additional-targets',
            ],
            'monitoring' => [
                'view forecasting',
                'view tax-targets',
                'manage tax-targets',
                'view realization-monitoring',
                'export realization-monitoring',
                'import data',
            ],
            'field-officer' => [
                'view field-officer',
            ],
            'rbac' => [
                'view roles',
                'manage roles',
                'delete roles',
                'view permissions',
                'manage permissions',
                'view rbac-users',
                'manage rbac-users',
                'delete rbac-users',
                'view access-monitoring',
            ],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function rolePermissions(): array
    {
        return [
            'admin' => [
                'view tax-types', 'manage tax-types', 'delete tax-types',
                'view districts', 'manage districts', 'delete districts',
                'view employees', 'manage employees', 'delete employees',
                'view upts', 'manage upts', 'delete upts',
                'view additional-targets', 'manage additional-targets', 'delete additional-targets',
                'view forecasting',
                'view tax-targets', 'manage tax-targets',
                'view realization-monitoring', 'export realization-monitoring',
                'import data',
                'view roles', 'manage roles', 'delete roles',
                'view permissions', 'manage permissions',
                'view rbac-users', 'manage rbac-users', 'delete rbac-users',
                'view access-monitoring',
            ],
            'kepala_upt' => [
                'view tax-types', 'manage tax-types',
                'view districts', 'manage districts',
                'view employees', 'manage employees',
                'view upts', 'manage upts',
                'view forecasting',
                'view realization-monitoring', 'export realization-monitoring',
            ],
            'pegawai' => [
                'view field-officer',
            ],
            'pemimpin' => [
                'view forecasting',
                'view tax-targets',
                'view realization-monitoring', 'export realization-monitoring',
            ],
        ];
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Seed permissions
        foreach ($this->permissions() as $group => $names) {
            foreach ($names as $name) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['group' => $group]
                );
            }
        }

        // Assign default permissions to system roles
        foreach ($this->rolePermissions() as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->syncPermissions($permissionNames);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
