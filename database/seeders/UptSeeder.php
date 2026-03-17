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

        // Create UPT I
        $upt1 = Upt::query()->create([
            'name' => 'UPT I',
            'code' => 'UPT-01',
            'description' => 'Unit Pelaksana Teknis Wilayah I',
        ]);

        // Assign first half of districts to UPT I
        $upt1->districts()->attach(
            $districts->take((int) ceil($districts->count() / 2))->pluck('id')
        );

        // Create UPT II
        $upt2 = Upt::query()->create([
            'name' => 'UPT II',
            'code' => 'UPT-02',
            'description' => 'Unit Pelaksana Teknis Wilayah II',
        ]);

        // Assign second half of districts to UPT II
        $upt2->districts()->attach(
            $districts->skip((int) ceil($districts->count() / 2))->pluck('id')
        );
    }
}
