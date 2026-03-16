<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $districts = [
            ['name' => 'Purwodadi', 'code' => '35.14.01'],
            ['name' => 'Tutur', 'code' => '35.14.02'],
            ['name' => 'Puspo', 'code' => '35.14.03'],
            ['name' => 'Lumbang', 'code' => '35.14.04'],
            ['name' => 'Pasrepan', 'code' => '35.14.05'],
            ['name' => 'Kejayan', 'code' => '35.14.06'],
            ['name' => 'Wonorejo', 'code' => '35.14.07'],
            ['name' => 'Purwosari', 'code' => '35.14.08'],
            ['name' => 'Sukorejo', 'code' => '35.14.09'],
            ['name' => 'Prigen', 'code' => '35.14.10'],
            ['name' => 'Pandaan', 'code' => '35.14.11'],
            ['name' => 'Gempol', 'code' => '35.14.12'],
            ['name' => 'Beji', 'code' => '35.14.13'],
            ['name' => 'Bangil', 'code' => '35.14.14'],
            ['name' => 'Rembang', 'code' => '35.14.15'],
            ['name' => 'Kraton', 'code' => '35.14.16'],
            ['name' => 'Pohjentrek', 'code' => '35.14.17'],
            ['name' => 'Gondangwetan', 'code' => '35.14.18'],
            ['name' => 'Winongan', 'code' => '35.14.19'],
            ['name' => 'Grati', 'code' => '35.14.20'],
            ['name' => 'Nguling', 'code' => '35.14.21'],
            ['name' => 'Lekok', 'code' => '35.14.22'],
            ['name' => 'Rejoso', 'code' => '35.14.23'],
            ['name' => 'Tosari', 'code' => '35.14.24'],
        ];

        foreach ($districts as $district) {
            District::query()->firstOrCreate(
                ['code' => $district['code']],
                ['name' => $district['name']],
            );
        }
    }
}
