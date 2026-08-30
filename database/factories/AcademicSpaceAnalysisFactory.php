<?php

namespace Database\Factories;

use App\Models\MicrocurricularLearningOutcome;
use App\Models\Programming;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicSpaceAnalysis>
 */
class AcademicSpaceAnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'programming_id' => Programming::factory(),
            'microcurricular_learning_outcome_id' => MicrocurricularLearningOutcome::factory(),
            'outcome_performance' => fake()->paragraph(),
            'academic_space_performance' => fake()->paragraph(),
            'improvement_proposals' => fake()->paragraph(),
            'written_by' => User::factory(),
        ];
    }

    /**
     * An analysis with every answer cleared, to exercise the pending state.
     */
    public function empty(): static
    {
        return $this->state(fn () => [
            'outcome_performance' => null,
            'academic_space_performance' => null,
            'improvement_proposals' => null,
        ]);
    }
}
