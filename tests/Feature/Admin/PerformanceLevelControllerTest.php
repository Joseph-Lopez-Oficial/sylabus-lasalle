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
    PerformanceLevel::forgetScaleCache();

    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->basic = PerformanceLevel::factory()->create([
        'name' => 'Básico',
        'order' => 2,
        'grade_value' => 2.5,
        'is_below_basic_threshold' => true,
    ]);

    $this->top = PerformanceLevel::factory()->create([
        'name' => 'Destacado',
        'order' => 4,
        'grade_value' => 5.0,
        'is_below_basic_threshold' => false,
    ]);
});

afterEach(fn () => PerformanceLevel::forgetScaleCache());

/** Payload with every required field, so each test only overrides what it exercises. */
function levelPayload(PerformanceLevel $level, array $overrides = []): array
{
    $payload = array_merge([
        'name' => $level->name,
        'description' => $level->description,
        'order' => $level->order,
        'grade_value' => $level->grade_value,
        'is_below_basic_threshold' => $level->is_below_basic_threshold,
        'is_active' => $level->is_active,
    ], $overrides);

    // A false bool serialises to an empty string in a form post, which the
    // boolean rule rejects; sending 0/1 mirrors what the browser submits.
    foreach (['is_below_basic_threshold', 'is_active'] as $flag) {
        $payload[$flag] = $payload[$flag] ? 1 : 0;
    }

    return $payload;
}

// ── Acceso ────────────────────────────────────────────────────────────────────

test('guest is redirected from the performance levels index', function () {
    $this->get(route('admin.performance-levels.index'))->assertRedirect(route('login'));
});

test('professor cannot access the performance levels index', function () {
    $professor = User::factory()->create(['role' => 'professor']);

    $this->actingAs($professor)
        ->get(route('admin.performance-levels.index'))
        ->assertForbidden();
});

test('professor cannot update a performance level', function () {
    $professor = User::factory()->create(['role' => 'professor']);

    $this->actingAs($professor)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['grade_value' => 4.0]))
        ->assertForbidden();

    expect($this->top->fresh()->grade_value)->toBe(5.0);
});

// ── Listado y edición ─────────────────────────────────────────────────────────

test('admin can list the performance levels ordered by their order', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.performance-levels.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/performance-levels/index')
            ->has('levels', 2)
            ->where('levels.0.order', 2)
            ->where('levels.1.order', 4)
        );
});

test('the index reports how many grades use each level', function () {
    $enrollment = makeGradedEnrollment($this->top, $this->admin);
    expect($enrollment)->not->toBeNull();

    $this->actingAs($this->admin)
        ->get(route('admin.performance-levels.index'))
        ->assertInertia(fn ($page) => $page->where('levels.1.grades_count', 1));
});

test('admin can open the edit page of a level', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.performance-levels.edit', $this->top))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/performance-levels/edit')
            ->where('level.name', 'Destacado')
            ->where('gradesCount', 0)
        );
});

test('admin can change the grade value of a level', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['grade_value' => 4.5]))
        ->assertRedirect(route('admin.performance-levels.index'));

    expect($this->top->fresh()->grade_value)->toBe(4.5);
});

test('changing the value from the interface changes the computed statistics', function () {
    $programming = makeGradedEnrollment($this->top, $this->admin, returnProgramming: true);

    $before = app(StatisticsService::class)->calculate($programming);
    expect($before['summary']['overall_average'])->toBe(5.0);

    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['grade_value' => 4.0]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $after = app(StatisticsService::class)->calculate($programming->fresh());
    expect($after['summary']['overall_average'])->toBe(4.0);
});

// ── Validación ────────────────────────────────────────────────────────────────

test('the grade value cannot exceed the top of the scale', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['grade_value' => 7.5]))
        ->assertSessionHasErrors('grade_value');

    expect($this->top->fresh()->grade_value)->toBe(5.0);
});

test('the grade value cannot be negative', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['grade_value' => -1]))
        ->assertSessionHasErrors('grade_value');
});

test('two levels cannot share the same order', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['order' => 2]))
        ->assertSessionHasErrors('order');
});

test('two levels cannot share the same name', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['name' => 'Básico']))
        ->assertSessionHasErrors('name');
});

test('a level keeps its own name and order when nothing else changes', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top))
        ->assertSessionHasNoErrors();
});

// ── Protecciones ──────────────────────────────────────────────────────────────

test('a level already used by grades cannot be left without a value', function () {
    makeGradedEnrollment($this->top, $this->admin);

    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['grade_value' => null]))
        ->assertSessionHasErrors('grade_value');

    expect($this->top->fresh()->grade_value)->toBe(5.0);
});

test('an unused level may be left without a value', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['grade_value' => null]))
        ->assertSessionHasNoErrors();

    expect($this->top->fresh()->grade_value)->toBeNull();
});

test('the system cannot be left without a below-basic threshold', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->basic), levelPayload($this->basic, [
            'is_below_basic_threshold' => false,
        ]))
        ->assertSessionHasErrors('is_below_basic_threshold');

    expect($this->basic->fresh()->is_below_basic_threshold)->toBeTrue();
});

test('marking a level as the threshold clears the flag from the previous one', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, [
            'is_below_basic_threshold' => true,
        ]))
        ->assertRedirect();

    expect($this->top->fresh()->is_below_basic_threshold)->toBeTrue()
        ->and($this->basic->fresh()->is_below_basic_threshold)->toBeFalse();

    PerformanceLevel::forgetScaleCache();
    expect(PerformanceLevel::belowBasicThreshold())->toBe(5.0);
});

