<?php

namespace Database\Factories;

use App\Models\TaxType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxType>
 */
class TaxTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taxNames = [
            'Pajak Bumi dan Bangunan',
            'Bea Perolehan Hak atas Tanah dan Bangunan',
            'Pajak Hotel',
            'Pajak Restoran',
            'Pajak Hiburan',
            'Pajak Reklame',
            'Pajak Penerangan Jalan',
            'Pajak Parkir',
            'Pajak Air Tanah',
            'Pajak Mineral Bukan Logam dan Batuan',
        ];

        $codes = [
            'PBB',
            'BPHTB',
            'P-HTL',
            'P-RST',
            'P-HBR',
            'P-RKL',
            'P-PPJ',
            'P-PRK',
            'P-AT',
            'P-MBLB',
        ];

        $index = fake()
            ->unique()
            ->numberBetween(0, count($taxNames) - 1);

        return [
            'name' => $taxNames[$index],
            'code' => $codes[$index],
        ];
    }
}
