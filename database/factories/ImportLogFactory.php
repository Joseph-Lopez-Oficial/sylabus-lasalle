<?php

namespace Database\Factories;

use App\Models\Programming;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportLog>
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
        $totalRows = fake()->numberBetween(10, 100);
        $failedRows = fake()->numberBetween(0, (int) ($totalRows * 0.1));
        $successfulRows = $totalRows - $failedRows;

        return [
            'imported_by' => User::factory(),
            'programming_id' => Programming::factory(),
            'file_name' => fake()->word().'.xlsx',
            'total_rows' => $totalRows,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'errors' => $failedRows > 0 ? [['row' => 1, 'message' => 'Student not found']] : null,
            'status' => 'completed',
            'imported_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'imported_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
