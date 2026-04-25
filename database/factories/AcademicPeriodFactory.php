<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicPeriod>
 */
class AcademicPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;
        $year = 2024 + intdiv($counter - 1, 2);
        $semester = ($counter % 2 === 1) ? '1' : '2';

        return [
            'name' => "{$year}-{$semester}",
            'description' => null,
            'start_date' => null,
            'end_date' => null,
            'is_active' => true,
        ];
    }
}
