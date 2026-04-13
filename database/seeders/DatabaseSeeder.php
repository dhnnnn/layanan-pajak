<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DistrictSeeder::class,
            UptSeeder::class,
            UserSeeder::class,
            KepalaUptSeeder::class,
            PemimpinSeeder::class,
            MonthSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}
