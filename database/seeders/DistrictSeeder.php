<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{

    public function run(): void
    {
        // Clear existing districts to remove duplicates (especially those with numeric codes)
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        District::query()->truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $districts = [
            ['name' => 'Purwodadi'],
            ['name' => 'Tutur'],
            ['name' => 'Puspo'],
            ['name' => 'Lumbang'],
            ['name' => 'Pasrepan'],
            ['name' => 'Kejayan'],
            ['name' => 'Wonorejo'],
            ['name' => 'Purwosari'],
            ['name' => 'Sukorejo'],
            ['name' => 'Prigen'],
            ['name' => 'Pandaan'],
            ['name' => 'Gempol'],
            ['name' => 'Beji'],
            ['name' => 'Bangil'],
            ['name' => 'Rembang'],
            ['name' => 'Kraton'],
            ['name' => 'Pohjentrek'],
            ['name' => 'Gondangwetan'],
            ['name' => 'Winongan'],
            ['name' => 'Grati'],
            ['name' => 'Nguling'],
            ['name' => 'Lekok'],
            ['name' => 'Rejoso'],
            ['name' => 'Tosari'],
        ];

        foreach ($districts as $district) {
            District::query()->create($district);
        }
    }
}
