<?php

namespace Database\Factories;

use App\Models\AcademicSpace;
use App\Models\Modality;
use App\Models\Professor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Programming>
 */
class ProgrammingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_space_id' => AcademicSpace::factory(),
            'professor_id' => Professor::factory(),
            'modality_id' => Modality::factory(),
            'period' => fake()->randomElement(['2024-I', '2024-II', '2025-I', '2025-II']),
            'group' => fake()->randomElement(['A', 'B', 'C', null]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
