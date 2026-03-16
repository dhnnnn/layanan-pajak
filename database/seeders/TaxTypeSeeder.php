<?php

namespace Database\Seeders;

use App\Models\TaxType;
use Illuminate\Database\Seeder;

class TaxTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxTypes = [
            ['name' => 'Pajak Bumi dan Bangunan', 'code' => 'PBB'],
            [
                'name' => 'Bea Perolehan Hak atas Tanah dan Bangunan',
                'code' => 'BPHTB',
            ],
            ['name' => 'Pajak Hotel', 'code' => 'P-HTL'],
            ['name' => 'Pajak Restoran', 'code' => 'P-RST'],
            ['name' => 'Pajak Hiburan', 'code' => 'P-HBR'],
            ['name' => 'Pajak Reklame', 'code' => 'P-RKL'],
            ['name' => 'Pajak Penerangan Jalan', 'code' => 'P-PPJ'],
            ['name' => 'Pajak Parkir', 'code' => 'P-PRK'],
            ['name' => 'Pajak Air Tanah', 'code' => 'P-AT'],
            [
                'name' => 'Pajak Mineral Bukan Logam dan Batuan',
                'code' => 'P-MBLB',
            ],
        ];

        foreach ($taxTypes as $taxType) {
            TaxType::query()->firstOrCreate(
                ['code' => $taxType['code']],
                ['name' => $taxType['name']],
            );
        }
    }
}
