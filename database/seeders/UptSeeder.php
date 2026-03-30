<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Upt;
use Illuminate\Database\Seeder;

class UptSeeder extends Seeder
{
    public function run(): void
    {
        $districts = District::query()->get();

        // Define districts for UPT I
        $upt1Districts = [
            'Purwodadi', 'Tutur', 'Purwosari', 'Prigen', 
            'Sukorejo', 'Pandaan', 'Gempol', 'Beji', 'Bangil'
        ];

        // Create UPT I
        $upt1 = Upt::query()->updateOrCreate(
            ['code' => 'UPT-01'],
            [
                'name' => 'UPT I',
                'description' => 'Unit Pelaksana Teknis Wilayah I',
            ]
        );

        $upt1->districts()->sync(
            District::query()->whereIn('name', $upt1Districts)->pluck('id')
        );

        // Define districts for UPT II
        $upt2Districts = [
            'Rejoso', 'Lekok', 'Gondangwetan', 'Pasrepan', 'Puspo', 'Tosari',
            'Pohjentrek', 'Grati', 'Nguling', 'Rembang', 'Kraton', 'Kejayan',
            'Winongan', 'Lumbang', 'Wonorejo'
        ];

        // Create UPT II
        $upt2 = Upt::query()->updateOrCreate(
            ['code' => 'UPT-02'],
            [
                'name' => 'UPT II',
                'description' => 'Unit Pelaksana Teknis Wilayah II',
            ]
        );

        $upt2->districts()->sync(
            District::query()->whereIn('name', $upt2Districts)->pluck('id')
        );
    }
}
