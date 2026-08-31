<?php

namespace App\Services;

use App\Models\AcademicSpaceAnalysis;
use App\Models\EvaluationCriterion;
use App\Models\Grade;
use App\Models\PerformanceLevel;
use App\Models\Programming;
use Illuminate\Support\Collection;

/**
 * Assembles everything the institutional report needs, in one place.
 *
 * The export writes this out and the re-import reads the file back, so both
 * sides must agree on which outcomes are assessed, which sheet holds each one
 * and in what order the criteria appear. Keeping that decision here is what
 * makes the round trip close.
 */
class InstitutionalReportBuilder
{
    public function __construct(
        private readonly AcademicSpaceAnalysisService $analysisService,
    ) {}

    /**
     * @return array{
     *   programming: Programming,
     *   outcomes: list<array<string, mixed>>,
     *   students: list<array<string, mixed>>,
     *   levels: \Illuminate\Support\Collection<int, PerformanceLevel>,
     *   competencies: list<string>
     * }
     */
    public function build(Programming $programming): array
    {
        $programming->loadMissing([
            'academicSpace.competency.problematicNucleus.program.faculty',
            'academicPeriod',
            'professor',
        ]);

        $outcomes = $this->outcomes($programming);
        $grades = $this->grades($programming, $outcomes);

        return [
            'programming' => $programming,
            'outcomes' => $outcomes,
            'students' => $this->students($programming, $outcomes, $grades),
            'levels' => PerformanceLevel::orderByDesc('order')->get(),
            'competencies' => $this->competencies($programming),
        ];
    }

    /**
     * The outcomes this group is assessed on, each tied to the sheet that will
     * hold its marks and to the criteria of its type.
     *
     * A sheet is named after the outcome's type, numbered from the second
     * outcome of that type onwards, exactly as the institutional files do.
     *
     * @return list<array<string, mixed>>
     */
    public function outcomes(Programming $programming): array
    {
        $criteriaByType = $this->criteria($programming);
        $averages = $this->analysisService->outcomeAverages($programming);

        $analyses = AcademicSpaceAnalysis::query()
            ->where('programming_id', $programming->id)
            ->get()
            ->keyBy('microcurricular_learning_outcome_id');

        $seenPerType = [];
        $outcomes = [];

        foreach ($this->analysisService->analysableOutcomes($programming) as $outcome) {
            $typeName = $outcome->type?->name ?? '';
            $seenPerType[$typeName] = ($seenPerType[$typeName] ?? 0) + 1;
            $suffix = $seenPerType[$typeName] > 1 ? (string) $seenPerType[$typeName] : '';

            $outcomes[] = [
                'model' => $outcome,
                'code' => $outcome->code,
                'description' => $outcome->description,
                'type' => $typeName,
                'sheet' => trim('RA '.$typeName.$suffix),
                'criteria' => $criteriaByType[$outcome->type_id] ?? collect(),
                'average' => $averages[$outcome->id] ?? null,
                'analysis' => $analyses->get($outcome->id),
            ];
        }

        return $outcomes;
    }

    /**
     * Criteria per outcome type: the active ones plus any retired criterion the
     * group already carries marks for, so a downloaded report never drops a
     * column the professor already filled in.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, EvaluationCriterion>>
     */
    private function criteria(Programming $programming): Collection
    {
        $usedIds = Grade::query()
            ->whereIn('enrollment_id', $programming->enrollments()->pluck('id'))
            ->distinct()
            ->pluck('evaluation_criterion_id');

        return EvaluationCriterion::query()
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $usedIds))
            ->orderBy('order')
            ->get()
            ->groupBy('microcurricular_learning_outcome_type_id')
            ->map(fn (Collection $items) => $items->values());
    }

    /**
     * @param  list<array<string, mixed>>  $outcomes
     * @return \Illuminate\Support\Collection<string, Grade>
     */
    private function grades(Programming $programming, array $outcomes): Collection
    {
        $outcomeIds = array_map(fn (array $o) => $o['model']->id, $outcomes);

        return Grade::query()
            ->whereIn('enrollment_id', $programming->enrollments()->where('is_active', true)->pluck('id'))
            ->whereIn('microcurricular_learning_outcome_id', $outcomeIds)
            ->with('performanceLevel:id,name,order,grade_value')
            ->get()
            ->keyBy(fn (Grade $g) => $g->enrollment_id.'-'.$g->microcurricular_learning_outcome_id.'-'.$g->evaluation_criterion_id);
    }

    /**
     * One entry per enrolled student, carrying the level assigned in every
     * criterion of every assessed outcome and the resulting averages.
     *
     * @param  list<array<string, mixed>>  $outcomes
     * @param  \Illuminate\Support\Collection<string, Grade>  $grades
     * @return list<array<string, mixed>>
     */
    private function students(Programming $programming, array $outcomes, Collection $grades): array
    {
        $enrollments = $programming->enrollments()
            ->where('is_active', true)
            ->with('student')
            ->get()
            ->filter(fn ($enrollment) => $enrollment->student !== null)
            ->sortBy(fn ($enrollment) => mb_strtolower(
                $enrollment->student->last_name.' '.$enrollment->student->first_name
            ));

        $students = [];

        foreach ($enrollments as $enrollment) {
            $perOutcome = [];

            foreach ($outcomes as $outcome) {
                $marks = [];
                $values = [];

                foreach ($outcome['criteria'] as $criterion) {
                    $grade = $grades->get(
                        $enrollment->id.'-'.$outcome['model']->id.'-'.$criterion->id
                    );

                    $marks[] = $grade?->performanceLevel;

                    $value = $grade === null
                        ? null
                        : PerformanceLevel::gradeForOrder($grade->performanceLevel->order);

                    if ($value !== null) {
                        $values[] = $value;
                    }
                }

                $perOutcome[$outcome['code']] = [
                    'marks' => $marks,
                    'average' => $values === [] ? null : round(array_sum($values) / count($values), 2),
                ];
            }

            $outcomeAverages = array_values(array_filter(
                array_column($perOutcome, 'average'),
                fn ($value) => $value !== null
            ));

            $students[] = [
                'enrollment_id' => $enrollment->id,
                'document' => $enrollment->student->document_number,
                'name' => trim($enrollment->student->first_name.' '.$enrollment->student->last_name),
                'outcomes' => $perOutcome,
                'average' => $outcomeAverages === []
                    ? null
                    : round(array_sum($outcomeAverages) / count($outcomeAverages), 2),
            ];
        }

        return $students;
    }

    /**
     * Competencies the academic space contributes to.
     *
     * @return list<string>
     */
    private function competencies(Programming $programming): array
    {
        $competency = $programming->academicSpace?->competency;

        if (! $competency) {
            return [];
        }

        return [trim(($competency->code ? $competency->code.'. ' : '').$competency->description)];
    }

    /**
     * File name following the convention of the delivered files.
     */
    public function fileName(Programming $programming): string
    {
        // Accented letters are transliterated rather than dropped, so
        // "Lógica" does not become "Lgica" in the file name.
        $space = preg_replace(
            '/[^A-Za-z0-9]+/u',
            '',
            strtr($programming->academicSpace?->name ?? 'EA', [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u',
                'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N', 'Ü' => 'U',
            ])
        );

        $group = preg_replace('/[^A-Za-z0-9]+/u', '', (string) $programming->group);
        $period = $programming->academicPeriod?->name ?? '';

        return sprintf('IAP_SOF_%s_Grupo%s_%s.xlsx', $space, $group ?: 'SN', $period);
    }
}
