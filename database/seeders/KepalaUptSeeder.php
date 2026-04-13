<?php

namespace Database\Seeders;

use App\Models\Upt;
use App\Models\User;
use Illuminate\Database\Seeder;
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
                'name' => 'UPT I',
                'description' => 'Unit Pelaksana Teknis Wilayah I',
            ]
        );

        $name1 = 'MUCHAMAD KHASAN SOLEH S.E., M.M.';
        $email1 = 'muchamad.khasan.soleh@upp.pendapatan';

        $user1 = User::updateOrCreate(
            ['email' => $email1],
            [
                'name' => $name1,
                'password' => Hash::make('password'),
            ]
        );
        $user1->syncRoles(['kepala_upt']);
        $user1->upts()->sync([$upt1->id]);

        // UPT 2
        $upt2 = Upt::firstOrCreate(
            ['code' => 'UPT-02'],
            [
                'name' => 'UPT II',
                'description' => 'Unit Pelaksana Teknis Wilayah II',
            ]
        );

        $name2 = 'ARDIE KURNIAWAN S. Pi, M.Si.';
        $email2 = 'ardie.kurniawan@upp.pendapatan';

        $user2 = User::updateOrCreate(
            ['email' => $email2],
            [
                'name' => $name2,
                'password' => Hash::make('password'),
            ]
        );
        $user2->syncRoles(['kepala_upt']);
        $user2->upts()->sync([$upt2->id]);
    }
}
