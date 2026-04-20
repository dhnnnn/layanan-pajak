<?php

namespace App\Support;

class SidebarPermissionMap
{
    /**
     * Returns sidebar navigation groups with their items.
     * Items with permission = null are always visible to authenticated users.
     *
     * @return array<string, array{label: string, items: list<array{permission: string|null, route: string, label: string, routePattern: string}>}>
     */
    public static function groups(): array
    {
        return [
            'master-data' => [
                'label' => 'Master Data',
                'items' => [
                    [
                        'permission' => 'view tax-types',
                        'route' => 'admin.tax-types.index',
                        'label' => 'Jenis Pajak',
                        'routePattern' => 'admin.tax-types.*',
                    ],
                    [
                        'permission' => 'view districts',
                        'route' => 'admin.districts.index',
                        'label' => 'Kecamatan',
                        'routePattern' => 'admin.districts.*',
                    ],
                    [
                        'permission' => 'view employees',
                        'route' => 'admin.employees.index',
                        'label' => 'Pegawai',
                        'routePattern' => 'admin.employees.*',
                    ],
                    [
                        'permission' => 'view upts',
                        'route' => 'admin.upts.index',
                        'label' => 'UPT',
                        'routePattern' => 'admin.upts.*',
                    ],
                ],
            ],
            'monitoring' => [
                'label' => 'Pengelolaan',
                'items' => [
                    [
                        'permission' => 'view forecasting',
                        'route' => 'admin.forecasting.index',
                        'label' => 'Prediksi Penerimaan',
                        'routePattern' => 'admin.forecasting.*',
                    ],
                    [
                        'permission' => 'view additional-targets',
                        'route' => 'admin.upt-additional-targets.index',
                        'label' => 'Target Tambahan',
                        'routePattern' => 'admin.upt-additional-targets.*',
                    ],
                    [
                        'permission' => 'view tax-targets',
                        'route' => 'admin.tax-targets.report',
                        'label' => 'Target APBD',
                        'routePattern' => 'admin.tax-targets.*',
                    ],
                    [
                        'permission' => 'view realization-monitoring',
                        'route' => 'admin.realization-monitoring.index',
                        'label' => 'Monitoring Realisasi',
                        'routePattern' => 'admin.realization-monitoring.*',
                    ],
                ],
            ],
            'rbac' => [
                'label' => 'Manajemen Akses',
                'items' => [
                    [
                        'permission' => 'view roles',
                        'route' => 'admin.roles.index',
                        'label' => 'Role',
                        'routePattern' => 'admin.roles.*',
                    ],
                    [
                        'permission' => 'view permissions',
                        'route' => 'admin.permissions.index',
                        'label' => 'Permission',
                        'routePattern' => 'admin.permissions.*',
                    ],
                    [
                        'permission' => 'view rbac-users',
                        'route' => 'admin.rbac-users.index',
                        'label' => 'Kelola User',
                        'routePattern' => 'admin.rbac-users.*',
                    ],
                    [
                        'permission' => 'view access-monitoring',
                        'route' => 'admin.access-monitoring.index',
                        'label' => 'Monitoring Akses',
                        'routePattern' => 'admin.access-monitoring.*',
                    ],
                ],
            ],
        ];
    }
}
