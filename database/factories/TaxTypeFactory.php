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
        return [
            'name' => fake()->unique()->word() . ' Tax',
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'simpadu_code' => fake()->unique()->numerify('1.1.1.##.##'),
        ];
    }
}
