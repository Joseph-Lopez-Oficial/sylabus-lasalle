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
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->professorUser = User::factory()->create(['role' => 'professor']);
    $this->professor = Professor::factory()->create([
        'user_id' => $this->professorUser->id,
        'is_active' => true,
    ]);

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
        'professor_id' => $this->professor->id,
        'modality_id' => Modality::factory()->create()->id,
        'is_active' => true,
    ]);

    // Catalog data
    $this->outcomeType = MicrocurricularLearningOutcomeType::factory()->create();
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $academicSpace->id,
        'type_id' => $this->outcomeType->id,
        'is_active' => true,
    ]);
    $this->criterion = EvaluationCriterion::factory()->create();
    $this->performanceLevel = PerformanceLevel::factory()->create(['order' => 3]);

    $gradedBy = User::factory()->create(['role' => 'admin']);

    // One enrolled student with one grade
    $student = Student::factory()->create(['is_active' => true]);
    $this->enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => $student->id,
        'is_active' => true,
    ]);
    $this->grade = Grade::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->performanceLevel->id,
        'graded_by' => $gradedBy->id,
    ]);
});

test('professor can view statistics for their programming when grading is complete', function () {
    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.statistics.show', $this->programming))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('professor/statistics/show')
            ->has('statistics')
            ->has('statistics.byStudent')
            ->has('statistics.byOutcome')
            ->has('statistics.byCriterion')
            ->has('statistics.summary')
        );
});

test('professor cannot view statistics for another professor programming', function () {
    $otherProfessor = Professor::factory()->create();
    $otherProgramming = Programming::factory()->create([
        'academic_space_id' => $this->programming->academic_space_id,
        'professor_id' => $otherProfessor->id,
        'modality_id' => $this->programming->modality_id,
    ]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.statistics.show', $otherProgramming))
        ->assertForbidden();
});

test('statistics endpoint rejects incomplete grading', function () {
    // Create a second criterion without a grade → grading is incomplete
    EvaluationCriterion::factory()->create();

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.statistics.show', $this->programming))
        ->assertStatus(422);
});

test('guest cannot access statistics endpoint', function () {
    $this->get(route('professor.programmings.statistics.show', $this->programming))
        ->assertRedirect(route('login'));
});
