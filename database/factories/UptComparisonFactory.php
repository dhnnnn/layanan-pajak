<?php

namespace Database\Factories;

use App\Models\TaxType;
use App\Models\Upt;
use App\Models\UptComparison;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UptComparison>
 */
class UptComparisonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tax_type_id' => TaxType::factory(),
            'upt_id' => Upt::factory(),
            'year' => (int) date('Y'),
            'target_amount' => fake()->randomFloat(2, 1000000, 100000000),
        ];
    }
}
