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
        $upt1 = Upt::query()->updateOrCreate(
            ['code' => 'UPT-01'],
            [
                'name' => 'UPT I',
                'description' => 'Unit Pelaksana Teknis Wilayah I',
            ]
        );

        // Assign first half of districts to UPT I
        $upt1->districts()->sync(
            $districts->take((int) ceil($districts->count() / 2))->pluck('id')
        );

        // Create UPT II
        $upt2 = Upt::query()->updateOrCreate(
            ['code' => 'UPT-02'],
            [
                'name' => 'UPT II',
                'code' => 'UPT-02',
                'description' => 'Unit Pelaksana Teknis Wilayah II',
            ]
        );

        // Assign second half of districts to UPT II
        $upt2->districts()->sync(
            $districts->skip((int) ceil($districts->count() / 2))->pluck('id')
        );
    }
}
