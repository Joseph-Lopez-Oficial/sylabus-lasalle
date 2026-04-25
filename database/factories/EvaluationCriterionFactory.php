<?php

namespace Database\Factories;

use App\Models\MicrocurricularLearningOutcomeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EvaluationCriterion>
 */
class EvaluationCriterionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'microcurricular_learning_outcome_type_id' => MicrocurricularLearningOutcomeType::factory(),
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
