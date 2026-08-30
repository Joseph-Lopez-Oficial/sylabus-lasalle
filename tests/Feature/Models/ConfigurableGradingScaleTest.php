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

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => Modality::factory()->create()->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
    ]);

    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->gradeStudent = function (PerformanceLevel $level) {
        $enrollment = Enrollment::factory()->create([
            'programming_id' => $this->programming->id,
            'student_id' => Student::factory()->create()->id,
            'is_active' => true,
        ]);

        Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'microcurricular_learning_outcome_id' => $this->outcome->id,
            'evaluation_criterion_id' => $this->criterion->id,
            'performance_level_id' => $level->id,
            'graded_by' => $this->admin->id,
        ]);

        return $enrollment;
    };
});

afterEach(fn () => PerformanceLevel::forgetScaleCache());

test('the scale comes from the database and not from a constant', function () {
    PerformanceLevel::factory()->create(['order' => 1, 'grade_value' => 1.3]);
    PerformanceLevel::factory()->create(['order' => 4, 'grade_value' => 5.0]);

    expect(PerformanceLevel::gradeForOrder(1))->toBe(1.3)
        ->and(PerformanceLevel::gradeForOrder(4))->toBe(5.0);
});

test('statistics follow a custom scale without touching any code', function () {
    // A 0-100 scale instead of the institutional 0-5 one.
    $low = PerformanceLevel::factory()->create(['order' => 1, 'grade_value' => 20.0]);
    $high = PerformanceLevel::factory()->create(['order' => 4, 'grade_value' => 100.0]);

    ($this->gradeStudent)($low);
    ($this->gradeStudent)($high);

    $stats = app(StatisticsService::class)->calculate($this->programming->fresh());

    expect($stats['summary']['overall_average'])->toBe(60.0);
});

test('changing a level value changes the average it produces', function () {
    $level = PerformanceLevel::factory()->create(['order' => 3, 'grade_value' => 3.8]);
    ($this->gradeStudent)($level);

    $before = app(StatisticsService::class)->calculate($this->programming->fresh());
    expect($before['summary']['overall_average'])->toBe(3.8);

    $level->update(['grade_value' => 4.5]);

    $after = app(StatisticsService::class)->calculate($this->programming->fresh());
    expect($after['summary']['overall_average'])->toBe(4.5);
});

test('a level without a value does not drag the average down', function () {
    $graded = PerformanceLevel::factory()->create(['order' => 4, 'grade_value' => 5.0]);
    $unvalued = PerformanceLevel::factory()->withoutGradeValue()->create(['order' => 7]);

    ($this->gradeStudent)($graded);
    ($this->gradeStudent)($unvalued);

    $stats = app(StatisticsService::class)->calculate($this->programming->fresh());

    // Averaging in a zero would give 2.5; skipping the unvalued level gives 5.0.
    expect($stats['summary']['overall_average'])->toBe(5.0);
});

test('gradeForOrder returns null for a level with no value', function () {
    PerformanceLevel::factory()->withoutGradeValue()->create(['order' => 7]);

    expect(PerformanceLevel::gradeForOrder(7))->toBeNull();
});

test('the below-basic threshold is derived from the flagged level', function () {
    PerformanceLevel::factory()->create([
        'order' => 2,
        'grade_value' => 2.5,
        'is_below_basic_threshold' => true,
    ]);

    expect(PerformanceLevel::belowBasicThreshold())->toBe(2.5);
});

test('the below-basic threshold follows the configured value', function () {
    PerformanceLevel::factory()->create([
        'order' => 2,
        'grade_value' => 60.0,
        'is_below_basic_threshold' => true,
    ]);

    expect(PerformanceLevel::belowBasicThreshold())->toBe(60.0);
});

test('the threshold falls back to the historical value when no level is flagged', function () {
    PerformanceLevel::factory()->create([
        'order' => 2,
        'grade_value' => 2.5,
        'is_below_basic_threshold' => false,
    ]);

    expect(PerformanceLevel::belowBasicThreshold())->toBe(2.5);
});

test('students below the configured threshold are reported as at risk', function () {
    PerformanceLevel::factory()->create([
        'order' => 2,
        'grade_value' => 3.0,
        'is_below_basic_threshold' => true,
    ]);
    $failing = PerformanceLevel::factory()->create(['order' => 1, 'grade_value' => 2.0]);
    $passing = PerformanceLevel::factory()->create(['order' => 4, 'grade_value' => 5.0]);

    ($this->gradeStudent)($failing);
    ($this->gradeStudent)($passing);

    $stats = app(StatisticsService::class)->calculate($this->programming->fresh());

    expect($stats['summary']['below_basic'])->toHaveCount(1)
        ->and($stats['summary']['below_basic'][0]['final_average'])->toBe(2.0);
});

test('the scale is read once and reused within a request', function () {
    PerformanceLevel::factory()->create(['order' => 1, 'grade_value' => 1.3]);
    PerformanceLevel::forgetScaleCache();

    DB::flushQueryLog();
    DB::enableQueryLog();
    foreach (range(1, 20) as $ignored) {
        PerformanceLevel::gradeForOrder(1);
    }
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBe(1, "Se ejecutaron {$queries} consultas para 20 traducciones de escala.");
});
