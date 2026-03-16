<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportLog>
 */
class ImportLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'file_name' => fake()->word().'_realisasi_'.fake()->year().'.xlsx',
            'status' => ImportStatus::Pending,
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the import completed successfully.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes): array {
            $totalRows = fake()->numberBetween(10, 100);

            return [
                'status' => ImportStatus::Completed,
                'total_rows' => $totalRows,
                'success_rows' => $totalRows,
                'failed_rows' => 0,
            ];
        });
    }

    /**
     * Indicate that the import failed.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes): array {
            $totalRows = fake()->numberBetween(10, 100);
            $failedRows = fake()->numberBetween(1, $totalRows);

            return [
                'status' => ImportStatus::Failed,
                'total_rows' => $totalRows,
                'success_rows' => $totalRows - $failedRows,
                'failed_rows' => $failedRows,
                'notes' => fake()->sentence(),
            ];
        });
    }

    /**
     * Indicate that the import is currently processing.
     */
    public function processing(): static
    {
        return $this->state(
            fn (array $attributes): array => [
                'status' => ImportStatus::Processing,
                'total_rows' => fake()->numberBetween(10, 100),
            ],
        );
    }
}
