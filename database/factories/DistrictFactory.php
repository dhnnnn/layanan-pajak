<?php

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 1;

        return [
            'name' => fake()
                ->unique()
                ->randomElement([
                    'Kecamatan Purwodadi',
                    'Kecamatan Tutur',
                    'Kecamatan Puspo',
                    'Kecamatan Lumbang',
                    'Kecamatan Pasrepan',
                    'Kecamatan Kejayan',
                    'Kecamatan Wonorejo',
                    'Kecamatan Purwosari',
                    'Kecamatan Sukorejo',
                    'Kecamatan Prigen',
                    'Kecamatan Pandaan',
                    'Kecamatan Gempol',
                    'Kecamatan Beji',
                    'Kecamatan Bangil',
                    'Kecamatan Rembang',
                    'Kecamatan Kraton',
                    'Kecamatan Pohjentrek',
                    'Kecamatan Gondangwetan',
                    'Kecamatan Winongan',
                    'Kecamatan Grati',
                    'Kecamatan Nguling',
                    'Kecamatan Lekok',
                    'Kecamatan Rejoso',
                    'Kecamatan Tosari',
                ]),
            'code' => '35.14.'.str_pad((string) $counter++, 2, '0', STR_PAD_LEFT),
        ];
    }
}
