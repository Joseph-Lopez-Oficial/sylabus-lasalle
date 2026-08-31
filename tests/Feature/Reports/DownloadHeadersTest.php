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
    PerformanceLevel::forgetScaleCache();

    $this->seed(\Database\Seeders\MicrocurricularLearningOutcomeTypeSeeder::class);
    $this->seed(\Database\Seeders\ModalitySeeder::class);
    $this->seed(\Database\Seeders\EvaluationCriterionSeeder::class);
    $this->seed(\Database\Seeders\PerformanceLevelSeeder::class);

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->professorUser = User::factory()->create(['role' => 'professor']);
    $this->professor = Professor::factory()->create([
        'user_id' => $this->professorUser->id,
        'is_active' => true,
    ]);

    $competency = Competency::factory()->create([
        'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
            'program_id' => Program::factory()->create([
                'faculty_id' => Faculty::factory()->create()->id,
            ])->id,
        ])->id,
    ]);

    $this->space = AcademicSpace::factory()->create(['competency_id' => $competency->id]);

    $type = MicrocurricularLearningOutcomeType::where('name', 'Conocimiento')->first();
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->space->id,
        'type_id' => $type->id,
        'is_active' => true,
    ]);

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => $this->professor->id,
        'modality_id' => Modality::first()->id,
        'academic_period_id' => AcademicPeriod::factory()->create(['name' => '2025-2'])->id,
        'is_active' => true,
    ]);

    $enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => Student::factory()->create()->id,
        'is_active' => true,
    ]);

    // Every criterion graded, so the reports that demand completeness pass.
    $level = PerformanceLevel::where('order', 3)->first();
    foreach (EvaluationCriterion::where('microcurricular_learning_outcome_type_id', $type->id)->get() as $criterion) {
        Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'microcurricular_learning_outcome_id' => $this->outcome->id,
            'evaluation_criterion_id' => $criterion->id,
            'performance_level_id' => $level->id,
            'graded_by' => $this->professorUser->id,
        ]);
    }
});

afterEach(fn () => PerformanceLevel::forgetScaleCache());

/**
 * Every file the interface offers, with the role allowed to ask for it.
 *
 * The download button reads the file name off the response instead of guessing
 * it, so a route that forgets to state one hands the user "descarga.xlsx".
 *
 * @return list<array{string, string}>
 */
dataset('downloads', function () {
    return [
        'plantilla de estudiantes' => ['admin', fn () => route('admin.students.template')],
        'plantilla de inscripciones (admin)' => ['admin', fn () => route('admin.programmings.enrollments.template', test()->programming)],
        'estadísticas de programación' => ['admin', fn () => route('admin.programmings.statistics.export', test()->programming)],
        'reporte institucional (admin)' => ['admin', fn () => route('admin.programmings.institutional-report', test()->programming)],
        'estadísticas de espacio académico' => ['admin', fn () => route('admin.academic-spaces.statistics.export', test()->space)],
        'reporte de resultado de aprendizaje' => ['admin', fn () => route('admin.microcurricular-outcomes.export', test()->outcome)],
        'plantilla de calificaciones' => ['professor', fn () => route('professor.programmings.grading.template', test()->programming)],
        'plantilla de inscripciones (profesor)' => ['professor', fn () => route('professor.programmings.enrollments.template', test()->programming)],
        'reporte de calificaciones' => ['professor', fn () => route('professor.programmings.grading.report', test()->programming)],
        'reporte institucional (profesor)' => ['professor', fn () => route('professor.programmings.grading.institutional-report', test()->programming)],
    ];
});

test('every download states the name of the file it returns', function (string $role, Closure $route) {
    $user = $role === 'admin' ? $this->admin : $this->professorUser;

    $response = $this->actingAs($user)->get($route());

    $response->assertOk();

    $disposition = $response->headers->get('content-disposition');

    expect($disposition)->not->toBeNull()
        ->and($disposition)->toContain('attachment')
        ->and($disposition)->toMatch('/filename\*?=/');

    // The name must survive as something a file system accepts.
    preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $matches);

    expect($matches[1] ?? '')->not->toBe('')
        ->and($matches[1])->toEndWith('.xlsx');
})->with('downloads');

test('a failed generation answers with a readable message', function () {
    // The statistics report refuses an incomplete group, and the button shows
    // whatever message comes back instead of spinning forever.
    Grade::query()->delete();

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.report', $this->programming))
        ->assertStatus(422);
});
