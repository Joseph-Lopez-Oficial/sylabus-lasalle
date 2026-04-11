<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\EvaluationCriterion;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\PerformanceLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'microcurricular_learning_outcome_id' => MicrocurricularLearningOutcome::factory(),
            'evaluation_criterion_id' => EvaluationCriterion::factory(),
            'performance_level_id' => PerformanceLevel::factory(),
            'graded_by' => User::factory()->admin(),
            'observations' => fake()->optional()->sentence(),
            'graded_at' => now(),
        ];
    }
}
