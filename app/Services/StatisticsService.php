<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Programming;
use Illuminate\Support\Collection;

class StatisticsService
{
    /**
     * Build full statistics for a programming.
     *
     * Loads all grades in 1 query with eager-loaded relations, then
     * performs every calculation in memory.
     *
     * @return array{
     *   byStudent: list<array<string,mixed>>,
     *   byOutcome: list<array<string,mixed>>,
     *   byCriterion: list<array<string,mixed>>,
     *   summary: array<string,mixed>
     * }
     */
    public function calculate(Programming $programming): array
    {
        $grades = Grade::query()
            ->whereIn('enrollment_id',
                $programming->enrollments()->where('is_active', true)->pluck('id')
            )
            ->with([
                'enrollment.student',
                'microcurricularLearningOutcome',
                'evaluationCriterion',
                'performanceLevel',
            ])
            ->get();

        $performanceLevels = $grades->pluck('performanceLevel')
            ->unique('id')
            ->sortBy('order')
            ->values();

        $byStudent = $this->byStudent($grades, $performanceLevels);
        $byOutcome = $this->byOutcome($grades, $performanceLevels);
        $byCriterion = $this->byCriterion($grades);
        $summary = $this->summary($grades, $byStudent, $performanceLevels);

        return compact('byStudent', 'byOutcome', 'byCriterion', 'summary');
    }

    /**
     * Statistics grouped by student.
     *
     * @return list<array<string,mixed>>
     */
    private function byStudent(Collection $grades, Collection $performanceLevels): array
    {
        return $grades
            ->groupBy('enrollment_id')
            ->map(function (Collection $studentGrades) {
                $enrollment = $studentGrades->first()->enrollment;
                $student = $enrollment->student;

                // Total por resultado: suma de los `order` de los criterios
                $totalsByOutcome = $studentGrades
                    ->groupBy('microcurricular_learning_outcome_id')
                    ->map(fn ($g) => $g->sum(fn ($grade) => $grade->performanceLevel->order))
                    ->values();

                $finalAverage = $totalsByOutcome->isNotEmpty()
                    ? round($totalsByOutcome->avg(), 2)
                    : 0.0;

                // Desglose por criterio: promedio del `order` en ese criterio
                $byCriterion = $studentGrades
                    ->groupBy('evaluation_criterion_id')
                    ->map(fn ($g) => [
                        'criterion_id' => $g->first()->evaluation_criterion_id,
                        'criterion_name' => $g->first()->evaluationCriterion->name,
                        'average' => round($g->avg(fn ($grade) => $grade->performanceLevel->order), 2),
                    ])
                    ->values();

                return [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $student->id,
                    'student_name' => $student->first_name.' '.$student->last_name,
                    'final_average' => $finalAverage,
                    'totals_by_outcome' => $totalsByOutcome,
                    'by_criterion' => $byCriterion,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Statistics grouped by microcurricular learning outcome.
     *
     * @return list<array<string,mixed>>
     */
    private function byOutcome(Collection $grades, Collection $performanceLevels): array
    {
        return $grades
            ->groupBy('microcurricular_learning_outcome_id')
            ->map(function (Collection $outcomeGrades) use ($performanceLevels) {
                $outcome = $outcomeGrades->first()->microcurricularLearningOutcome;

                // Total por estudiante para este resultado
                $totalsByStudent = $outcomeGrades
                    ->groupBy('enrollment_id')
                    ->map(fn ($g) => $g->sum(fn ($grade) => $grade->performanceLevel->order))
                    ->values();

                $groupAverage = $totalsByStudent->isNotEmpty()
                    ? round($totalsByStudent->avg(), 2)
                    : 0.0;

                // Distribución porcentual de niveles de desempeño
                $totalGrades = $outcomeGrades->count();
                $distribution = $performanceLevels->map(function ($level) use ($outcomeGrades, $totalGrades) {
                    $count = $outcomeGrades->where('performance_level_id', $level->id)->count();

                    return [
                        'level_id' => $level->id,
                        'level_name' => $level->name,
                        'count' => $count,
                        'percentage' => $totalGrades > 0
                            ? round(($count / $totalGrades) * 100, 1)
                            : 0.0,
                    ];
                })->values();

                return [
                    'outcome_id' => $outcome->id,
                    'outcome_desc' => $outcome->description,
                    'group_average' => $groupAverage,
                    'highest' => $totalsByStudent->max() ?? 0,
                    'lowest' => $totalsByStudent->min() ?? 0,
                    'distribution' => $distribution,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Statistics grouped by evaluation criterion.
     *
     * @return list<array<string,mixed>>
     */
    private function byCriterion(Collection $grades): array
    {
        return $grades
            ->groupBy('evaluation_criterion_id')
            ->map(function (Collection $criterionGrades) {
                $criterion = $criterionGrades->first()->evaluationCriterion;
                $average = round(
                    $criterionGrades->avg(fn ($g) => $g->performanceLevel->order),
                    2
                );

                return [
                    'criterion_id' => $criterion->id,
                    'criterion_name' => $criterion->name,
                    'group_average' => $average,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Global summary for the programming.
     *
     * @param  list<array<string,mixed>>  $byStudent
     * @return array<string,mixed>
     */
    private function summary(Collection $grades, array $byStudent, Collection $performanceLevels): array
    {
        $averages = collect($byStudent)->pluck('final_average');

        $overallAverage = $averages->isNotEmpty()
            ? round($averages->avg(), 2)
            : 0.0;

        $totalGrades = $grades->count();
        $distribution = $performanceLevels->map(function ($level) use ($grades, $totalGrades) {
            $count = $grades->where('performance_level_id', $level->id)->count();

            return [
                'level_id' => $level->id,
                'level_name' => $level->name,
                'count' => $count,
                'percentage' => $totalGrades > 0
                    ? round(($count / $totalGrades) * 100, 1)
                    : 0.0,
            ];
        })->values();

        // Nivel básico = order 2; por debajo de básico = order < 2
        $basicOrderThreshold = 2;

        $topStudents = collect($byStudent)
            ->sortByDesc('final_average')
            ->take(5)
            ->values()
            ->toArray();

        $belowBasic = collect($byStudent)
            ->filter(fn ($s) => $s['final_average'] < $basicOrderThreshold)
            ->values()
            ->toArray();

        return [
            'overall_average' => $overallAverage,
            'distribution' => $distribution,
            'top_students' => $topStudents,
            'below_basic' => $belowBasic,
        ];
    }
}
