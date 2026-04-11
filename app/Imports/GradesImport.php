<?php

namespace App\Imports;

use App\Models\Enrollment;
use App\Models\EvaluationCriterion;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\PerformanceLevel;
use App\Models\Programming;
use App\Services\GradingService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GradesImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{row: int, status: string, message: string}> */
    public array $results = [];

    private readonly array $performanceLevelsByName;

    private readonly array $enrollmentsByStudentName;

    private readonly array $criteriaById;

    private readonly array $outcomesById;

    public function __construct(
        private readonly Programming $programming,
        private readonly int $gradedByUserId,
        private readonly GradingService $gradingService,
    ) {
        $this->performanceLevelsByName = PerformanceLevel::all()
            ->keyBy(fn ($l) => strtolower(trim($l->name)))
            ->toArray();

        $enrollments = $programming->enrollments()
            ->where('is_active', true)
            ->with('student')
            ->get();

        $this->enrollmentsByStudentName = $enrollments
            ->mapWithKeys(fn ($e) => [
                strtolower(trim($e->student->first_name.' '.$e->student->last_name)) => $e->id,
            ])
            ->toArray();

        $this->criteriaById = EvaluationCriterion::all()->keyBy('id')->toArray();

        $outcomeIds = $programming->academicSpace
            ->microcurricularLearningOutcomes()
            ->where('is_active', true)
            ->pluck('id');

        $this->outcomesById = MicrocurricularLearningOutcome::whereIn('id', $outcomeIds)
            ->get()
            ->keyBy('id')
            ->toArray();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $rowArray = $row->toArray();

            $enrollmentId = (int) ($rowArray['enrollment_id'] ?? 0);
            $studentName = strtolower(trim((string) ($rowArray['estudiante'] ?? '')));

            // Resolve enrollment
            if ($enrollmentId) {
                $resolvedEnrollmentId = Enrollment::where('id', $enrollmentId)
                    ->where('programming_id', $this->programming->id)
                    ->where('is_active', true)
                    ->exists() ? $enrollmentId : null;
            } else {
                $resolvedEnrollmentId = $this->enrollmentsByStudentName[$studentName] ?? null;
            }

            if (! $resolvedEnrollmentId) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'status' => 'error',
                    'message' => "Fila {$rowNumber}: Estudiante no encontrado o no inscrito en esta programación.",
                ];

                continue;
            }

            $gradesToSave = [];
            $rowHasError = false;

            // Each column after enrollment_id and Estudiante is a grade cell
            foreach ($rowArray as $colKey => $cellValue) {
                if (in_array($colKey, ['enrollment_id', 'estudiante']) || trim((string) $cellValue) === '') {
                    continue;
                }

                // Column format: RA{outcomeId}_{criterionId}
                if (! preg_match('/^ra(\d+)_(\d+)$/i', $colKey, $matches)) {
                    continue;
                }

                $outcomeId = (int) $matches[1];
                $criterionId = (int) $matches[2];

                if (! isset($this->outcomesById[$outcomeId])) {
                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'error',
                        'message' => "Fila {$rowNumber}: Resultado microcurricular {$outcomeId} no existe o no pertenece a esta programación.",
                    ];
                    $rowHasError = true;

                    break;
                }

                if (! isset($this->criteriaById[$criterionId])) {
                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'error',
                        'message' => "Fila {$rowNumber}: Criterio {$criterionId} no existe.",
                    ];
                    $rowHasError = true;

                    break;
                }

                $levelKey = strtolower(trim((string) $cellValue));
                $level = $this->performanceLevelsByName[$levelKey] ?? null;

                if (! $level) {
                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'error',
                        'message' => "Fila {$rowNumber}: Nivel de desempeño '{$cellValue}' no reconocido.",
                    ];
                    $rowHasError = true;

                    break;
                }

                $gradesToSave[] = [
                    'enrollment_id' => $resolvedEnrollmentId,
                    'microcurricular_learning_outcome_id' => $outcomeId,
                    'evaluation_criterion_id' => $criterionId,
                    'performance_level_id' => $level['id'],
                ];
            }

            if ($rowHasError || empty($gradesToSave)) {
                continue;
            }

            try {
                $this->gradingService->saveGrades($gradesToSave, $this->gradedByUserId);
                $this->results[] = [
                    'row' => $rowNumber,
                    'status' => 'success',
                    'message' => "Fila {$rowNumber}: Calificaciones guardadas correctamente.",
                ];
            } catch (\Throwable $e) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'status' => 'error',
                    'message' => "Fila {$rowNumber}: Error al guardar: ".$e->getMessage(),
                ];
            }
        }
    }
}
