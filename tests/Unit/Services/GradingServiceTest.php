<?php

use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Enrollment;
use App\Models\EvaluationCriterion;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcomeType;
use App\Models\Modality;
use App\Models\PerformanceLevel;
use App\Models\ProblematicNucleus;
use App\Models\Professor;
use App\Models\Program;
use App\Models\Programming;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new GradingService;

    $this->gradedByUser = User::factory()->create(['role' => 'admin']);

    $academicSpace = AcademicSpace::factory()->create([
        'competency_id' => Competency::factory()->create([
            'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
                'program_id' => Program::factory()->create([
                    'faculty_id' => Faculty::factory()->create()->id,
                ])->id,
            ])->id,
        ])->id,
    ]);

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $academicSpace->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => Modality::factory()->create()->id,
    ]);

    $this->outcomeType = MicrocurricularLearningOutcomeType::factory()->create();
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $academicSpace->id,
        'type_id' => $this->outcomeType->id,
        'is_active' => true,
    ]);

    $this->criterion = EvaluationCriterion::factory()->create();
    $this->performanceLevel = PerformanceLevel::factory()->create();

    $this->enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'is_active' => true,
    ]);
});

test('saveGrades creates new grade records', function () {
    $this->service->saveGrades([
        [
            'enrollment_id' => $this->enrollment->id,
            'microcurricular_learning_outcome_id' => $this->outcome->id,
            'evaluation_criterion_id' => $this->criterion->id,
            'performance_level_id' => $this->performanceLevel->id,
            'observations' => 'Observación de prueba',
        ],
    ], $this->gradedByUser->id);

    expect(Grade::count())->toBe(1);
    expect(Grade::first()->observations)->toBe('Observación de prueba');
});

test('saveGrades updates existing grade instead of duplicating', function () {
    Grade::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->performanceLevel->id,
        'graded_by' => $this->gradedByUser->id,
    ]);

    $newLevel = PerformanceLevel::factory()->create();

    $this->service->saveGrades([
        [
            'enrollment_id' => $this->enrollment->id,
            'microcurricular_learning_outcome_id' => $this->outcome->id,
            'evaluation_criterion_id' => $this->criterion->id,
            'performance_level_id' => $newLevel->id,
        ],
    ], $this->gradedByUser->id);

    expect(Grade::count())->toBe(1);
    expect(Grade::first()->performance_level_id)->toBe($newLevel->id);
});

test('saveGrades is atomic and rolls back on error', function () {
    expect(fn () => $this->service->saveGrades([
        [
            'enrollment_id' => $this->enrollment->id,
            'microcurricular_learning_outcome_id' => $this->outcome->id,
            'evaluation_criterion_id' => $this->criterion->id,
            'performance_level_id' => 99999, // ID inválido — forzará error
        ],
    ], $this->gradedByUser->id))->toThrow(\Exception::class);

    expect(Grade::count())->toBe(0);
});

test('completeness returns 100 percent when no enrollments or outcomes', function () {
    $emptyProgramming = Programming::factory()->create([
        'academic_space_id' => $this->programming->academicSpace->id,
        'professor_id' => $this->programming->professor_id,
        'modality_id' => $this->programming->modality_id,
    ]);

    $result = $this->service->completeness($emptyProgramming);

    expect($result['percentage'])->toBe(100.0);
    expect($result['pending'])->toBeEmpty();
});

test('completeness returns correct percentage with partial grades', function () {
    // 1 estudiante × 1 resultado × 1 criterio = 1 combinación posible
    // Ninguna calificada → 0%
    $result = $this->service->completeness($this->programming);

    expect($result['total'])->toBe(1);
    expect($result['completed'])->toBe(0);
    expect($result['percentage'])->toBe(0.0);
    expect($result['pending'])->toHaveCount(1);
});

test('completeness returns 100 percent when all grades exist', function () {
    Grade::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->performanceLevel->id,
        'graded_by' => $this->gradedByUser->id,
    ]);

    $result = $this->service->completeness($this->programming);

    expect($result['percentage'])->toBe(100.0);
    expect($result['pending'])->toBeEmpty();
});
