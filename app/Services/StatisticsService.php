<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Programming;
use Illuminate\Support\Collection;

class StatisticsService
{
    // Maps performance level order to institutional grade
    private const ORDER_TO_GRADE = [
        1 => 1.3,
        2 => 2.5,
        3 => 3.8,
        4 => 5.0,
    ];

    private function orderToGrade(int $order): float
    {
        return self::ORDER_TO_GRADE[$order] ?? (float) $order;
    }

    /**
     * Build full statistics for a programming.
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
            ->whereIn(
                'enrollment_id',
                $programming->enrollments()->where('is_active', true)->pluck('id')
            )
            ->with([
                'enrollment.student',
                'microcurricularLearningOutcome.type',
                'evaluationCriterion.outcomeType',
                'performanceLevel',
            ])
            ->get();

        $performanceLevels = $grades->pluck('performanceLevel')
            ->unique('id')
            ->sortBy('order')
            ->values();

        $byStudent = $this->byStudent($grades);
        $byOutcome = $this->byOutcome($grades, $performanceLevels);
        $byCriterion = $this->byCriterion($grades);
        $summary = $this->summary($byStudent, $performanceLevels, $grades);

        return compact('byStudent', 'byOutcome', 'byCriterion', 'summary');
    }

    /**
     * Statistics grouped by student.
     * Each criterion average is the real institutional grade (1.3/2.5/3.8/5.0).
     * Each outcome average is the avg of its criteria grades.
     * Final average is the avg of outcome averages.
     */
    private function byStudent(Collection $grades): array
    {
        return $grades
            ->groupBy('enrollment_id')
            ->map(function (Collection $studentGrades) {
                $enrollment = $studentGrades->first()->enrollment;
                $student = $enrollment->student;

                // For each outcome: avg grade of its criteria (real scale)
                $gradesByOutcome = $studentGrades
                    ->groupBy('microcurricular_learning_outcome_id')
                    ->map(function ($outcomeGrades) {
                        $outcome = $outcomeGrades->first()->microcurricularLearningOutcome;
                        $avgGrade = round(
                            $outcomeGrades->avg(fn($g) => $this->orderToGrade($g->performanceLevel->order)),
                            2
                        );

                        // Per-criterion breakdown within this outcome for this student
                        $byCriterion = $outcomeGrades->map(fn($g) => [
                            'criterion_id' => $g->evaluation_criterion_id,
                            'criterion_name' => $g->evaluationCriterion->name,
                            'grade' => $this->orderToGrade($g->performanceLevel->order),
                            'level_name' => $g->performanceLevel->name,
                        ])->values()->toArray();

                        return [
                            'outcome_id' => $outcome->id,
                            'outcome_code' => $outcome->code,
                            'outcome_desc' => $outcome->description,
                            'type_id' => $outcome->type_id,
                            'type_name' => $outcome->type?->name,
                            'grade' => $avgGrade,
                            'by_criterion' => $byCriterion,
                        ];
                    })
                    ->values();

                $finalAverage = $gradesByOutcome->isNotEmpty()
                    ? round($gradesByOutcome->avg('grade'), 2)
                    : 0.0;

                return [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'final_average' => $finalAverage,
                    'by_outcome' => $gradesByOutcome->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Statistics grouped by outcome.
     * group_average = avg of per-student outcome grades (real scale).
     * distribution = count of students whose dominant level in this outcome is X.
     */
    private function byOutcome(Collection $grades, Collection $performanceLevels): array
    {
        return $grades
            ->groupBy('microcurricular_learning_outcome_id')
            ->map(function (Collection $outcomeGrades) use ($performanceLevels) {
                $outcome = $outcomeGrades->first()->microcurricularLearningOutcome;

                // Per-student: avg grade across criteria for this outcome
                $gradesByStudent = $outcomeGrades
                    ->groupBy('enrollment_id')
                    ->map(fn($g) => round(
                        $g->avg(fn($grade) => $this->orderToGrade($grade->performanceLevel->order)),
                        2
                    ))
                    ->values();

                $groupAverage = $gradesByStudent->isNotEmpty()
                    ? round($gradesByStudent->avg(), 2)
                    : 0.0;

                $highest = $gradesByStudent->max() ?? 0.0;
                $lowest = $gradesByStudent->min() ?? 0.0;

                // Distribution: total individual grades per level (criterion × student)
                $totalGrades = $outcomeGrades->count();
                $totalStudents = $outcomeGrades->groupBy('enrollment_id')->count();
                $distribution = $performanceLevels->map(function ($level) use ($outcomeGrades, $totalGrades, $totalStudents) {
                    $gradeCount = $outcomeGrades->where('performance_level_id', $level->id)->count();
                    $studentCount = $outcomeGrades->where('performance_level_id', $level->id)->unique('enrollment_id')->count();

                    return [
                        'level_id' => $level->id,
                        'level_name' => $level->name,
                        'count' => $gradeCount,           // total individual grades at this level
                        'student_count' => $studentCount, // distinct students with at least one grade at this level
                        'percentage' => $totalGrades > 0
                            ? round(($gradeCount / $totalGrades) * 100, 1)
                            : 0.0,
                        'student_percentage' => $totalStudents > 0
                            ? round(($studentCount / $totalStudents) * 100, 1)
                            : 0.0,
                    ];
                })->values();

                return [
                    'outcome_id' => $outcome->id,
                    'outcome_desc' => $outcome->description,
                    'outcome_code' => $outcome->code,
                    'type_id' => $outcome->type_id,
                    'type_name' => $outcome->type?->name,
                    'group_average' => $groupAverage,
                    'highest' => $highest,
                    'lowest' => $lowest,
                    'distribution' => $distribution,
                    'grades_by_student' => $gradesByStudent,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Statistics grouped by criterion.
     * group_average = avg real grade of all students in that criterion.
     */
    private function byCriterion(Collection $grades): array
    {
        return $grades
            ->groupBy('evaluation_criterion_id')
            ->map(function (Collection $criterionGrades) {
                $criterion = $criterionGrades->first()->evaluationCriterion;
                $average = round(
                    $criterionGrades->avg(fn($g) => $this->orderToGrade($g->performanceLevel->order)),
                    2
                );

                // Per RA that uses this criterion: group average + per-student grades
                $byOutcome = $criterionGrades
                    ->groupBy('microcurricular_learning_outcome_id')
                    ->map(function (Collection $outcomeGrades) {
                        $outcome = $outcomeGrades->first()->microcurricularLearningOutcome;
                        $outcomeAvg = round(
                            $outcomeGrades->avg(fn($g) => $this->orderToGrade($g->performanceLevel->order)),
                            2
                        );

                        $students = $outcomeGrades->map(fn($g) => [
                            'student_name' => $g->enrollment->student->first_name . ' ' . $g->enrollment->student->last_name,
                            'grade' => $this->orderToGrade($g->performanceLevel->order),
                            'level_name' => $g->performanceLevel->name,
                        ])->sortByDesc('grade')->values()->toArray();

                        return [
                            'outcome_id' => $outcome->id,
                            'outcome_code' => $outcome->code,
                            'outcome_desc' => $outcome->description,
                            'group_average' => $outcomeAvg,
                            'students' => $students,
                        ];
                    })
                    ->values()
                    ->toArray();

                return [
                    'criterion_id' => $criterion->id,
                    'criterion_name' => $criterion->name,
                    'type_id' => $criterion->microcurricular_learning_outcome_type_id,
                    'type_name' => $criterion->outcomeType?->name,
                    'group_average' => $average,
                    'by_outcome' => $byOutcome,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Global summary.
     * overall_average = avg of student final averages (real scale).
     * distribution = count of students per level based on their dominant final level.
     * below_basic = students with final_average < 2.5 (nivel Básico).
     */
    private function summary(array $byStudent, Collection $performanceLevels, Collection $grades): array
    {
        $averages = collect($byStudent)->pluck('final_average');

        $overallAverage = $averages->isNotEmpty()
            ? round($averages->avg(), 2)
            : 0.0;

        // Distribution by individual grades (count of all criterion-level assignments)
        $totalGrades = $grades->count();
        $totalStudents = $grades->unique('enrollment_id')->count();
        $distribution = $performanceLevels->map(function ($level) use ($grades, $totalGrades, $totalStudents) {
            $gradeCount = $grades->where('performance_level_id', $level->id)->count();
            $studentCount = $grades->where('performance_level_id', $level->id)->unique('enrollment_id')->count();

            return [
                'level_id' => $level->id,
                'level_name' => $level->name,
                'count' => $gradeCount,
                'student_count' => $studentCount,
                'percentage' => $totalGrades > 0
                    ? round(($gradeCount / $totalGrades) * 100, 1)
                    : 0.0,
                'student_percentage' => $totalStudents > 0
                    ? round(($studentCount / $totalStudents) * 100, 1)
                    : 0.0,
            ];
        })->values();

        $topStudents = collect($byStudent)
            ->sortByDesc('final_average')
            ->take(5)
            ->values()
            ->toArray();

        // below_basic = final_average < 2.5 (the real grade for Básico)
        $belowBasic = collect($byStudent)
            ->filter(fn($s) => $s['final_average'] < 2.5)
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
