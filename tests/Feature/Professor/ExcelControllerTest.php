<?php

use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Enrollment;
use App\Models\EvaluationCriterion;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\ImportLog;
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
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

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

    $this->outcomeType = MicrocurricularLearningOutcomeType::factory()->create();
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $academicSpace->id,
        'type_id' => $this->outcomeType->id,
        'is_active' => true,
    ]);
    $this->criterion = EvaluationCriterion::factory()->create();
    $this->performanceLevel = PerformanceLevel::factory()->create(['order' => 3, 'name' => 'Competente']);

    $student = Student::factory()->create(['is_active' => true]);
    $this->enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => $student->id,
        'is_active' => true,
    ]);

    $gradedBy = User::factory()->create(['role' => 'admin']);
    $this->grade = Grade::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => $this->criterion->id,
        'performance_level_id' => $this->performanceLevel->id,
        'graded_by' => $gradedBy->id,
    ]);
});

// ── Template download ─────────────────────────────────────────────────────────

test('professor can download grading template', function () {
    Excel::fake();

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.template', $this->programming))
        ->assertOk();

    $expectedFile = 'plantilla_calificaciones_'.$this->programming->id.'_'.now()->format('Ymd').'.xlsx';
    Excel::assertDownloaded($expectedFile);
});

test('professor cannot download template for another programming', function () {
    $otherProfessor = Professor::factory()->create();
    $otherProgramming = Programming::factory()->create([
        'academic_space_id' => $this->programming->academic_space_id,
        'professor_id' => $otherProfessor->id,
        'modality_id' => $this->programming->modality_id,
    ]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.template', $otherProgramming))
        ->assertForbidden();
});

// ── Grades import ─────────────────────────────────────────────────────────────

test('professor can import grades via excel', function () {
    Excel::fake();

    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.import', $this->programming), [
            'file' => UploadedFile::fake()->create(
                'grades.xlsx',
                100,
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ),
        ])
        ->assertOk()
        ->assertJsonStructure(['message', 'results']);

    Excel::assertImported('grades.xlsx');
});

test('import creates an import log entry', function () {
    Excel::fake();

    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.import', $this->programming), [
            'file' => UploadedFile::fake()->create(
                'grades.xlsx',
                100,
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ),
        ])
        ->assertOk();

    expect(ImportLog::where('programming_id', $this->programming->id)->exists())->toBeTrue();
});

test('import fails without file', function () {
    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.import', $this->programming), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

test('professor cannot import grades for another programming', function () {
    $otherProfessor = Professor::factory()->create();
    $otherProgramming = Programming::factory()->create([
        'academic_space_id' => $this->programming->academic_space_id,
        'professor_id' => $otherProfessor->id,
        'modality_id' => $this->programming->modality_id,
    ]);

    $this->actingAs($this->professorUser)
        ->postJson(route('professor.programmings.grading.import', $otherProgramming), [
            'file' => UploadedFile::fake()->create('grades.xlsx', 100),
        ])
        ->assertForbidden();
});

// ── Report export ─────────────────────────────────────────────────────────────

test('professor can download statistics report when grading is complete', function () {
    Excel::fake();

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.report', $this->programming))
        ->assertOk();

    $expectedFile = 'reporte_calificaciones_'.$this->programming->id.'_'.now()->format('Ymd').'.xlsx';
    Excel::assertDownloaded($expectedFile);
});

test('report download is rejected when grading is incomplete', function () {
    EvaluationCriterion::factory()->create();

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.report', $this->programming))
        ->assertStatus(422);
});

test('professor cannot download report for another programming', function () {
    $otherProfessor = Professor::factory()->create();
    $otherProgramming = Programming::factory()->create([
        'academic_space_id' => $this->programming->academic_space_id,
        'professor_id' => $otherProfessor->id,
        'modality_id' => $this->programming->modality_id,
    ]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.report', $otherProgramming))
        ->assertForbidden();
});

test('guest cannot access template download', function () {
    $this->get(route('professor.programmings.grading.template', $this->programming))
        ->assertRedirect(route('login'));
});
