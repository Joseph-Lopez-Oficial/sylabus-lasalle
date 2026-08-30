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

test('a graded outcome cannot be deactivated from the toggle', function () {
    $service = app(\App\Services\GradingService::class);

    expect($service->completeness($this->programming)['total'])->toBe(1);

    // Deactivating it would drop the outcome from the completeness count while
    // keeping its grades stored, letting a partially graded programming jump to
    // 100%; the operation is refused instead.
    $this->actingAs($this->admin)
        ->patch(route('admin.microcurricular-outcomes.toggle-status', $this->outcome))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($this->outcome->fresh()->is_active)->toBeTrue()
        ->and(Grade::count())->toBe(1)
        ->and($service->completeness($this->programming->fresh())['total'])->toBe(1);
});

test('a graded outcome cannot be deactivated from the edit form', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.microcurricular-outcomes.update', $this->outcome), [
            'academic_space_id' => $this->outcome->academic_space_id,
            'code' => $this->outcome->code,
            'type_id' => $this->outcome->type_id,
            'description' => $this->outcome->description,
            'is_active' => 0,
        ])
        ->assertSessionHasErrors('is_active');

    expect($this->outcome->fresh()->is_active)->toBeTrue();
});

test('an outcome without grades can still be deactivated', function () {
    $spare = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->space->id,
        'type_id' => $this->outcome->type_id,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.microcurricular-outcomes.toggle-status', $spare))
        ->assertSessionHas('success');

    expect($spare->fresh()->is_active)->toBeFalse();
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
