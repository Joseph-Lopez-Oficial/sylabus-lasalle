<?php

namespace App\Services;

use App\Models\EvaluationCriterion;
use App\Models\Grade;
use App\Models\MicrocurricularLearningOutcome;
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

        $outcomes = MicrocurricularLearningOutcome::query()
            ->where('academic_space_id', $programming->academic_space_id)
            ->where('is_active', true)
            ->get(['id', 'type_id']);

        if ($enrollmentIds->isEmpty() || $outcomes->isEmpty()) {
            return ['percentage' => 100.0, 'total' => 0, 'completed' => 0, 'pending' => []];
        }

        // Build map of type_id => [criterion_ids]
        $criteriaByType = EvaluationCriterion::whereIn(
            'microcurricular_learning_outcome_type_id',
            $outcomes->pluck('type_id')->unique()
        )->orderBy('order')->get(['id', 'microcurricular_learning_outcome_type_id']);

        $criterionsByType = $criteriaByType->groupBy('microcurricular_learning_outcome_type_id')
            ->map(fn($items) => $items->pluck('id'));

        // Compute all required combinations
        $allCriterionIds = $criteriaByType->pluck('id');
        $outcomeIds = $outcomes->pluck('id');

        $existingGrades = Grade::whereIn('enrollment_id', $enrollmentIds)
            ->whereIn('microcurricular_learning_outcome_id', $outcomeIds)
            ->whereIn('evaluation_criterion_id', $allCriterionIds)
            ->get(['enrollment_id', 'microcurricular_learning_outcome_id', 'evaluation_criterion_id']);

        $existingSet = $existingGrades->map(
            fn($g) => "{$g->enrollment_id}-{$g->microcurricular_learning_outcome_id}-{$g->evaluation_criterion_id}"
        )->flip();

        $pending = [];
        $total = 0;

        foreach ($enrollmentIds as $enrollmentId) {
            foreach ($outcomes as $outcome) {
                $typeCriterionIds = $criterionsByType[$outcome->type_id] ?? collect();
                foreach ($typeCriterionIds as $criterionId) {
                    $total++;
                    $key = "{$enrollmentId}-{$outcome->id}-{$criterionId}";
                    if (! $existingSet->has($key)) {
                        $pending[] = [
                            'enrollment_id' => $enrollmentId,
                            'microcurricular_learning_outcome_id' => $outcome->id,
                            'evaluation_criterion_id' => $criterionId,
                        ];
                    }
                }
            }
        }

        if ($total === 0) {
            return ['percentage' => 100.0, 'total' => 0, 'completed' => 0, 'pending' => []];
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
