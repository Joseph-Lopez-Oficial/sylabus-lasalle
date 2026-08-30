<?php

use App\Models\AcademicPeriod;
use App\Models\AcademicSpace;
use App\Models\AcademicSpaceAnalysis;
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
use App\Services\AcademicSpaceAnalysisService;
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    PerformanceLevel::forgetScaleCache();

    $this->professorUser = User::factory()->create(['role' => 'professor']);
    $this->professor = Professor::factory()->create([
        'user_id' => $this->professorUser->id,
        'is_active' => true,
    ]);

    $this->space = AcademicSpace::factory()->create([
        'competency_id' => Competency::factory()->create([
            'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
                'program_id' => Program::factory()->create([
                    'faculty_id' => Faculty::factory()->create()->id,
                ])->id,
            ])->id,
        ])->id,
    ]);

    $this->type = MicrocurricularLearningOutcomeType::factory()->create(['name' => 'Conocimiento']);

    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->space->id,
        'type_id' => $this->type->id,
        'is_active' => true,
    ]);

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => $this->professor->id,
        'modality_id' => Modality::factory()->create()->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
        'is_active' => true,
    ]);
});

afterEach(fn () => PerformanceLevel::forgetScaleCache());

/** Payload with the three answers for a single outcome. */
function analysisPayload(int $outcomeId, array $overrides = []): array
{
    return ['analyses' => [array_merge([
        'microcurricular_learning_outcome_id' => $outcomeId,
        'outcome_performance' => 'El grupo alcanzó el resultado esperado.',
        'academic_space_performance' => 'La participación fue constante.',
        'improvement_proposals' => 'Reforzar el acompañamiento en las prácticas.',
    ], $overrides)]];
}

// ── Acceso ────────────────────────────────────────────────────────────────────

test('guest is redirected from the analysis page', function () {
    $this->get(route('professor.programmings.analysis.show', $this->programming))
        ->assertRedirect(route('login'));
});

test('professor can open the analysis page of their programming', function () {
    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.analysis.show', $this->programming))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('professor/analysis/show')
            ->has('outcomes', 1)
            ->where('outcomes.0.code', $this->outcome->code)
            ->where('canEdit', true)
        );
});

test('professor cannot open the analysis of another professor programming', function () {
    $other = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => $this->programming->modality_id,
        'academic_period_id' => $this->programming->academic_period_id,
    ]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.analysis.show', $other))
        ->assertForbidden();
});

test('professor cannot save the analysis of another professor programming', function () {
    $other = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => $this->programming->modality_id,
        'academic_period_id' => $this->programming->academic_period_id,
    ]);

    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $other), analysisPayload($this->outcome->id))
        ->assertForbidden();

    expect(AcademicSpaceAnalysis::count())->toBe(0);
});

// ── Guardado ──────────────────────────────────────────────────────────────────

test('professor can save the analysis of an outcome', function () {
    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($this->outcome->id))
        ->assertRedirect()
        ->assertSessionHas('success');

    $analysis = AcademicSpaceAnalysis::first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->programming_id)->toBe($this->programming->id)
        ->and($analysis->microcurricular_learning_outcome_id)->toBe($this->outcome->id)
        ->and($analysis->outcome_performance)->toBe('El grupo alcanzó el resultado esperado.')
        ->and($analysis->written_by)->toBe($this->professorUser->id);
});

test('saving twice updates the analysis instead of duplicating it', function () {
    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($this->outcome->id));

    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($this->outcome->id, [
            'outcome_performance' => 'Texto corregido.',
        ]));

    expect(AcademicSpaceAnalysis::count())->toBe(1)
        ->and(AcademicSpaceAnalysis::first()->outcome_performance)->toBe('Texto corregido.');
});

test('the analysis may be saved with empty answers', function () {
    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($this->outcome->id, [
            'outcome_performance' => '',
            'academic_space_performance' => null,
            'improvement_proposals' => '',
        ]))
        ->assertSessionHasNoErrors();

    $analysis = AcademicSpaceAnalysis::first();

    // Blank answers are stored as null so the pending check stays a single test.
    expect($analysis->outcome_performance)->toBeNull()
        ->and($analysis->hasContent())->toBeFalse();
});

test('an answer longer than the limit is rejected', function () {
    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($this->outcome->id, [
            'outcome_performance' => str_repeat('a', 5001),
        ]))
        ->assertSessionHasErrors('analyses.0.outcome_performance');

    expect(AcademicSpaceAnalysis::count())->toBe(0);
});

