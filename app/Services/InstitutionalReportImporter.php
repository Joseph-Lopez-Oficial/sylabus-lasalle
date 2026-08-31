<?php

namespace App\Services;

use App\Models\AcademicSpaceAnalysis;
use App\Models\PerformanceLevel;
use App\Models\Programming;
use App\Services\Import\MeasurementFileReader;
use App\Services\Import\MeasurementNormalizer;
use RuntimeException;

/**
 * Reads back a report the system produced and filled in by hand.
 *
 * The file travels out through InstitutionalReportExport and comes back here,
 * so both use the same reader the historical load used: the layout is the same,
 * and what worked for the coordination's own files works for ours.
 *
 * Nothing is written until the file is confirmed to belong to the programming
 * it is being uploaded against.
 */
class InstitutionalReportImporter
{
    public function __construct(
        private readonly MeasurementFileReader $reader,
        private readonly MeasurementNormalizer $normalizer,
        private readonly InstitutionalReportBuilder $builder,
        private readonly GradingService $gradingService,
    ) {}

    /**
     * @return array{
     *   saved: int, skipped: int,
     *   errors: list<array{sheet: string, row: string, message: string}>,
     *   analysis_conflicts: list<array{outcome_code: string, stored: ?string, incoming: string, field: string}>
     * }
     */
    public function import(string $path, Programming $programming, int $userId): array
    {
        $data = $this->reader->read($path);

        $this->assertBelongsToProgramming($data, $programming);

        $report = $this->builder->build($programming);

        $enrollmentByDocument = [];
        foreach ($report['students'] as $student) {
            $enrollmentByDocument[$student['document']] = $student['enrollment_id'];
        }

        $outcomeByCode = [];
        $criteriaByCode = [];
        foreach ($report['outcomes'] as $outcome) {
            $outcomeByCode[$outcome['code']] = $outcome['model'];
            $criteriaByCode[$outcome['code']] = $outcome['criteria'];
        }

        $levelsByOrder = PerformanceLevel::pluck('id', 'order');

        $payload = [];
        $errors = [];
        $skipped = 0;

        foreach ($data['grades'] as $grade) {
            $outcome = $outcomeByCode[$grade['outcome_code']] ?? null;
            $enrollmentId = $enrollmentByDocument[$grade['document']] ?? null;

            if (! $outcome) {
                $errors[] = [
                    'sheet' => 'RA '.$grade['outcome_code'],
                    'row' => $grade['document'],
                    'message' => "El resultado de aprendizaje {$grade['outcome_code']} no pertenece a esta programación.",
                ];
                $skipped++;

                continue;
            }

            if ($enrollmentId === null) {
                $errors[] = [
                    'sheet' => $grade['outcome_code'],
                    'row' => $grade['document'],
                    'message' => "El estudiante con documento {$grade['document']} no está inscrito en esta programación.",
                ];
                $skipped++;

                continue;
            }

            $criterion = ($criteriaByCode[$grade['outcome_code']] ?? collect())->get($grade['criterion_index']);
            $levelId = $levelsByOrder[$grade['level_order']] ?? null;

            if (! $criterion || $levelId === null) {
                $errors[] = [
                    'sheet' => $grade['outcome_code'],
                    'row' => $grade['document'],
                    'message' => 'No se reconoció el criterio o el nivel de esta calificación.',
                ];
                $skipped++;

                continue;
            }

            $payload[] = [
                'enrollment_id' => $enrollmentId,
                'microcurricular_learning_outcome_id' => $outcome->id,
                'evaluation_criterion_id' => $criterion->id,
                'performance_level_id' => $levelId,
            ];
        }

        if ($payload !== []) {
            // The same choke point the interface writes through, so the upload
            // is subject to the very ownership checks a professor's saves are.
            $this->gradingService->saveGrades($payload, $userId, $programming);
        }

        return [
            'saved' => count($payload),
            'skipped' => $skipped,
            'errors' => $errors,
            'analysis_conflicts' => $this->analysisConflicts($data, $programming, $outcomeByCode),
        ];
    }

    /**
     * Applies the analysis coming in the file, replacing what is stored.
     *
     * Called only after the professor confirms, because the conflicts reported
     * by import() are theirs to resolve.
     *
     * @return int number of outcomes whose analysis was replaced
     */
    public function applyAnalysis(string $path, Programming $programming, int $userId): int
    {
        $data = $this->reader->read($path);
        $this->assertBelongsToProgramming($data, $programming);

        $report = $this->builder->build($programming);
        $outcomeByCode = [];
        foreach ($report['outcomes'] as $outcome) {
            $outcomeByCode[$outcome['code']] = $outcome['model'];
        }

        $applied = 0;

        foreach ($data['analyses'] as $analysis) {
            $outcome = $outcomeByCode[$analysis['outcome_code']] ?? null;

            if (! $outcome) {
                continue;
            }

            AcademicSpaceAnalysis::updateOrCreate(
                [
                    'programming_id' => $programming->id,
                    'microcurricular_learning_outcome_id' => $outcome->id,
                ],
                [
                    'outcome_performance' => $analysis['answers'][0] ?? null,
                    'academic_space_performance' => $analysis['answers'][1] ?? null,
                    'improvement_proposals' => $analysis['answers'][2] ?? null,
                    'written_by' => $userId,
                ]
            );

            $applied++;
        }

        return $applied;
    }

    /**
     * Differences between the analysis in the file and the one already stored.
     *
     * The professor decides whether to replace it, so the import only reports
     * them instead of overwriting text that may be newer than the file.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, \App\Models\MicrocurricularLearningOutcome>  $outcomeByCode
     * @return list<array{outcome_code: string, stored: ?string, incoming: string, field: string}>
     */
    private function analysisConflicts(array $data, Programming $programming, array $outcomeByCode): array
    {
        $stored = AcademicSpaceAnalysis::where('programming_id', $programming->id)
            ->get()
            ->keyBy('microcurricular_learning_outcome_id');

        $fields = AcademicSpaceAnalysis::ANSWER_FIELDS;
        $conflicts = [];

        foreach ($data['analyses'] as $analysis) {
            $outcome = $outcomeByCode[$analysis['outcome_code']] ?? null;

            if (! $outcome) {
                continue;
            }

            $current = $stored->get($outcome->id);

            foreach ($fields as $index => $field) {
                $incoming = trim((string) ($analysis['answers'][$index] ?? ''));
                $existing = trim((string) ($current?->{$field} ?? ''));

                if ($incoming === '' || $incoming === $existing) {
                    continue;
                }

                $conflicts[] = [
                    'outcome_code' => $analysis['outcome_code'],
                    'field' => $field,
                    'stored' => $existing === '' ? null : $existing,
                    'incoming' => $incoming,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Rejects a file that does not describe the programming it is uploaded to.
     *
     * The report states its own period, academic space and group, so a
     * professor cannot load one group's marks into another by mistake.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertBelongsToProgramming(array $data, Programming $programming): void
    {
        $programming->loadMissing(['academicSpace', 'academicPeriod']);

        $sameSpace = $this->normalizer->key((string) $data['academic_space'])
            === $this->normalizer->key($programming->academicSpace?->name ?? '');

        $samePeriod = $data['period'] === null
            || $data['period'] === $programming->academicPeriod?->name;

        if (! $sameSpace || ! $samePeriod) {
            throw new RuntimeException(
                'El archivo no corresponde a esta programación: revise el espacio académico y el período.'
            );
        }
    }
}
