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
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->knowledge = MicrocurricularLearningOutcomeType::factory()->create(['name' => 'Conocimiento']);
    $this->skill = MicrocurricularLearningOutcomeType::factory()->create(['name' => 'Habilidad']);

    $this->criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->knowledge->id,
        'name' => 'Apropiación conceptual',
        'order' => 1,
        'is_active' => true,
    ]);
});

/** Payload with every required field, so each test only overrides what it exercises. */
function criterionPayload(EvaluationCriterion $criterion, array $overrides = []): array
{
    return array_merge([
        'microcurricular_learning_outcome_type_id' => $criterion->microcurricular_learning_outcome_type_id,
        'name' => $criterion->name,
        'description' => $criterion->description,
        'order' => $criterion->order,
        'is_active' => $criterion->is_active,
    ], $overrides);
}

// ── Acceso ────────────────────────────────────────────────────────────────────

test('guest is redirected from the evaluation criteria index', function () {
    $this->get(route('admin.evaluation-criteria.index'))->assertRedirect(route('login'));
});

test('professor cannot access the evaluation criteria index', function () {
    $this->actingAs(User::factory()->create(['role' => 'professor']))
        ->get(route('admin.evaluation-criteria.index'))
        ->assertForbidden();
});

test('professor cannot create an evaluation criterion', function () {
    $this->actingAs(User::factory()->create(['role' => 'professor']))
        ->post(route('admin.evaluation-criteria.store'), [
            'microcurricular_learning_outcome_type_id' => $this->knowledge->id,
            'name' => 'Intruso',
            'order' => 9,
        ])
        ->assertForbidden();

    expect(EvaluationCriterion::where('name', 'Intruso')->exists())->toBeFalse();
});

test('professor cannot toggle an evaluation criterion', function () {
    $this->actingAs(User::factory()->create(['role' => 'professor']))
        ->patch(route('admin.evaluation-criteria.toggle-status', $this->criterion))
        ->assertForbidden();

    expect($this->criterion->fresh()->is_active)->toBeTrue();
});

// ── Listado ───────────────────────────────────────────────────────────────────

test('admin can list the criteria with their type and grade count', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.evaluation-criteria.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/evaluation-criteria/index')
            ->has('criteria', 1)
            ->where('criteria.0.name', 'Apropiación conceptual')
            ->where('criteria.0.grades_count', 0)
            ->where('criteria.0.outcome_type.name', 'Conocimiento')
            ->has('types', 2)
        );
});

test('the index can be filtered by outcome type', function () {
    EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->skill->id,
        'name' => 'Ejecución técnica',
        'order' => 1,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.evaluation-criteria.index', [
            'microcurricular_learning_outcome_type_id' => $this->skill->id,
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('criteria', 1)
            ->where('criteria.0.name', 'Ejecución técnica')
        );
});

test('the index reports how many grades depend on a criterion', function () {
    makeGradedProgramming($this->criterion, $this->admin);

    $this->actingAs($this->admin)
        ->get(route('admin.evaluation-criteria.index'))
        ->assertInertia(fn ($page) => $page->where('criteria.0.grades_count', 1));
});

// ── Creación ──────────────────────────────────────────────────────────────────

test('admin can create a criterion', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.evaluation-criteria.store'), [
            'microcurricular_learning_outcome_type_id' => $this->knowledge->id,
            'name' => 'Fundamentación teórica',
            'description' => 'Domina los conceptos base',
            'order' => 2,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.evaluation-criteria.index'));

    $created = EvaluationCriterion::where('name', 'Fundamentación teórica')->first();

    expect($created)->not->toBeNull()
        ->and($created->order)->toBe(2)
        ->and($created->is_active)->toBeTrue()
        ->and($created->microcurricular_learning_outcome_type_id)->toBe($this->knowledge->id);
});

