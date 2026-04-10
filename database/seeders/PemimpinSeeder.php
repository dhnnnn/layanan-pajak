<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PemimpinSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'pemimpin@upp.pendapatan'],
            [
                'name' => 'Pemimpin',
                'password' => Hash::make('password'),
            ]
        );

        $user->syncRoles(['pemimpin']);
    }
}
