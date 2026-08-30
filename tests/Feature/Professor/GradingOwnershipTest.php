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
    $faculty = Faculty::factory()->create();
    $program = Program::factory()->create(['faculty_id' => $faculty->id]);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id]);

    $this->modality = Modality::factory()->create();
    $this->period = AcademicPeriod::factory()->create();
    $this->outcomeType = MicrocurricularLearningOutcomeType::factory()->create();
    $this->criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->outcomeType->id,
    ]);
    $this->level = PerformanceLevel::factory()->create(['order' => 4]);

    $makeContext = function (string $suffix) use ($competency) {
        $user = User::factory()->create(['role' => 'professor']);
        $professor = Professor::factory()->create(['user_id' => $user->id]);
        $space = AcademicSpace::factory()->create([
            'competency_id' => $competency->id,
            'code' => 'AS'.$suffix,
        ]);
        $outcome = MicrocurricularLearningOutcome::factory()->create([
            'academic_space_id' => $space->id,
            'type_id' => $this->outcomeType->id,
            'is_active' => true,
        ]);
        $programming = Programming::factory()->create([
            'academic_space_id' => $space->id,
            'professor_id' => $professor->id,
            'modality_id' => $this->modality->id,
            'academic_period_id' => $this->period->id,
            'group' => $suffix,
        ]);
        $enrollment = Enrollment::factory()->create([
            'programming_id' => $programming->id,
            'student_id' => Student::factory()->create()->id,
            'is_active' => true,
        ]);

        return compact('user', 'professor', 'space', 'outcome', 'programming', 'enrollment');
    };

    $this->owner = $makeContext('A');
    $this->stranger = $makeContext('B');
});

test('professor cannot grade an enrollment belonging to another programming', function () {
    $this->actingAs($this->owner['user'])
        ->postJson(route('professor.programmings.grading.save', $this->owner['programming']), [
            'grades' => [[
                'enrollment_id' => $this->stranger['enrollment']->id,
                'microcurricular_learning_outcome_id' => $this->stranger['outcome']->id,
                'evaluation_criterion_id' => $this->criterion->id,
                'performance_level_id' => $this->level->id,
            ]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('grades.0.enrollment_id');

    expect(Grade::where('enrollment_id', $this->stranger['enrollment']->id)->exists())->toBeFalse();
});

test('professor cannot grade an outcome from another academic space', function () {
    $this->actingAs($this->owner['user'])
        ->postJson(route('professor.programmings.grading.save', $this->owner['programming']), [
            'grades' => [[
                'enrollment_id' => $this->owner['enrollment']->id,
                'microcurricular_learning_outcome_id' => $this->stranger['outcome']->id,
                'evaluation_criterion_id' => $this->criterion->id,
                'performance_level_id' => $this->level->id,
            ]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('grades.0.microcurricular_learning_outcome_id');

    expect(Grade::count())->toBe(0);
});

test('professor cannot pair an outcome with a criterion of a different type', function () {
    $foreignType = MicrocurricularLearningOutcomeType::factory()->create();
    $foreignCriterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $foreignType->id,
    ]);

    $this->actingAs($this->owner['user'])
        ->postJson(route('professor.programmings.grading.save', $this->owner['programming']), [
            'grades' => [[
                'enrollment_id' => $this->owner['enrollment']->id,
                'microcurricular_learning_outcome_id' => $this->owner['outcome']->id,
                'evaluation_criterion_id' => $foreignCriterion->id,
                'performance_level_id' => $this->level->id,
            ]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('grades.0.evaluation_criterion_id');

    expect(Grade::where('evaluation_criterion_id', $foreignCriterion->id)->exists())->toBeFalse();
});

test('professor can still save a fully valid grade', function () {
    $this->actingAs($this->owner['user'])
        ->postJson(route('professor.programmings.grading.save', $this->owner['programming']), [
            'grades' => [[
                'enrollment_id' => $this->owner['enrollment']->id,
                'microcurricular_learning_outcome_id' => $this->owner['outcome']->id,
                'evaluation_criterion_id' => $this->criterion->id,
                'performance_level_id' => $this->level->id,
                'observations' => 'Buen desempeño.',
            ]],
        ])
        ->assertOk();

    expect(Grade::where([
        'enrollment_id' => $this->owner['enrollment']->id,
        'microcurricular_learning_outcome_id' => $this->owner['outcome']->id,
        'evaluation_criterion_id' => $this->criterion->id,
    ])->exists())->toBeTrue();
});

test('a valid batch is rejected atomically when one entry is invalid', function () {
    $this->actingAs($this->owner['user'])
        ->postJson(route('professor.programmings.grading.save', $this->owner['programming']), [
            'grades' => [
                [
                    'enrollment_id' => $this->owner['enrollment']->id,
                    'microcurricular_learning_outcome_id' => $this->owner['outcome']->id,
                    'evaluation_criterion_id' => $this->criterion->id,
                    'performance_level_id' => $this->level->id,
                ],
                [
                    'enrollment_id' => $this->stranger['enrollment']->id,
                    'microcurricular_learning_outcome_id' => $this->owner['outcome']->id,
                    'evaluation_criterion_id' => $this->criterion->id,
                    'performance_level_id' => $this->level->id,
                ],
            ],
        ])
        ->assertStatus(422);

    expect(Grade::count())->toBe(0, 'The valid row was written despite the batch being rejected.');
});

test('grading an inactive enrollment is rejected', function () {
    $this->owner['enrollment']->update(['is_active' => false]);

    $this->actingAs($this->owner['user'])
        ->postJson(route('professor.programmings.grading.save', $this->owner['programming']), [
            'grades' => [[
                'enrollment_id' => $this->owner['enrollment']->id,
                'microcurricular_learning_outcome_id' => $this->owner['outcome']->id,
                'evaluation_criterion_id' => $this->criterion->id,
                'performance_level_id' => $this->level->id,
            ]],
        ])
        ->assertStatus(422);
});

test('admin cannot toggle an enrollment through a foreign programming url', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->patch(route('admin.programmings.enrollments.toggle-status', [
            'programming' => $this->owner['programming']->id,
            'enrollment' => $this->stranger['enrollment']->id,
        ]))
        ->assertNotFound();

    expect($this->stranger['enrollment']->fresh()->is_active)->toBeTrue();
});

test('admin can still toggle an enrollment through its own programming url', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->patch(route('admin.programmings.enrollments.toggle-status', [
            'programming' => $this->owner['programming']->id,
            'enrollment' => $this->owner['enrollment']->id,
        ]))
        ->assertRedirect();

    expect($this->owner['enrollment']->fresh()->is_active)->toBeFalse();
});