test('two criteria of the same type cannot share a name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.evaluation-criteria.store'), [
            'microcurricular_learning_outcome_type_id' => $this->knowledge->id,
            'name' => 'Apropiación conceptual',
            'order' => 5,
        ])
        ->assertSessionHasErrors('name');
});

test('two criteria of the same type cannot share an order', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.evaluation-criteria.store'), [
            'microcurricular_learning_outcome_type_id' => $this->knowledge->id,
            'name' => 'Otro criterio',
            'order' => 1,
        ])
        ->assertSessionHasErrors('order');
});

test('criteria of different types may share name and order', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.evaluation-criteria.store'), [
            'microcurricular_learning_outcome_type_id' => $this->skill->id,
            'name' => 'Apropiación conceptual',
            'order' => 1,
        ])
        ->assertSessionHasNoErrors();

    expect(EvaluationCriterion::where('name', 'Apropiación conceptual')->count())->toBe(2);
});

test('the order must be at least one', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.evaluation-criteria.store'), [
            'microcurricular_learning_outcome_type_id' => $this->knowledge->id,
            'name' => 'Criterio inválido',
            'order' => 0,
        ])
        ->assertSessionHasErrors('order');
});

// ── Edición ───────────────────────────────────────────────────────────────────

test('admin can open the edit page of a criterion', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.evaluation-criteria.edit', $this->criterion))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/evaluation-criteria/edit')
            ->where('criterion.name', 'Apropiación conceptual')
            ->where('gradesCount', 0)
        );
});

test('admin can rename a criterion', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.evaluation-criteria.update', $this->criterion), criterionPayload($this->criterion, [
            'name' => 'Apropiación de conceptos',
        ]))
        ->assertRedirect(route('admin.evaluation-criteria.index'));

    expect($this->criterion->fresh()->name)->toBe('Apropiación de conceptos');
});

test('a criterion keeps its own name and order when nothing else changes', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.evaluation-criteria.update', $this->criterion), criterionPayload($this->criterion))
        ->assertSessionHasNoErrors();
});

test('an unused criterion can be moved to another type', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.evaluation-criteria.update', $this->criterion), criterionPayload($this->criterion, [
            'microcurricular_learning_outcome_type_id' => $this->skill->id,
        ]))
        ->assertSessionHasNoErrors();

    expect($this->criterion->fresh()->microcurricular_learning_outcome_type_id)->toBe($this->skill->id);
});

// ── Protecciones sobre criterios en uso ───────────────────────────────────────

test('a criterion with grades cannot be moved to another type', function () {
    makeGradedProgramming($this->criterion, $this->admin);

    $this->actingAs($this->admin)
        ->put(route('admin.evaluation-criteria.update', $this->criterion), criterionPayload($this->criterion, [
            'microcurricular_learning_outcome_type_id' => $this->skill->id,
        ]))
        ->assertSessionHasErrors('microcurricular_learning_outcome_type_id');

    expect($this->criterion->fresh()->microcurricular_learning_outcome_type_id)->toBe($this->knowledge->id);
});

test('a criterion with grades cannot be deactivated from the edit form', function () {
    makeGradedProgramming($this->criterion, $this->admin);

    $this->actingAs($this->admin)
        ->put(route('admin.evaluation-criteria.update', $this->criterion), criterionPayload($this->criterion, [
            'is_active' => false,
        ]))
        ->assertSessionHasErrors('is_active');

    expect($this->criterion->fresh()->is_active)->toBeTrue();
});

test('a criterion with grades cannot be deactivated from the toggle', function () {
    makeGradedProgramming($this->criterion, $this->admin);

    $this->actingAs($this->admin)
        ->patch(route('admin.evaluation-criteria.toggle-status', $this->criterion))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($this->criterion->fresh()->is_active)->toBeTrue();
});

