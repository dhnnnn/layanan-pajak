<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            // UPT I
            'Purwodadi', 'Tutur', 'Purwosari', 'Prigen', 
            'Sukorejo', 'Pandaan', 'Gempol', 'Beji', 'Bangil',
            
            // UPT II
            'Rejoso', 'Lekok', 'Gondangwetan', 'Pasrepan', 'Puspo', 'Tosari',
            'Pohjentrek', 'Grati', 'Nguling', 'Rembang', 'Kraton', 'Kejayan',
            'Winongan', 'Lumbang', 'Wonorejo'
        ];

        foreach ($districts as $name) {
            District::updateOrCreate(
                ['name' => $name],
                ['code' => 'KEC-' . strtoupper(str_replace(' ', '-', trim($name)))]
            );
        }
    }
}
