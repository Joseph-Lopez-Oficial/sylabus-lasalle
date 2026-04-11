<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Programming;
use Illuminate\Support\Facades\DB;

class GradingService
{
    /**
     * Save or update a batch of grades atomically.
     *
     * Each entry in $grades must contain:
     *   - enrollment_id
     *   - microcurricular_learning_outcome_id
     *   - evaluation_criterion_id
     *   - performance_level_id
     *   - observations (optional)
     *
     * @param  array<int, array{enrollment_id: int, microcurricular_learning_outcome_id: int, evaluation_criterion_id: int, performance_level_id: int, observations?: string|null}>  $grades
     */
    public function saveGrades(array $grades, int $gradedByUserId): void
    {
        DB::transaction(function () use ($grades, $gradedByUserId) {
            foreach ($grades as $gradeData) {
                Grade::updateOrCreate(
                    [
                        'enrollment_id' => $gradeData['enrollment_id'],
                        'microcurricular_learning_outcome_id' => $gradeData['microcurricular_learning_outcome_id'],
                        'evaluation_criterion_id' => $gradeData['evaluation_criterion_id'],
                    ],
                    [
                        'performance_level_id' => $gradeData['performance_level_id'],
                        'graded_by' => $gradedByUserId,
                        'observations' => $gradeData['observations'] ?? null,
                        'graded_at' => now(),
                    ]
                );
            }
        });
    }

    /**
     * Calculate grading completeness for a programming.
     *
     * Returns the percentage of grades completed and the list of pending
     * combinations (enrollment_id × outcome_id × criterion_id).
     *
     * @return array{percentage: float, total: int, completed: int, pending: list<array{enrollment_id: int, microcurricular_learning_outcome_id: int, evaluation_criterion_id: int}>}
     */
    public function completeness(Programming $programming): array
    {
        $enrollmentIds = $programming->enrollments()
            ->where('is_active', true)
            ->pluck('id');

        $outcomeIds = $programming->academicSpace
            ->microcurricularLearningOutcomes()
            ->where('is_active', true)
            ->pluck('id');

        $criterionIds = \App\Models\EvaluationCriterion::orderBy('order')->pluck('id');

        $total = $enrollmentIds->count() * $outcomeIds->count() * $criterionIds->count();

        if ($total === 0) {
            return ['percentage' => 100.0, 'total' => 0, 'completed' => 0, 'pending' => []];
        }

        $existingGrades = Grade::whereIn('enrollment_id', $enrollmentIds)
            ->whereIn('microcurricular_learning_outcome_id', $outcomeIds)
            ->whereIn('evaluation_criterion_id', $criterionIds)
            ->get(['enrollment_id', 'microcurricular_learning_outcome_id', 'evaluation_criterion_id']);

        $existingSet = $existingGrades->map(
            fn ($g) => "{$g->enrollment_id}-{$g->microcurricular_learning_outcome_id}-{$g->evaluation_criterion_id}"
        )->flip();

        $pending = [];

        foreach ($enrollmentIds as $enrollmentId) {
            foreach ($outcomeIds as $outcomeId) {
                foreach ($criterionIds as $criterionId) {
                    $key = "{$enrollmentId}-{$outcomeId}-{$criterionId}";
                    if (! $existingSet->has($key)) {
                        $pending[] = [
                            'enrollment_id' => $enrollmentId,
                            'microcurricular_learning_outcome_id' => $outcomeId,
                            'evaluation_criterion_id' => $criterionId,
                        ];
                    }
                }
            }
        }

        $completed = $total - count($pending);
        $percentage = round(($completed / $total) * 100, 2);

        return [
            'percentage' => $percentage,
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
        ];
    }
}
