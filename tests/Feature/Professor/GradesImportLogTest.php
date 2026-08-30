<?php

use App\Models\AcademicPeriod;
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

uses(RefreshDatabase::class);

/**
 * Writes a CSV the GradesImport can parse: an identity column plus one
 * "ra{outcomeId}_{criterionId}" grade column per cell.
 */
function writeGradesCsv(string $path, array $headings, array $rows): UploadedFile
{
    $handle = fopen($path, 'w');
    fputcsv($handle, $headings);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    // A plain-text CSV is sniffed as text/plain, which the mimes rule rejects,
    // so declare the extension explicitly and mark the file as a test upload.
    return new UploadedFile($path, 'grades.csv', 'text/csv', null, true);
}

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'professor']);
    $this->professor = Professor::factory()->create(['user_id' => $this->user->id]);

    $faculty = Faculty::factory()->create();
    $program = Program::factory()->create(['faculty_id' => $faculty->id]);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id]);
    $space = AcademicSpace::factory()->create(['competency_id' => $competency->id]);

    $this->outcomeType = MicrocurricularLearningOutcomeType::factory()->create();
    $this->criterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $this->outcomeType->id,
    ]);
    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $space->id,
        'type_id' => $this->outcomeType->id,
        'is_active' => true,
    ]);
    PerformanceLevel::factory()->create(['name' => 'Competente', 'order' => 3]);

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $space->id,
        'professor_id' => $this->professor->id,
        'modality_id' => Modality::factory()->create()->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
    ]);

    $this->enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => Student::factory()->create()->id,
        'is_active' => true,
    ]);

    $this->csvPath = sys_get_temp_dir().'/grades_'.uniqid().'.csv';
});

afterEach(function () {
    if (isset($this->csvPath) && file_exists($this->csvPath)) {
        unlink($this->csvPath);
    }
});

test('a clean import is logged as completed', function () {
    $file = writeGradesCsv(
        $this->csvPath,
        ['enrollment_id', "ra{$this->outcome->id}_{$this->criterion->id}"],
        [[$this->enrollment->id, 'Competente']]
    );

    $this->actingAs($this->user)
        ->post(route('professor.programmings.grading.import', $this->programming), ['file' => $file])
        ->assertOk();

    $log = ImportLog::where('programming_id', $this->programming->id)->firstOrFail();

    expect($log->status)->toBe('completed')
        ->and($log->failed_rows)->toBe(0)
        ->and($log->successful_rows)->toBe(1);

    expect(Grade::where('enrollment_id', $this->enrollment->id)->exists())->toBeTrue();
});

test('an import with a bad row is logged as partial', function () {
    $file = writeGradesCsv(
        $this->csvPath,
        ['enrollment_id', "ra{$this->outcome->id}_{$this->criterion->id}"],
        [
            [$this->enrollment->id, 'Competente'],
            [999999, 'Competente'],           // enrollment not in this programming
        ]
    );

    $this->actingAs($this->user)
        ->post(route('professor.programmings.grading.import', $this->programming), ['file' => $file])
        ->assertOk();

    $log = ImportLog::where('programming_id', $this->programming->id)->firstOrFail();

    expect($log->status)->toBe('partial')
        ->and($log->failed_rows)->toBe(1)
        ->and($log->successful_rows)->toBe(1);
});

test('the import status value fits the status column', function () {
    expect(strlen('partial'))->toBeLessThanOrEqual(20);
});

test('import rejects a grade whose criterion type does not match the outcome', function () {
    $foreignType = MicrocurricularLearningOutcomeType::factory()->create();
    $foreignCriterion = EvaluationCriterion::factory()->create([
        'microcurricular_learning_outcome_type_id' => $foreignType->id,
    ]);

    $file = writeGradesCsv(
        $this->csvPath,
        ['enrollment_id', "ra{$this->outcome->id}_{$foreignCriterion->id}"],
        [[$this->enrollment->id, 'Competente']]
    );

    $this->actingAs($this->user)
        ->post(route('professor.programmings.grading.import', $this->programming), ['file' => $file])
        ->assertOk();

    expect(Grade::where('evaluation_criterion_id', $foreignCriterion->id)->exists())->toBeFalse();

    $log = ImportLog::where('programming_id', $this->programming->id)->firstOrFail();
    expect($log->status)->toBe('partial');
});
