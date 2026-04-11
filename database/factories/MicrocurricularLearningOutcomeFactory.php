<?php

namespace Database\Factories;

use App\Models\AcademicSpace;
use App\Models\MicrocurricularLearningOutcomeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MicrocurricularLearningOutcome>
 */
class MicrocurricularLearningOutcomeFactory extends Factory
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
            'type_id' => MicrocurricularLearningOutcomeType::factory(),
            'mesocurricular_learning_outcome_id' => null,
            'description' => fake()->paragraph(),
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
