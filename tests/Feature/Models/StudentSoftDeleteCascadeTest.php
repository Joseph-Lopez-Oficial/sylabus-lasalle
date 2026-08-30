<?php

use App\Models\AcademicPeriod;
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
use App\Models\Student;
use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $faculty = Faculty::factory()->create();
    $program = Program::factory()->create(['faculty_id' => $faculty->id]);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id]);
    $space = AcademicSpace::factory()->create(['competency_id' => $competency->id]);

    $this->outcomeType = MicrocurricularLearningOutcomeType::factory()->create();
    $this->criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->outcomeType->id,
    ]);
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $space->id,
        'type_id' => $this->outcomeType->id,
        'is_active' => true,
    ]);
    $this->level = PerformanceLevel::factory()->create(['order' => 4]);

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => Modality::factory()->create()->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
    ]);

    $this->student = Student::factory()->create();
    $this->enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => $this->student->id,
        'is_active' => true,
    ]);
});

test('soft-deleting a student deactivates their enrollments', function () {
    $this->student->delete();

    expect($this->enrollment->fresh()->is_active)->toBeFalse();
});

test('restoring a student reactivates their enrollments', function () {
    $this->student->delete();
    $this->student->restore();

    expect($this->enrollment->fresh()->is_active)->toBeTrue();
});

test('statistics survive a student soft-deleted after being graded', function () {
    Grade::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->level->id,
        'graded_by' => User::factory()->create(['role' => 'admin'])->id,
    ]);

    $this->student->delete();

    $statistics = app(StatisticsService::class)->calculate($this->programming->fresh());

    expect($statistics['byStudent'])->toBe([])
        ->and($statistics['summary']['overall_average'])->toBe(0.0);
});

test('statistics ignore an orphaned enrollment left active by legacy data', function () {
    Grade::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->level->id,
        'graded_by' => User::factory()->create(['role' => 'admin'])->id,
    ]);

    // Simulate pre-existing corrupt data: student gone, enrollment still active.
    Student::withoutEvents(fn () => $this->student->delete());

    expect($this->enrollment->fresh()->is_active)->toBeTrue();

    $statistics = app(StatisticsService::class)->calculate($this->programming->fresh());

    expect($statistics['byStudent'])->toBe([]);
});

test('an active student still appears in statistics', function () {
    Grade::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->level->id,
        'graded_by' => User::factory()->create(['role' => 'admin'])->id,
    ]);

    $statistics = app(StatisticsService::class)->calculate($this->programming->fresh());

    expect($statistics['byStudent'])->toHaveCount(1)
        ->and($statistics['byStudent'][0]['final_average'])->toBe(5.0);
});