test('an unused criterion can be deactivated and reactivated', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.evaluation-criteria.toggle-status', $this->criterion))
        ->assertSessionHas('success');

    expect($this->criterion->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)
        ->patch(route('admin.evaluation-criteria.toggle-status', $this->criterion))
        ->assertSessionHas('success');

    expect($this->criterion->fresh()->is_active)->toBeTrue();
});

// ── Efecto sobre la calificación ──────────────────────────────────────────────

test('a deactivated criterion stops being required to reach one hundred percent', function () {
    $programming = makeGradedProgramming($this->criterion, $this->admin, gradeIt: false);

    $extra = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->knowledge->id,
        'name' => 'Criterio adicional',
        'order' => 2,
        'is_active' => true,
    ]);

    $service = app(GradingService::class);

    // Two active criteria over one outcome and one student.
    expect($service->completeness($programming)['total'])->toBe(2);

    $extra->update(['is_active' => false]);

    expect($service->completeness($programming->fresh())['total'])->toBe(1);
});

test('a criterion retired after grading still counts for the group that used it', function () {
    $programming = makeGradedProgramming($this->criterion, $this->admin);

    // The criterion was retired from the catalogue, but this group was graded
    // with it, so it must keep being part of that group's accounting.
    $this->criterion->update(['is_active' => false]);

    $completeness = app(GradingService::class)->completeness($programming->fresh());

    expect($completeness['total'])->toBe(1)
        ->and($completeness['completed'])->toBe(1)
        ->and($completeness['percentage'])->toBe(100.0);
});

test('a criterion created after grading started is not demanded from that group', function () {
    $programming = makeGradedProgramming($this->criterion, $this->admin);

    // Grading clearly happened before the new criterion existed, so the rule is
    // exercised on its own rather than on the one-second timestamp boundary.
    Grade::query()->update(['created_at' => now()->subDay()]);

    expect(app(GradingService::class)->completeness($programming->fresh())['percentage'])->toBe(100.0);

    // A brand new criterion must not push an already finished group back below
    // one hundred percent.
    EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->knowledge->id,
        'name' => 'Criterio nuevo',
        'order' => 3,
        'is_active' => true,
    ]);

    $after = app(GradingService::class)->completeness($programming->fresh());

    expect($after['total'])->toBe(1)
        ->and($after['percentage'])->toBe(100.0);
});

test('the grading screen still offers a retired criterion the group was graded with', function () {
    $programming = makeGradedProgramming($this->criterion, $this->admin);

    $this->criterion->update(['is_active' => false]);

    $professorUser = User::factory()->create(['role' => 'professor']);
    $programming->professor->update(['user_id' => $professorUser->id]);

    $this->actingAs($professorUser)
        ->get(route('professor.programmings.grading.show', $programming))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has("criteriaByTypeId.{$this->knowledge->id}", 1)
        );
});

/**
 * Builds a fully wired programming whose single outcome is of the criterion's
 * type, optionally with one grade already recorded against that criterion.
 */
function makeGradedProgramming(EvaluationCriterion $criterion, User $gradedBy, bool $gradeIt = true): Programming
{
    $faculty = Faculty::factory()->create();
    $program = Program::factory()->create(['faculty_id' => $faculty->id]);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id]);
    $space = AcademicSpace::factory()->create(['competency_id' => $competency->id]);

    $outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $space->id,
        'type_id' => $criterion->microcurricular_learning_outcome_type_id,
        'is_active' => true,
    ]);

    $programming = Programming::factory()->create([
        'academic_space_id' => $space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => Modality::factory()->create()->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'programming_id' => $programming->id,
        'student_id' => Student::factory()->create()->id,
        'is_active' => true,
    ]);

    if ($gradeIt) {
        Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'microcurricular_learning_outcome_id' => $outcome->id,
            'evaluation_criterion_id' => $criterion->id,
            'performance_level_id' => PerformanceLevel::factory()->create(['order' => 4])->id,
            'graded_by' => $gradedBy->id,
        ]);
    }

    return $programming;
}
