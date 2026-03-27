<?php

namespace Database\Seeders;

use App\Models\Upt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class KepalaUptSeeder extends Seeder
{
    public function run(): void
    {
        // Roles are already seeded in DatabaseSeeder

        // UPT 1
        $upt1 = Upt::firstOrCreate(
            ['code' => 'UPT-01'],
            [
                'name' => 'UPT Wilayah I',
                'description' => 'Unit Pelaksana Teknis Wilayah I',
            ]
        );

        $user1 = User::firstOrCreate(
            ['email' => 'kepala.upt1@gmail.com'],
            [
                'name' => 'Kepala UPT 1',
                'password' => Hash::make('password'),
                'upt_id' => $upt1->id,
            ]
        );
        $user1->syncRoles(['kepala_upt']);

        // UPT 2
        $upt2 = Upt::firstOrCreate(
            ['code' => 'UPT-02'],
            [
                'name' => 'UPT Wilayah II',
                'description' => 'Unit Pelaksana Teknis Wilayah II',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'kepala.upt2@gmail.com'],
            [
                'name' => 'Kepala UPT 2',
                'password' => Hash::make('password'),
                'upt_id' => $upt2->id,
            ]
        );
        $user2->syncRoles(['kepala_upt']);
    }
}
