<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\TaxRealization;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaxRealizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxTypes = TaxType::query()->get();
        $districts = District::query()->get();
        $user = User::query()->role('pegawai')->first();

        if (! $user) {
            return;
        }

        foreach ([2024, 2025, 2026] as $year) {
            foreach ($districts as $district) {
                foreach ($taxTypes as $taxType) {
                    // Generate random monthly realization (realistic but random)
                    TaxRealization::query()->create([
                        'tax_type_id' => $taxType->id,
                        'district_id' => $district->id,
                        'user_id' => $user->id,
                        'year' => $year,
                        'january' => fake()->numberBetween(100_000, 1_000_000),
                        'february' => fake()->numberBetween(100_000, 1_000_000),
                        'march' => fake()->numberBetween(100_000, 1_000_000),
                        'april' => fake()->numberBetween(100_000, 1_000_000),
                        'may' => fake()->numberBetween(100_000, 1_000_000),
                        'june' => fake()->numberBetween(100_000, 1_000_000),
                        'july' => fake()->numberBetween(100_000, 1_000_000),
                        'august' => fake()->numberBetween(100_000, 1_000_000),
                        'september' => fake()->numberBetween(100_000, 1_000_000),
                        'october' => fake()->numberBetween(100_000, 1_000_000),
                        'november' => fake()->numberBetween(100_000, 1_000_000),
                        'december' => fake()->numberBetween(100_000, 1_000_000),
                    ]);
                }
            }
        }
    }
}
