<?php

namespace App\Services;

use App\Models\EvaluationCriterion;
use App\Models\Grade;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\Programming;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradingService
{
    /**
     * Save or update a batch of grades atomically.
     *
     * Every entry is validated against the programming before being written:
     * the enrollment must belong to it, the outcome must belong to its academic
     * space, and the criterion must match the outcome's type. This is the single
     * choke point for both manual grading and Excel import.
     *
     * Each entry in $grades must contain:
     *   - enrollment_id
     *   - microcurricular_learning_outcome_id
     *   - evaluation_criterion_id
     *   - performance_level_id
     *   - observations (optional)
     *
     * @param  array<int, array{enrollment_id: int, microcurricular_learning_outcome_id: int, evaluation_criterion_id: int, performance_level_id: int, observations?: string|null}>  $grades
     *
     * @throws ValidationException When any entry does not belong to the programming.
     */
    public function saveGrades(array $grades, int $gradedByUserId, Programming $programming): void
    {
        $this->assertGradesBelongToProgramming($grades, $programming);

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
                    ]
                );
            }
        });
    }

    /**
     * Reject any grade whose enrollment, outcome or criterion does not belong
     * to the given programming.
     *
     * Without this guard a professor could post another programming's
     * enrollment id and overwrite grades they do not own, or pair an outcome
     * with a criterion of a foreign type and corrupt every statistic derived
     * from it.
     *
     * @param  array<int, array<string, mixed>>  $grades
     *
     * @throws ValidationException
     */
    private function assertGradesBelongToProgramming(array $grades, Programming $programming): void
    {
        if ($grades === []) {
            return;
        }

        $validEnrollmentIds = $programming->enrollments()
            ->where('is_active', true)
            ->pluck('id')
            ->flip();

        $outcomeTypeById = MicrocurricularLearningOutcome::query()
            ->where('academic_space_id', $programming->academic_space_id)
            ->where('is_active', true)
            ->pluck('type_id', 'id');

        $criterionTypeById = EvaluationCriterion::query()
            ->pluck('microcurricular_learning_outcome_type_id', 'id');

        $errors = [];

        foreach ($grades as $index => $gradeData) {
            $enrollmentId = (int) ($gradeData['enrollment_id'] ?? 0);
            $outcomeId = (int) ($gradeData['microcurricular_learning_outcome_id'] ?? 0);
            $criterionId = (int) ($gradeData['evaluation_criterion_id'] ?? 0);

            if (! $validEnrollmentIds->has($enrollmentId)) {
                $errors["grades.{$index}.enrollment_id"] = 'La inscripción no pertenece a esta programación.';

                continue;
            }

            if (! $outcomeTypeById->has($outcomeId)) {
                $errors["grades.{$index}.microcurricular_learning_outcome_id"] = 'El resultado microcurricular no pertenece a esta programación.';

                continue;
            }

            if ($criterionTypeById->get($criterionId) !== $outcomeTypeById->get($outcomeId)) {
                $errors["grades.{$index}.evaluation_criterion_id"] = 'El criterio de evaluación no corresponde al tipo del resultado microcurricular.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
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
            ->map(fn ($items) => $items->pluck('id'));

        // Compute all required combinations
        $allCriterionIds = $criteriaByType->pluck('id');
        $outcomeIds = $outcomes->pluck('id');

        $existingGrades = Grade::whereIn('enrollment_id', $enrollmentIds)
            ->whereIn('microcurricular_learning_outcome_id', $outcomeIds)
            ->whereIn('evaluation_criterion_id', $allCriterionIds)
            ->get(['enrollment_id', 'microcurricular_learning_outcome_id', 'evaluation_criterion_id']);

        $existingSet = $existingGrades->map(
            fn ($g) => "{$g->enrollment_id}-{$g->microcurricular_learning_outcome_id}-{$g->evaluation_criterion_id}"
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
