<?php

namespace App\Services;

use App\Models\AcademicSpaceAnalysis;
use App\Models\Grade;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\PerformanceLevel;
use App\Models\Programming;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicSpaceAnalysisService
{
    /**
     * Outcomes a programming may be analysed against.
     *
     * The institutional format asks for one analysis block per assessed outcome,
     * so the list is the active outcomes of the academic space plus any retired
     * one this group already has grades for: hiding the latter would drop an
     * analysis the professor already wrote.
     *
     * @return Collection<int, MicrocurricularLearningOutcome>
     */
    public function analysableOutcomes(Programming $programming): Collection
    {
        $gradedOutcomeIds = Grade::query()
            ->whereIn('enrollment_id', $programming->enrollments()->pluck('id'))
            ->distinct()
            ->pluck('microcurricular_learning_outcome_id');

        return MicrocurricularLearningOutcome::query()
            ->where('academic_space_id', $programming->academic_space_id)
            ->where(fn ($q) => $q
                ->where('is_active', true)
                ->orWhereIn('id', $gradedOutcomeIds)
            )
            ->with('type:id,name')
            ->orderBy('type_id')
            ->orderBy('id')
            ->get(['id', 'code', 'description', 'type_id']);
    }

    /**
     * Group average for each outcome, so the professor writes the analysis with
     * the number in front of them, the way the spreadsheet presents it.
     *
     * Returns null for an outcome with no grades yet, which the interface shows
     * as "sin calificar" rather than as a zero.
     *
     * @return array<int, float|null>
     */
    public function outcomeAverages(Programming $programming): array
    {
        $grades = Grade::query()
            ->whereIn(
                'enrollment_id',
                $programming->enrollments()->where('is_active', true)->pluck('id')
            )
            ->with('performanceLevel:id,order')
            ->get(['enrollment_id', 'microcurricular_learning_outcome_id', 'performance_level_id']);

        return $grades
            ->groupBy('microcurricular_learning_outcome_id')
            ->map(function (Collection $outcomeGrades) {
                // Average per student first, then across students, matching how
                // StatisticsService reports the group average for an outcome.
                $perStudent = $outcomeGrades
                    ->groupBy('enrollment_id')
                    ->map(function (Collection $studentGrades) {
                        $values = $studentGrades
                            ->map(fn (Grade $g) => PerformanceLevel::gradeForOrder($g->performanceLevel->order))
                            ->filter(fn ($v) => $v !== null);

                        return $values->isEmpty() ? null : $values->avg();
                    })
                    ->filter(fn ($v) => $v !== null);

                return $perStudent->isEmpty() ? null : round($perStudent->avg(), 2);
            })
            ->all();
    }

    /**
     * Save or update the analysis of each outcome in one transaction.
     *
     * Entries are rejected when the outcome does not belong to the programming,
     * so a crafted payload cannot attach an analysis to another group's outcome.
     *
     * @param  array<int, array{microcurricular_learning_outcome_id: int, outcome_performance?: string|null, academic_space_performance?: string|null, improvement_proposals?: string|null}>  $analyses
     *
     * @throws ValidationException
     */
    public function save(array $analyses, Programming $programming, int $writtenByUserId): void
    {
        $allowedIds = $this->analysableOutcomes($programming)->pluck('id')->flip();

        foreach ($analyses as $analysis) {
            if (! $allowedIds->has($analysis['microcurricular_learning_outcome_id'])) {
                throw ValidationException::withMessages([
                    'analyses' => 'Uno de los resultados de aprendizaje no pertenece a esta programación.',
                ]);
            }
        }

        DB::transaction(function () use ($analyses, $programming, $writtenByUserId) {
            foreach ($analyses as $analysis) {
                AcademicSpaceAnalysis::updateOrCreate(
                    [
                        'programming_id' => $programming->id,
                        'microcurricular_learning_outcome_id' => $analysis['microcurricular_learning_outcome_id'],
                    ],
                    [
                        'outcome_performance' => $this->normalize($analysis['outcome_performance'] ?? null),
                        'academic_space_performance' => $this->normalize($analysis['academic_space_performance'] ?? null),
                        'improvement_proposals' => $this->normalize($analysis['improvement_proposals'] ?? null),
                        'written_by' => $writtenByUserId,
                    ]
                );
            }
        });
    }

    /**
     * Outcomes that still have no analysis written.
     *
     * The interface uses this to point out what is pending without blocking any
     * action: writing the analysis is optional.
     *
     * @return list<int>
     */
    public function pendingOutcomeIds(Programming $programming): array
    {
        $written = $programming->academicSpaceAnalyses()
            ->get()
            ->filter(fn (AcademicSpaceAnalysis $a) => $a->hasContent())
            ->pluck('microcurricular_learning_outcome_id')
            ->flip();

        return $this->analysableOutcomes($programming)
            ->reject(fn (MicrocurricularLearningOutcome $o) => $written->has($o->id))
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * An empty answer is stored as null rather than as an empty string, so the
     * "written" check stays a single comparison.
     */
    private function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
