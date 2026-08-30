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

    // Institutional scale: order 1..4 => 1.3 / 2.5 / 3.8 / 5.0
    $this->levels = collect([1, 2, 3, 4])->mapWithKeys(fn ($o) => [
        $o => PerformanceLevel::factory()->create(['order' => $o, 'name' => "Nivel {$o}"]),
    ]);

    $this->modality = Modality::factory()->create();
    $this->period = AcademicPeriod::factory()->create();

    $this->makeProgramming = function (string $group) {
        return Programming::factory()->create([
            'academic_space_id' => $this->space->id,
            'professor_id' => Professor::factory()->create()->id,
            'modality_id' => $this->modality->id,
            'academic_period_id' => $this->period->id,
            'group' => $group,
        ]);
    };

    $this->gradeStudent = function (Programming $programming, int $order) {
        $enrollment = Enrollment::factory()->create([
            'programming_id' => $programming->id,
            'student_id' => Student::factory()->create()->id,
            'is_active' => true,
        ]);

        Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'microcurricular_learning_outcome_id' => $this->outcome->id,
            'evaluation_criterion_id' => $this->criterion->id,
            'performance_level_id' => $this->levels[$order]->id,
            'graded_by' => $this->admin->id,
        ]);

        return $enrollment;
    };
});

test('BUG: outcome report global average is a mean of group means, not of students', function () {
    // Programming A: 1 student at 5.0
    $a = ($this->makeProgramming)('A');
    ($this->gradeStudent)($a, 4);

    // Programming B: 3 students at 1.3
    $b = ($this->makeProgramming)('B');
    foreach (range(1, 3) as $i) {
        ($this->gradeStudent)($b, 1);
    }

    // True student-weighted average = (5.0 + 1.3*3) / 4 = 2.225 -> 2.23
    // Mean of group means = (5.0 + 1.3) / 2 = 3.15
    $this->actingAs($this->admin)
        ->get(route('admin.microcurricular-outcomes.show', $this->outcome))
        ->assertInertia(fn ($page) => $page->where('summary.global_average', 2.23));
});

test('BUG: raw grades sheet crashes when a graded student is soft-deleted', function () {
    $programming = ($this->makeProgramming)('A');
    $enrollment = ($this->gradeStudent)($programming, 4);

    // Legacy corruption: student gone, enrollment left active.
    Student::withoutEvents(fn () => $enrollment->student->delete());

    $this->actingAs($this->admin)
        ->get(route('admin.programmings.statistics.export', $programming))
        ->assertOk();
});

test('BUG: outcome report crashes when a graded student is soft-deleted', function () {
    $programming = ($this->makeProgramming)('A');
    $enrollment = ($this->gradeStudent)($programming, 4);

    Student::withoutEvents(fn () => $enrollment->student->delete());

    $this->actingAs($this->admin)
        ->get(route('admin.microcurricular-outcomes.show', $this->outcome))
        ->assertOk();
});

test('BUG: academic space statistics crash when a graded student is soft-deleted', function () {
    $programming = ($this->makeProgramming)('A');
    $enrollment = ($this->gradeStudent)($programming, 4);

    Student::withoutEvents(fn () => $enrollment->student->delete());

    $this->actingAs($this->admin)
        ->get(route('admin.academic-spaces.show', $this->space))
        ->assertOk();
});

test('BUG: academic space export crashes when a graded student is soft-deleted', function () {
    $programming = ($this->makeProgramming)('A');
    $enrollment = ($this->gradeStudent)($programming, 4);

    Student::withoutEvents(fn () => $enrollment->student->delete());

    $this->actingAs($this->admin)
        ->get(route('admin.academic-spaces.statistics.export', $this->space))
        ->assertOk();
});

test('inactive enrollments are excluded from academic space statistics', function () {
    $programming = ($this->makeProgramming)('A');
    ($this->gradeStudent)($programming, 4);
    $inactive = ($this->gradeStudent)($programming, 1);
    $inactive->update(['is_active' => false]);

    $this->actingAs($this->admin)
        ->get(route('admin.academic-spaces.show', $this->space))
        ->assertInertia(fn ($page) => $page->where('statistics.by_programming.0.student_count', 1));
});

test('BUG: academic space global average is a mean of group means', function () {
    // Programming A: 1 student at 5.0
    $a = ($this->makeProgramming)('A');
    ($this->gradeStudent)($a, 4);

    // Programming B: 3 students at 1.3
    $b = ($this->makeProgramming)('B');
    foreach (range(1, 3) as $i) {
        ($this->gradeStudent)($b, 1);
    }

    // Student-weighted = (5.0 + 1.3*3) / 4 = 2.23; mean of group means = 3.15
    $this->actingAs($this->admin)
        ->get(route('admin.academic-spaces.show', $this->space))
        ->assertInertia(fn ($page) => $page->where('statistics.summary.global_average', 2.23));
});

test('the grading scale is read from the database', function () {
    expect(PerformanceLevel::gradeForOrder(1))->toBe(1.3)
        ->and(PerformanceLevel::gradeForOrder(2))->toBe(2.5)
        ->and(PerformanceLevel::gradeForOrder(3))->toBe(3.8)
        ->and(PerformanceLevel::gradeForOrder(4))->toBe(5.0);

    // An order with no level behind it has no grade, rather than degrading to
    // the order number itself, which was never a grade.
    expect(PerformanceLevel::gradeForOrder(9))->toBeNull();
});

test('inactive enrollments are excluded from the outcome report', function () {
    $programming = ($this->makeProgramming)('A');
    ($this->gradeStudent)($programming, 4);
    $inactive = ($this->gradeStudent)($programming, 1);
    $inactive->update(['is_active' => false]);

    // Only the active student (5.0) should count.
    $this->actingAs($this->admin)
        ->get(route('admin.microcurricular-outcomes.show', $this->outcome))
        ->assertInertia(fn ($page) => $page->where('summary.global_average', 5));
});