test('an outcome of another academic space cannot be analysed', function () {
    $foreignSpace = AcademicSpace::factory()->create([
        'competency_id' => $this->space->competency_id,
    ]);
    $foreignOutcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $foreignSpace->id,
        'type_id' => $this->type->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($foreignOutcome->id))
        ->assertSessionHasErrors('analyses');

    expect(AcademicSpaceAnalysis::count())->toBe(0);
});

// ── Estado pendiente y promedios ──────────────────────────────────────────────

test('an outcome without analysis is reported as pending', function () {
    $service = app(AcademicSpaceAnalysisService::class);

    expect($service->pendingOutcomeIds($this->programming))->toBe([$this->outcome->id]);

    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($this->outcome->id));

    expect($service->pendingOutcomeIds($this->programming->fresh()))->toBe([]);
});

test('an analysis with every answer cleared counts as pending', function () {
    AcademicSpaceAnalysis::factory()->empty()->create([
        'programming_id' => $this->programming->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'written_by' => $this->professorUser->id,
    ]);

    expect(app(AcademicSpaceAnalysisService::class)->pendingOutcomeIds($this->programming))
        ->toBe([$this->outcome->id]);
});

test('the grading screen reports how many outcomes lack analysis', function () {
    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.show', $this->programming))
        ->assertInertia(fn ($page) => $page->where('pendingAnalysisCount', 1));

    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($this->outcome->id));

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.show', $this->programming))
        ->assertInertia(fn ($page) => $page->where('pendingAnalysisCount', 0));
});

test('the analysis page shows the group average of each outcome', function () {
    $level = PerformanceLevel::factory()->create(['order' => 4, 'grade_value' => 5.0]);
    $criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->type->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => Student::factory()->create()->id,
        'is_active' => true,
    ]);

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $criterion->id,
        'performance_level_id' => $level->id,
        'graded_by' => $this->professorUser->id,
    ]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.analysis.show', $this->programming))
        ->assertInertia(fn ($page) => $page->where('outcomes.0.average', 5));

    // The service is the one that decides the number; the page only renders it.
    expect(app(AcademicSpaceAnalysisService::class)->outcomeAverages($this->programming))
        ->toBe([$this->outcome->id => 5.0]);
});

test('an outcome with no grades reports a null average instead of zero', function () {
    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.analysis.show', $this->programming))
        ->assertInertia(fn ($page) => $page->where('outcomes.0.average', null));
});

test('an outcome retired after being graded stays analysable', function () {
    $level = PerformanceLevel::factory()->create(['order' => 4, 'grade_value' => 5.0]);
    $criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->type->id,
    ]);
    $enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => Student::factory()->create()->id,
        'is_active' => true,
    ]);
    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $criterion->id,
        'performance_level_id' => $level->id,
        'graded_by' => $this->professorUser->id,
    ]);

    // Retiring it from the catalogue must not hide an analysis already written
    // for a group that was graded on it.
    $this->outcome->update(['is_active' => false]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.analysis.show', $this->programming))
        ->assertInertia(fn ($page) => $page->has('outcomes', 1));
});

// ── El análisis no altera el avance de calificación ───────────────────────────

test('writing the analysis does not change the grading progress', function () {
    $before = app(GradingService::class)->completeness($this->programming);

    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($this->outcome->id));

    $after = app(GradingService::class)->completeness($this->programming->fresh());

    expect($after)->toBe($before);
});

// ── Vista del administrador ───────────────────────────────────────────────────

test('admin can read the analysis of any programming', function () {
    $this->actingAs($this->professorUser)
        ->post(route('professor.programmings.analysis.save', $this->programming), analysisPayload($this->outcome->id));

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.programmings.analysis', $this->programming))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/programmings/analysis')
            ->where('outcomes.0.analysis.outcome_performance', 'El grupo alcanzó el resultado esperado.')
        );
});

test('professor cannot reach the admin analysis view', function () {
    $this->actingAs($this->professorUser)
        ->get(route('admin.programmings.analysis', $this->programming))
        ->assertForbidden();
});

test('the analysis is removed when its programming is deleted', function () {
    AcademicSpaceAnalysis::factory()->create([
        'programming_id' => $this->programming->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'written_by' => $this->professorUser->id,
    ]);

    $this->programming->delete();

    expect(AcademicSpaceAnalysis::count())->toBe(0);
});
