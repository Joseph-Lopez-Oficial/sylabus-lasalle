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
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminUser = User::factory()->create(['role' => 'admin']);

    $this->faculty = Faculty::factory()->create();
    $this->program = Program::factory()->create(['faculty_id' => $this->faculty->id]);
    $this->nucleus = ProblematicNucleus::factory()->create(['program_id' => $this->program->id]);
    $this->competency = Competency::factory()->create(['problematic_nucleus_id' => $this->nucleus->id]);
    $this->academicSpace = AcademicSpace::factory()->create(['competency_id' => $this->competency->id]);
    $this->academicPeriod = AcademicPeriod::factory()->create(['is_active' => true]);

    $this->professor = Professor::factory()->create(['is_active' => true]);
    $this->programming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => Modality::factory()->create()->id,
        'academic_period_id' => $this->academicPeriod->id,
        'is_active' => true,
    ]);

    $this->outcomeType = MicrocurricularLearningOutcomeType::factory()->create();
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'type_id' => $this->outcomeType->id,
        'is_active' => true,
    ]);
    $this->criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->outcomeType->id,
    ]);
    $this->performanceLevel = PerformanceLevel::factory()->create(['order' => 3, 'name' => 'Competente']);

    $student = Student::factory()->create(['is_active' => true]);
    $gradedBy = User::factory()->create(['role' => 'admin']);

    $enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => $student->id,
        'is_active' => true,
    ]);

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->performanceLevel->id,
        'graded_by' => $gradedBy->id,
    ]);
});

// ── Admin: Programming Statistics Export ──────────────────────────────────────

test('admin can download programming statistics', function () {
    Excel::fake();

    $this->actingAs($this->adminUser)
        ->get(route('admin.programmings.statistics.export', $this->programming))
        ->assertOk();

    $expectedFile = 'estadisticas_programacion_'.$this->programming->id.'_'.now()->format('Ymd').'.xlsx';
    Excel::assertDownloaded($expectedFile);
});

test('guest cannot access programming statistics export', function () {
    $this->get(route('admin.programmings.statistics.export', $this->programming))
        ->assertRedirect(route('login'));
});

test('professor cannot access admin programming statistics export', function () {
    $professorUser = User::factory()->create(['role' => 'professor']);

    $this->actingAs($professorUser)
        ->get(route('admin.programmings.statistics.export', $this->programming))
        ->assertForbidden();
});

// ── Admin: Microcurricular Outcome Export ────────────────────────────────────

test('admin can download microcurricular outcome report', function () {
    Excel::fake();

    $this->actingAs($this->adminUser)
        ->get(route('admin.microcurricular-outcomes.export', $this->outcome))
        ->assertOk();

    $expectedFile = 'reporte_ra_'.$this->outcome->id.'_'.now()->format('Ymd').'.xlsx';
    Excel::assertDownloaded($expectedFile);
});

test('guest cannot access microcurricular outcome export', function () {
    $this->get(route('admin.microcurricular-outcomes.export', $this->outcome))
        ->assertRedirect(route('login'));
});

// ── Admin: Academic Space Statistics Export ──────────────────────────────────

test('admin can download academic space statistics', function () {
    Excel::fake();

    $this->actingAs($this->adminUser)
        ->get(route('admin.academic-spaces.statistics.export', $this->academicSpace))
        ->assertOk();

    $expectedFile = 'estadisticas_espacio_'.$this->academicSpace->id.'_'.now()->format('Ymd').'.xlsx';
    Excel::assertDownloaded($expectedFile);
});

test('guest cannot access academic space statistics export', function () {
    $this->get(route('admin.academic-spaces.statistics.export', $this->academicSpace))
        ->assertRedirect(route('login'));
});

test('academic space statistics export returns 422 when space has no grades', function () {
    $emptySpace = AcademicSpace::factory()->create(['competency_id' => $this->competency->id]);

    $this->actingAs($this->adminUser)
        ->get(route('admin.academic-spaces.statistics.export', $emptySpace))
        ->assertStatus(422);
});
