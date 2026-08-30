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
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);

    $faculty = Faculty::factory()->create();
    $program = Program::factory()->create(['faculty_id' => $faculty->id]);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id]);
    $this->space = AcademicSpace::factory()->create(['competency_id' => $competency->id]);

    $this->type = MicrocurricularLearningOutcomeType::factory()->create();
    $this->criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->type->id,
    ]);
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->space->id,
        'type_id' => $this->type->id,
        'is_active' => true,
    ]);
    $this->level = PerformanceLevel::factory()->create(['order' => 4]);

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => Modality::factory()->create()->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
    ]);

    $this->enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => Student::factory()->create()->id,
        'is_active' => true,
    ]);

    $this->grade = Grade::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->level->id,
        'graded_by' => $this->admin->id,
    ]);
});

test('deactivating a graded outcome removes it from completeness accounting', function () {
    $service = app(\App\Services\GradingService::class);

    expect($service->completeness($this->programming)['total'])->toBe(1);

    // An admin deactivates an outcome that already has grades recorded.
    $this->actingAs($this->admin)
        ->patch(route('admin.microcurricular-outcomes.toggle-status', $this->outcome))
        ->assertRedirect();

    $after = $service->completeness($this->programming);

    // Characterisation test: the grade row survives but stops being counted,
    // so a partially graded programming can jump to 100% complete. Whether an
    // outcome with existing grades may be deactivated at all is a product
    // decision tracked alongside the evaluation-configuration work.
    expect(Grade::count())->toBe(1);
    expect($after['total'])->toBe(0)
        ->and($after['percentage'])->toBe(100.0);
});

test('deleting an academic space cascades to its outcomes and grades', function () {
    // Foreign keys use cascadeOnDelete from academic_spaces down to grades.
    $this->space->delete();

    expect(MicrocurricularLearningOutcome::count())->toBe(0)
        ->and(Grade::count())->toBe(0)
        ->and(Enrollment::count())->toBe(0);
});

test('a criterion in use cannot be removed without taking grades with it', function () {
    // evaluation_criterion_id has no cascade, so the database must refuse.
    expect(fn () => $this->criterion->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(Grade::count())->toBe(1);
});

test('a performance level in use cannot be removed without taking grades with it', function () {
    expect(fn () => $this->level->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(Grade::count())->toBe(1);
});

test('a professor with programmings can be soft-deleted without breaking them', function () {
    $professor = $this->programming->professor;
    $professor->delete();

    // The programming survives; its professor relation resolves to null.
    expect($this->programming->fresh())->not->toBeNull();

    $this->actingAs($this->admin)
        ->get(route('admin.programmings.index'))
        ->assertOk();
});

test('the programmings statistics page survives a soft-deleted professor', function () {
    $this->programming->professor->delete();

    $this->actingAs($this->admin)
        ->get(route('admin.programmings.statistics', $this->programming))
        ->assertOk();
});
