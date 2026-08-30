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
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'professor']);
    $this->professor = Professor::factory()->create(['user_id' => $this->user->id]);

    $faculty = Faculty::factory()->create();
    $program = Program::factory()->create(['faculty_id' => $faculty->id]);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);
    $this->competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id]);
    $this->modality = Modality::factory()->create();
    $this->period = AcademicPeriod::factory()->create();
    $this->outcomeType = MicrocurricularLearningOutcomeType::factory()->create();
    $this->criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->outcomeType->id,
    ]);

    $this->makeProgramming = function (string $group) {
        $space = AcademicSpace::factory()->create([
            'competency_id' => $this->competency->id,
            'code' => 'AS'.$group,
        ]);
        $outcome = MicrocurricularLearningOutcome::factory()->create([
            'academic_space_id' => $space->id,
            'type_id' => $this->outcomeType->id,
            'is_active' => true,
        ]);
        $programming = Programming::factory()->create([
            'academic_space_id' => $space->id,
            'professor_id' => $this->professor->id,
            'modality_id' => $this->modality->id,
            'academic_period_id' => $this->period->id,
            'group' => $group,
        ]);
        $enrollment = Enrollment::factory()->create([
            'programming_id' => $programming->id,
            'student_id' => Student::factory()->create()->id,
            'is_active' => true,
        ]);

        return compact('space', 'outcome', 'programming', 'enrollment');
    };
});

test('dashboard query count does not scale with the number of programmings', function () {
    ($this->makeProgramming)('A');

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($this->user)->get(route('professor.dashboard'))->assertOk();
    $baseline = count(DB::getQueryLog());
    DB::disableQueryLog();

    foreach (['B', 'C', 'D', 'E'] as $group) {
        ($this->makeProgramming)($group);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($this->user)->get(route('professor.dashboard'))->assertOk();
    $scaled = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($scaled)->toBeLessThanOrEqual(
        $baseline,
        "Query count grew from {$baseline} to {$scaled} across 1 → 5 programmings."
    );
});

test('dashboard reports the correct grading percentage', function () {
    $context = ($this->makeProgramming)('A');

    // One outcome × one criterion × one enrollment = 1 required grade.
    Grade::factory()->create([
        'enrollment_id' => $context['enrollment']->id,
        'microcurricular_learning_outcome_id' => $context['outcome']->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => PerformanceLevel::factory()->create()->id,
        'graded_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('professor.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('programmings.0.grading_percentage', 100)
            ->where('programmings.0.enrolled_count', 1)
        );
});

test('dashboard reports zero percent when nothing is graded', function () {
    ($this->makeProgramming)('A');

    $this->actingAs($this->user)
        ->get(route('professor.dashboard'))
        ->assertInertia(fn ($page) => $page->where('programmings.0.grading_percentage', 0));
});

test('dashboard handles a programming with no enrolled students', function () {
    $space = AcademicSpace::factory()->create([
        'competency_id' => $this->competency->id,
        'code' => 'EMPTY',
    ]);
    MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $space->id,
        'type_id' => $this->outcomeType->id,
        'is_active' => true,
    ]);
    Programming::factory()->create([
        'academic_space_id' => $space->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
        'academic_period_id' => $this->period->id,
        'group' => 'Z',
    ]);

    $this->actingAs($this->user)
        ->get(route('professor.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('programmings.0.enrolled_count', 0)
            ->where('programmings.0.grading_percentage', 0)
        );
});
