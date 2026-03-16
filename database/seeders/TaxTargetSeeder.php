<?php

namespace Database\Seeders;

use App\Models\TaxTarget;
use App\Models\TaxType;
use Illuminate\Database\Seeder;

class TaxTargetSeeder extends Seeder
{
    public function run(): void
    {
        $years = [2024, 2025, 2026];

        /**
         * Realistic APBD targets per tax type (in IDR).
         * Keyed by tax type code.
         *
         * @var array<string, float>
         */
        $targetsByCode = [
            'PBB' => 52_000_000_000,
            'BPHTB' => 35_000_000_000,
            'P-HTL' => 8_500_000_000,
            'P-RST' => 12_000_000_000,
            'P-HBR' => 4_200_000_000,
            'P-RKL' => 6_800_000_000,
            'P-PPJ' => 18_000_000_000,
            'P-PRK' => 3_500_000_000,
            'P-AT' => 2_100_000_000,
            'P-MBLB' => 1_800_000_000,
        ];

        $taxTypes = TaxType::query()->get()->keyBy('code');

        foreach ($years as $year) {
            // Apply a small growth factor per year: 2024 = 1.0x, 2025 = 1.05x, 2026 = 1.10x
            $growthFactor = 1.0 + ($year - 2024) * 0.05;

            foreach ($targetsByCode as $code => $baseAmount) {
                $taxType = $taxTypes->get($code);

                if ($taxType === null) {
                    continue;
                }

                TaxTarget::query()->firstOrCreate(
                    [
                        'tax_type_id' => $taxType->id,
                        'year' => $year,
                    ],
                    [
                        'target_amount' => round(
                            $baseAmount * $growthFactor,
                            2,
                        ),
                    ],
                );
            }
        }
    }
}
