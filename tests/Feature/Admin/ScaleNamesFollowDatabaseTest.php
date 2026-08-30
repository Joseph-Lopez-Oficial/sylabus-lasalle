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

    $faculty = Faculty::factory()->create();
    $program = Program::factory()->create(['faculty_id' => $faculty->id]);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id]);
    $this->space = AcademicSpace::factory()->create(['competency_id' => $competency->id]);

    $type = MicrocurricularLearningOutcomeType::factory()->create();
    $this->criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $type->id,
    ]);
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->space->id,
        'type_id' => $type->id,
        'is_active' => true,
    ]);

    $this->basic = PerformanceLevel::factory()->create([
        'name' => 'Básico', 'order' => 2, 'grade_value' => 2.5,
        'is_below_basic_threshold' => true,
    ]);
    $this->top = PerformanceLevel::factory()->create([
        'name' => 'Destacado', 'order' => 4, 'grade_value' => 5.0,
    ]);

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => Modality::factory()->create()->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => Student::factory()->create()->id,
        'is_active' => true,
    ]);

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->top->id,
        'graded_by' => $this->admin->id,
    ]);
});

afterEach(fn () => PerformanceLevel::forgetScaleCache());

test('the statistics service ships the configured scale to the interface', function () {
    $stats = app(StatisticsService::class)->calculate($this->programming);

    expect($stats)->toHaveKey('scale')
        ->and($stats['scale'])->toHaveCount(2)
        ->and($stats['scale'][0]['name'])->toBe('Básico')
        ->and($stats['scale'][1]['name'])->toBe('Destacado')
        ->and($stats['scale'][1]['grade_value'])->toBe(5.0);
});

test('renaming a level renames it in the statistics payload', function () {
    $this->top->update(['name' => 'Sobresaliente']);
    PerformanceLevel::forgetScaleCache();

    $stats = app(StatisticsService::class)->calculate($this->programming->fresh());

    expect($stats['scale'][1]['name'])->toBe('Sobresaliente')
        ->and(collect($stats['summary']['distribution'])->pluck('level_name'))
        ->toContain('Sobresaliente')
        ->not->toContain('Destacado');
});

test('the programming statistics page receives the scale', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.programmings.statistics', $this->programming))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('statistics.scale', 2));
});

test('the academic space page receives the scale', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.academic-spaces.show', $this->space))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('scale', 2));
});

test('the learning outcome page receives the scale', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.microcurricular-outcomes.show', $this->outcome))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('scale', 2));
});

test('the at-risk label in exports follows the renamed level', function () {
    expect(PerformanceLevel::belowBasicLevelName())->toBe('Básico');

    $this->basic->update(['name' => 'Mínimo']);
    PerformanceLevel::forgetScaleCache();

    expect(PerformanceLevel::belowBasicLevelName())->toBe('Mínimo');
});

test('the at-risk label falls back when no level carries the flag', function () {
    $this->basic->update(['is_below_basic_threshold' => false]);
    PerformanceLevel::forgetScaleCache();

    expect(PerformanceLevel::belowBasicLevelName())->toBe('Básico');
});

test('a level renamed from the interface reaches the statistics page', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.performance-levels.update', $this->top), [
            'name' => 'Excelente',
            'description' => $this->top->description,
            'order' => $this->top->order,
            'grade_value' => $this->top->grade_value,
            'is_below_basic_threshold' => false,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->get(route('admin.programmings.statistics', $this->programming))
        ->assertInertia(fn ($page) => $page->where('statistics.scale.1.name', 'Excelente'));
});
