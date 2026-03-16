<?php

namespace Database\Factories;

use App\Models\TaxTarget;
use App\Models\TaxType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxTarget>
 */
class TaxTargetFactory extends Factory
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
            'year' => fake()->numberBetween(2020, 2030),
            'target_amount' => fake()->randomFloat(
                2,
                100_000_000,
                10_000_000_000,
            ),
        ];
    }
}
