<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@upp.pendapatan'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
            ]
        );
        $admin->syncRoles(['admin']);

        $pegawai = User::query()->updateOrCreate(
            ['email' => 'pegawai@upp.pendapatan'],
            [
                'name' => 'Pegawai Test',
                'password' => Hash::make('password'),
            ]
        );
        $pegawai->syncRoles(['pegawai']);

        // Assign districts to test employee
        $districts = District::query()->limit(2)->get();
        $pegawai->districts()->sync($districts->pluck('id'));

        $otherUsers = User::factory(4)->create();

        foreach ($otherUsers as $user) {
            $user->assignRole('pegawai');
        }
    }
}