// ── Creación ──────────────────────────────────────────────────────────────────

test('professor cannot create a performance level', function () {
    $this->actingAs(User::factory()->create(['role' => 'professor']))
        ->post(route('admin.performance-levels.store'), [
            'name' => 'Intruso',
            'order' => 9,
            'grade_value' => 3.0,
        ])
        ->assertForbidden();

    expect(PerformanceLevel::where('name', 'Intruso')->exists())->toBeFalse();
});

test('admin can create a performance level', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.performance-levels.store'), [
            'name' => 'Competente',
            'description' => 'Alcanza lo esperado',
            'order' => 3,
            'grade_value' => 3.8,
            'is_below_basic_threshold' => false,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.performance-levels.index'));

    $created = PerformanceLevel::where('name', 'Competente')->first();

    expect($created)->not->toBeNull()
        ->and($created->order)->toBe(3)
        ->and($created->grade_value)->toBe(3.8)
        ->and($created->is_active)->toBeTrue();
});

test('a new level cannot reuse an existing name or order', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.performance-levels.store'), [
            'name' => 'Básico',
            'order' => 7,
            'grade_value' => 3.0,
        ])
        ->assertSessionHasErrors('name');

    $this->actingAs($this->admin)
        ->post(route('admin.performance-levels.store'), [
            'name' => 'Nuevo nivel',
            'order' => 2,
            'grade_value' => 3.0,
        ])
        ->assertSessionHasErrors('order');
});

test('a new level marked as threshold clears the flag from the previous one', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.performance-levels.store'), [
            'name' => 'Mínimo aceptable',
            'order' => 3,
            'grade_value' => 3.0,
            'is_below_basic_threshold' => true,
        ])
        ->assertRedirect();

    expect($this->basic->fresh()->is_below_basic_threshold)->toBeFalse();

    PerformanceLevel::forgetScaleCache();
    expect(PerformanceLevel::belowBasicThreshold())->toBe(3.0)
        ->and(PerformanceLevel::belowBasicLevelName())->toBe('Mínimo aceptable');
});

test('a created level immediately joins the grading scale', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.performance-levels.store'), [
            'name' => 'Competente',
            'order' => 3,
            'grade_value' => 3.8,
        ])
        ->assertRedirect();

    expect(PerformanceLevel::gradeForOrder(3))->toBe(3.8);
});

// ── Desactivación ─────────────────────────────────────────────────────────────

test('professor cannot toggle a performance level', function () {
    $this->actingAs(User::factory()->create(['role' => 'professor']))
        ->patch(route('admin.performance-levels.toggle-status', $this->top))
        ->assertForbidden();

    expect($this->top->fresh()->is_active)->toBeTrue();
});

test('an unused level can be deactivated and reactivated', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.performance-levels.toggle-status', $this->top))
        ->assertSessionHas('success');

    expect($this->top->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)
        ->patch(route('admin.performance-levels.toggle-status', $this->top))
        ->assertSessionHas('success');

    expect($this->top->fresh()->is_active)->toBeTrue();
});

test('a level used by grades cannot be deactivated from the toggle', function () {
    makeGradedEnrollment($this->top, $this->admin);

    $this->actingAs($this->admin)
        ->patch(route('admin.performance-levels.toggle-status', $this->top))
        ->assertSessionHas('error');

    expect($this->top->fresh()->is_active)->toBeTrue();
});

test('a level used by grades cannot be deactivated from the edit form', function () {
    makeGradedEnrollment($this->top, $this->admin);

    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), levelPayload($this->top, ['is_active' => false]))
        ->assertSessionHasErrors('is_active');

    expect($this->top->fresh()->is_active)->toBeTrue();
});

test('the threshold level cannot be deactivated', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.performance-levels.toggle-status', $this->basic))
        ->assertSessionHas('error');

    expect($this->basic->fresh()->is_active)->toBeTrue();
});

test('a deactivated level still resolves the grades already assigned to it', function () {
    $programming = makeGradedEnrollment($this->top, $this->admin, returnProgramming: true);

    $this->top->update(['is_active' => false]);
    PerformanceLevel::forgetScaleCache();

    // Averages read the whole catalogue, so retiring a level must not change a
    // single number already computed from it.
    $stats = app(StatisticsService::class)->calculate($programming->fresh());

    expect($stats['summary']['overall_average'])->toBe(5.0);
});

/**
 * Creates a fully wired programming with one enrolled student graded at the
 * given level. Returns the enrollment, or the programming when asked.
 */
function makeGradedEnrollment(PerformanceLevel $level, User $gradedBy, bool $returnProgramming = false)
{
    $faculty = Faculty::factory()->create();
    $program = Program::factory()->create(['faculty_id' => $faculty->id]);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id]);
    $space = AcademicSpace::factory()->create(['competency_id' => $competency->id]);

    $type = MicrocurricularLearningOutcomeType::factory()->create();
    $criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $type->id,
    ]);
    $outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $space->id,
        'type_id' => $type->id,
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

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'microcurricular_learning_outcome_id' => $outcome->id,
        'evaluation_criterion_id' => $criterion->id,
        'performance_level_id' => $level->id,
        'graded_by' => $gradedBy->id,
    ]);

    return $returnProgramming ? $programming : $enrollment;
}
