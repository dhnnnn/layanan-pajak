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
        $admin = User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@layananpajak.test',
            'password' => Hash::make('password'),
        ]);

        $admin->assignRole('admin');

        $pegawai = User::factory()->create([
            'name' => 'Pegawai Tester',
            'email' => 'pegawai@layananpajak.test',
            'password' => Hash::make('password'),
        ]);

        $pegawai->assignRole('pegawai');

        // Assign districts to test employee
        $districts = District::query()->limit(2)->get();
        $pegawai->districts()->attach($districts->pluck('id'));

        $otherUsers = User::factory(4)->create();

        foreach ($otherUsers as $user) {
            $user->assignRole('pegawai');
        }
    }
}
