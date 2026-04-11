<?php

use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Enrollment;
use App\Models\EvaluationCriterion;
use App\Models\Faculty;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcomeType;
use App\Models\Modality;
use App\Models\PerformanceLevel;
use App\Models\ProblematicNucleus;
use App\Models\Professor;
use App\Models\Program;
use App\Models\Programming;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->professorUser = User::factory()->create(['role' => 'professor']);
    $this->professor = Professor::factory()->create(['user_id' => $this->professorUser->id, 'is_active' => true]);

    $this->academicSpace = AcademicSpace::factory()->create([
        'competency_id' => Competency::factory()->create([
            'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
                'program_id' => Program::factory()->create([
                    'faculty_id' => Faculty::factory()->create()->id,
                ])->id,
            ])->id,
        ])->id,
    ]);

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => Modality::factory()->create()->id,
        'is_active' => true,
    ]);

    $this->criterion = EvaluationCriterion::factory()->create();
    $this->performanceLevel = PerformanceLevel::factory()->create();
    $this->outcomeType = MicrocurricularLearningOutcomeType::factory()->create();
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'type_id' => $this->outcomeType->id,
        'is_active' => true,
    ]);
    $this->enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'is_active' => true,
    ]);
});

test('professor can view grading page for their programming', function () {
    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.show', $this->programming))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('professor/grading/show')
            ->has('programming')
            ->has('enrollments')
            ->has('criteria')
            ->has('performanceLevels')
            ->has('outcomesByType')
            ->has('existingGrades')
            ->has('completeness')
        );
});

test('professor cannot view grading for another professor programming', function () {
    $otherProfessor = Professor::factory()->create();
    $otherProgramming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $otherProfessor->id,
        'modality_id' => $this->programming->modality_id,
    ]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.show', $otherProgramming))
        ->assertForbidden();
});

test('professor can save grades', function () {
    $response = $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.save', $this->programming), [
            'grades' => [
                [
                    'enrollment_id' => $this->enrollment->id,
                    'microcurricular_learning_outcome_id' => $this->outcome->id,
                    'evaluation_criterion_id' => $this->criterion->id,
                    'performance_level_id' => $this->performanceLevel->id,
                    'observations' => 'Buen desempeño',
                ],
            ],
        ]);

    $response->assertOk();

    $this->assertDatabaseHas('grades', [
        'enrollment_id' => $this->enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->performanceLevel->id,
    ]);
});

test('saving grades twice updates instead of duplicating', function () {
    $payload = [
        'grades' => [
            [
                'enrollment_id' => $this->enrollment->id,
                'microcurricular_learning_outcome_id' => $this->outcome->id,
                'evaluation_criterion_id' => $this->criterion->id,
                'performance_level_id' => $this->performanceLevel->id,
            ],
        ],
    ];

    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.save', $this->programming), $payload)
        ->assertOk();

    $newLevel = PerformanceLevel::factory()->create();
    $payload['grades'][0]['performance_level_id'] = $newLevel->id;

    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.save', $this->programming), $payload)
        ->assertOk();

    expect(\App\Models\Grade::count())->toBe(1);
    expect(\App\Models\Grade::first()->performance_level_id)->toBe($newLevel->id);
});

test('professor cannot save grades for another professor programming', function () {
    $otherProfessor = Professor::factory()->create();
    $otherProgramming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $otherProfessor->id,
        'modality_id' => $this->programming->modality_id,
    ]);

    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.save', $otherProgramming), [
            'grades' => [
                [
                    'enrollment_id' => $this->enrollment->id,
                    'microcurricular_learning_outcome_id' => $this->outcome->id,
                    'evaluation_criterion_id' => $this->criterion->id,
                    'performance_level_id' => $this->performanceLevel->id,
                ],
            ],
        ])
        ->assertForbidden();
});

test('save grades fails with invalid payload', function () {
    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.save', $this->programming), [
            'grades' => [],
        ])
        ->assertUnprocessable();
});

test('confirm consolidation returns error when grades are incomplete', function () {
    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.confirm', $this->programming))
        ->assertUnprocessable()
        ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'pendientes'));
});

test('confirm consolidation succeeds when all grades are complete', function () {
    // Grading a programming with no students and no outcomes = 100%
    $emptyProgramming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->programming->modality_id,
    ]);

    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.confirm', $emptyProgramming))
        ->assertOk();
});
