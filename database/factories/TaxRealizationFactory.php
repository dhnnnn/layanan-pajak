<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\TaxRealization;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRealization>
 */
class TaxRealizationFactory extends Factory
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
            'district_id' => District::factory(),
            'user_id' => User::factory(),
            'year' => fake()->numberBetween(2020, 2030),
            'january' => fake()->randomFloat(2, 0, 100_000_000),
            'february' => fake()->randomFloat(2, 0, 100_000_000),
            'march' => fake()->randomFloat(2, 0, 100_000_000),
            'april' => fake()->randomFloat(2, 0, 100_000_000),
            'may' => fake()->randomFloat(2, 0, 100_000_000),
            'june' => fake()->randomFloat(2, 0, 100_000_000),
            'july' => fake()->randomFloat(2, 0, 100_000_000),
            'august' => fake()->randomFloat(2, 0, 100_000_000),
            'september' => fake()->randomFloat(2, 0, 100_000_000),
            'october' => fake()->randomFloat(2, 0, 100_000_000),
            'november' => fake()->randomFloat(2, 0, 100_000_000),
            'december' => fake()->randomFloat(2, 0, 100_000_000),
        ];
    }
}
