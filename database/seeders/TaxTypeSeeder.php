<?php

namespace Database\Seeders;

use App\Models\TaxType;
use Illuminate\Database\Seeder;

class TaxTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'HOTEL', 'name' => 'Pajak Hotel'],
            ['code' => 'RESTORAN', 'name' => 'Pajak Restoran'],
            ['code' => 'HIBURAN', 'name' => 'Pajak Hiburan'],
            ['code' => 'REKLAME', 'name' => 'Pajak Reklame'],
            ['code' => 'PPJ', 'name' => 'Pajak Penerangan Jalan'],
            ['code' => 'MINERAL', 'name' => 'Pajak Mineral Bukan Logam dan Batuan'],
            ['code' => 'PARKIR', 'name' => 'Pajak Parkir'],
            ['code' => 'AIR_TANAH', 'name' => 'Pajak Air Tanah'],
            ['code' => 'WALET', 'name' => 'Pajak Sarang Burung Walet'],
            ['code' => 'BPHTB', 'name' => 'Bea Perolehan Hak Atas Tanah dan Bangunan (BPHTB)'],
            ['code' => 'PBB', 'name' => 'Pajak Bumi dan Bangunan Perdesaan dan Perkotaan (PBB-P2)'],
        ];

        foreach ($types as $type) {
            TaxType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
