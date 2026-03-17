<?php

namespace Database\Factories;

use App\Models\Upt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Upt>
 */
class UptFactory extends Factory
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
            'name' => 'UPT '.fake()->randomElement(['I', 'II', 'III', 'IV', 'V']),
            'code' => 'UPT-'.str_pad((string) $counter++, 2, '0', STR_PAD_LEFT),
            'description' => fake()->sentence(),
        ];
    }
}
