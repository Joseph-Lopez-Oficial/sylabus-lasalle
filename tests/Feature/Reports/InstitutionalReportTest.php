<?php

use App\Exports\InstitutionalReportExport;
use App\Exports\Sheets\Institutional\AnalysisSheet;
use App\Models\AcademicPeriod;
use App\Models\AcademicSpace;
use App\Models\AcademicSpaceAnalysis;
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
use App\Services\InstitutionalReportBuilder;
use App\Services\InstitutionalReportImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    PerformanceLevel::forgetScaleCache();

    $this->seed(\Database\Seeders\MicrocurricularLearningOutcomeTypeSeeder::class);
    $this->seed(\Database\Seeders\ModalitySeeder::class);
    $this->seed(\Database\Seeders\EvaluationCriterionSeeder::class);
    $this->seed(\Database\Seeders\PerformanceLevelSeeder::class);

    $this->professorUser = User::factory()->create(['role' => 'professor']);
    $this->professor = Professor::factory()->create([
        'user_id' => $this->professorUser->id,
        'first_name' => 'Fabio',
        'last_name' => 'Hernandez',
        'is_active' => true,
    ]);

    $faculty = Faculty::factory()->create(['name' => 'Facultad de Ingeniería']);
    $program = Program::factory()->create(['faculty_id' => $faculty->id, 'name' => 'Ingeniería de Software']);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id, 'code' => 'C1']);

    $this->space = AcademicSpace::factory()->create([
        'competency_id' => $competency->id,
        'name' => 'Bases De Datos I',
    ]);

    $this->knowledge = MicrocurricularLearningOutcomeType::where('name', 'Conocimiento')->first();
    $this->skill = MicrocurricularLearningOutcomeType::where('name', 'Habilidad')->first();

    $this->outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->space->id,
        'type_id' => $this->knowledge->id,
        'code' => 'RA31',
        'description' => 'Diseña repositorios de datos.',
        'is_active' => true,
    ]);

    $this->programming = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => $this->professor->id,
        'modality_id' => Modality::first()->id,
        'academic_period_id' => AcademicPeriod::factory()->create(['name' => '2025-2'])->id,
        'group' => '1',
        'is_active' => true,
    ]);

    $this->enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => Student::factory()->create([
            'first_name' => 'Diego Alejandro',
            'last_name' => 'Forero Hernandez',
            'document_number' => '1013159478',
        ])->id,
        'is_active' => true,
    ]);

    $this->folder = sys_get_temp_dir().'/institutional-'.uniqid();
    mkdir($this->folder);

    // Uploads waiting for confirmation must not pile up in the project's own
    // storage while the suite runs.
    $this->uploads = $this->folder.'/uploads';
    config(['filesystems.disks.local.root' => $this->uploads]);
});

afterEach(function () {
    PerformanceLevel::forgetScaleCache();

    if (is_dir($this->folder)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->folder);
    }
});

/** Grades every criterion of the outcome at the given level order. */
function gradeEveryCriterion(int $levelOrder): void
{
    $test = test();
    $level = PerformanceLevel::where('order', $levelOrder)->first();

    foreach (EvaluationCriterion::where('microcurricular_learning_outcome_type_id', $test->knowledge->id)->get() as $criterion) {
        Grade::factory()->create([
            'enrollment_id' => $test->enrollment->id,
            'microcurricular_learning_outcome_id' => $test->outcome->id,
            'evaluation_criterion_id' => $criterion->id,
            'performance_level_id' => $level->id,
            'graded_by' => $test->professorUser->id,
        ]);
    }
}

/** Writes the report to disk and returns its path. */
function storeReport(): string
{
    $test = test();
    $path = $test->folder.'/reporte.xlsx';

    (new InstitutionalReportExport($test->programming, app(InstitutionalReportBuilder::class)))
        ->store('reporte.xlsx', 'local');

    // The disk is redirected in beforeEach, so its own path is the only one
    // that finds the file.
    $stored = \Illuminate\Support\Facades\Storage::disk('local')->path('reporte.xlsx');

    copy($stored, $path);
    @unlink($stored);

    return $path;
}

// ── Estructura del archivo generado ───────────────────────────────────────────

test('the report carries the institutional sheets in order', function () {
    $spreadsheet = IOFactory::load(storeReport());

    expect($spreadsheet->getSheetNames())
        ->toBe(['Consolidado', 'Consolidado x Est', 'RA Conocimiento', 'Analisis del EA']);
});

test('a second outcome of the same type gets a numbered sheet', function () {
    MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->space->id,
        'type_id' => $this->knowledge->id,
        'code' => 'RA32',
        'is_active' => true,
    ]);

    $spreadsheet = IOFactory::load(storeReport());

    expect($spreadsheet->getSheetNames())
        ->toContain('RA Conocimiento')
        ->toContain('RA Conocimiento2');
});

test('the consolidated sheet places every field where the format expects it', function () {
    $sheet = IOFactory::load(storeReport())->getSheetByName('Consolidado');

    expect(trim((string) $sheet->getCell('B4')->getValue()))->toBe('RESULTADO FINAL 2025-2')
        ->and(trim((string) $sheet->getCell('C9')->getValue()))->toBe('Fabio Hernandez')
        ->and(trim((string) $sheet->getCell('C10')->getValue()))->toBe('Bases De Datos I')
        ->and(trim((string) $sheet->getCell('C11')->getValue()))->toBe('1')
        ->and((string) $sheet->getCell('B21')->getValue())->toBe('RESULTADO DE APRENDIZAJE')
        ->and((string) $sheet->getCell('B22')->getValue())
        ->toBe('RA31. Diseña repositorios de datos. (Conocimiento)');
});

test('the grading sheet places the student table where the importer reads it', function () {
    gradeEveryCriterion(4);
    $sheet = IOFactory::load(storeReport())->getSheetByName('RA Conocimiento');

    expect((string) $sheet->getCell('B17')->getValue())->toBe('ID')
        ->and((string) $sheet->getCell('D17')->getValue())->toBe('Criterio 1')
        ->and((string) $sheet->getCell('B18')->getValue())->toBe('1013159478')
        ->and((string) $sheet->getCell('D18')->getValue())->toBe('Destacado(4)');
});

test('the rubric carries the configured criteria and their grade values', function () {
    $sheet = IOFactory::load(storeReport())->getSheetByName('RA Conocimiento');

    expect((string) $sheet->getCell('C6')->getValue())->toBe('Comprensión Conceptual')
        ->and((string) $sheet->getCell('C10')->getValue())->toBe('Nota equivalente')
        ->and((float) $sheet->getCell('D10')->getValue())->toBe(5.0);
});

test('an unassessed criterion is written as not applicable', function () {
    $sheet = IOFactory::load(storeReport())->getSheetByName('RA Conocimiento');

    expect((string) $sheet->getCell('D18')->getValue())->toBe('No Aplica')
        ->and((string) $sheet->getCell('H18')->getValue())->toBe('Sin Evaluar');
});

test('averages are written as values and not as formulas', function () {
    gradeEveryCriterion(4);
    $spreadsheet = IOFactory::load(storeReport());

    $total = (string) $spreadsheet->getSheetByName('RA Conocimiento')->getCell('H18')->getValue();
    $groupAverage = (string) $spreadsheet->getSheetByName('Consolidado')->getCell('D33')->getValue();

    expect($total)->not->toStartWith('=')
        ->and($groupAverage)->not->toStartWith('=')
        ->and((float) $total)->toBe(5.0);
});

test('the analysis sheet carries what the professor wrote', function () {
    AcademicSpaceAnalysis::factory()->create([
        'programming_id' => $this->programming->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'outcome_performance' => 'El grupo alcanzó el resultado.',
        'academic_space_performance' => 'Participación constante.',
        'improvement_proposals' => 'Reforzar prácticas.',
        'written_by' => $this->professorUser->id,
    ]);

    $sheet = IOFactory::load(storeReport())->getSheetByName('Analisis del EA');

    expect((string) $sheet->getCell('B10')->getValue())->toBe('El grupo alcanzó el resultado.')
        ->and((string) $sheet->getCell('B12')->getValue())->toBe('Participación constante.')
        ->and((string) $sheet->getCell('B14')->getValue())->toBe('Reforzar prácticas.');
});

test('no sheet freezes so much that it cannot be scrolled', function () {
    gradeEveryCriterion(4);
    $spreadsheet = IOFactory::load(storeReport());

    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $freeze = $sheet->getFreezePane();

        if ($freeze === null) {
            continue;
        }

        // Freezing at the grading table's header would pin the seventeen rows
        // of rubric above it and leave barely a strip able to scroll, which
        // reads as a broken file.
        $row = (int) preg_replace('/\D/', '', $freeze);

        expect($row)->toBeLessThanOrEqual(7, "La hoja «{$sheet->getTitle()}» congela demasiadas filas.");
    }
});

test('every sheet opens at the top', function () {
    $spreadsheet = IOFactory::load(storeReport());

    foreach ($spreadsheet->getAllSheets() as $sheet) {
        expect($sheet->getSelectedCells())->toBe('A1', "La hoja «{$sheet->getTitle()}» no abre en A1.");
    }
});

test('the file name follows the convention of the delivered files', function () {
    expect(app(InstitutionalReportBuilder::class)->fileName($this->programming))
        ->toBe('IAP_SOF_BasesDeDatosI_Grupo1_2025-2.xlsx');
});

// ── Descarga ──────────────────────────────────────────────────────────────────

test('the professor can download the report of their programming', function () {
    Excel::fake();

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.institutional-report', $this->programming))
        ->assertOk();

    Excel::assertDownloaded('IAP_SOF_BasesDeDatosI_Grupo1_2025-2.xlsx');
});

test('the report can be downloaded before grading is complete', function () {
    // Unlike the statistics report, this one exists to be filled in.
    Excel::fake();

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.institutional-report', $this->programming))
        ->assertOk();
});

test('a professor cannot download another professor programming', function () {
    $other = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => $this->programming->modality_id,
        'academic_period_id' => $this->programming->academic_period_id,
    ]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.programmings.grading.institutional-report', $other))
        ->assertForbidden();
});

test('the admin can download the report of any programming', function () {
    Excel::fake();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('admin.programmings.institutional-report', $this->programming))
        ->assertOk();
});

test('a professor cannot reach the admin download', function () {
    $this->actingAs($this->professorUser)
        ->get(route('admin.programmings.institutional-report', $this->programming))
        ->assertForbidden();
});

// ── Ida y vuelta ──────────────────────────────────────────────────────────────

test('a graded report imported back leaves the marks identical', function () {
    gradeEveryCriterion(3);

    $before = Grade::whereIn('enrollment_id', [$this->enrollment->id])
        ->orderBy('evaluation_criterion_id')
        ->pluck('performance_level_id')
        ->all();

    $path = storeReport();
    Grade::query()->delete();

    $result = app(InstitutionalReportImporter::class)
        ->import($path, $this->programming, $this->professorUser->id);

    $after = Grade::whereIn('enrollment_id', [$this->enrollment->id])
        ->orderBy('evaluation_criterion_id')
        ->pluck('performance_level_id')
        ->all();

    expect($result['errors'])->toBeEmpty()
        ->and($result['saved'])->toBe(count($before))
        ->and($after)->toBe($before);
});

test('a report filled in by hand loads the marks it carries', function () {
    $path = storeReport();

    // The professor picks levels from the dropdown the sheet offers.
    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getSheetByName('RA Conocimiento');
    foreach (['D', 'E', 'F', 'G'] as $column) {
        $sheet->setCellValue($column.'18', 'Competente(3)');
    }
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    $result = app(InstitutionalReportImporter::class)
        ->import($path, $this->programming, $this->professorUser->id);

    $levels = Grade::with('performanceLevel')->get()->pluck('performanceLevel.order')->unique()->values();

    expect($result['saved'])->toBe(4)
        ->and($result['errors'])->toBeEmpty()
        ->and($levels->all())->toBe([3]);
});

test('re-importing twice does not duplicate the marks', function () {
    gradeEveryCriterion(4);
    $path = storeReport();

    $importer = app(InstitutionalReportImporter::class);
    $importer->import($path, $this->programming, $this->professorUser->id);
    $count = Grade::count();
    $importer->import($path, $this->programming, $this->professorUser->id);

    expect(Grade::count())->toBe($count);
});

// ── Rechazos y errores ────────────────────────────────────────────────────────

test('a report of another programming is rejected', function () {
    $path = storeReport();

    $otherSpace = AcademicSpace::factory()->create([
        'competency_id' => $this->space->competency_id,
        'name' => 'Redes Y Comunicacion De Datos',
    ]);
    $other = Programming::factory()->create([
        'academic_space_id' => $otherSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->programming->modality_id,
        'academic_period_id' => $this->programming->academic_period_id,
    ]);

    expect(fn () => app(InstitutionalReportImporter::class)
        ->import($path, $other, $this->professorUser->id))
        ->toThrow(RuntimeException::class);
});

test('a row added for someone outside the programming never becomes a grade', function () {
    $path = storeReport();

    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getSheetByName('RA Conocimiento');

    // A row appended beneath the real student, for someone not enrolled.
    $sheet->setCellValue('B19', '9999999999');
    $sheet->setCellValue('C19', 'AJENO A LA PROGRAMACION');
    foreach (['D', 'E', 'F', 'G'] as $column) {
        $sheet->setCellValue($column.'18', 'Destacado(4)');
        $sheet->setCellValue($column.'19', 'Destacado(4)');
    }
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    $result = app(InstitutionalReportImporter::class)
        ->import($path, $this->programming, $this->professorUser->id);

    // The enrolled list is the authority on who sits in the group, so the
    // extra row is dropped and only the real student's marks are stored.
    expect($result['saved'])->toBe(4)
        ->and(Grade::count())->toBe(4)
        ->and(Grade::whereIn('enrollment_id', [$this->enrollment->id])->count())->toBe(4);
});

test('a mark for a student of another programming is refused', function () {
    // Reaching the importer directly is what a crafted request would do.
    $foreign = Enrollment::factory()->create([
        'programming_id' => Programming::factory()->create([
            'academic_space_id' => $this->space->id,
            'professor_id' => $this->professor->id,
            'modality_id' => $this->programming->modality_id,
            'academic_period_id' => $this->programming->academic_period_id,
            'group' => '99',
        ])->id,
        'student_id' => Student::factory()->create()->id,
        'is_active' => true,
    ]);

    expect(fn () => app(\App\Services\GradingService::class)->saveGrades([[
        'enrollment_id' => $foreign->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'evaluation_criterion_id' => EvaluationCriterion::first()->id,
        'performance_level_id' => PerformanceLevel::first()->id,
    ]], $this->professorUser->id, $this->programming))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(Grade::count())->toBe(0);
});

test('an unrecognised level is skipped rather than guessed', function () {
    $path = storeReport();

    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getSheetByName('RA Conocimiento');
    $sheet->setCellValue('D18', 'Excelentísimo(9)');
    $sheet->setCellValue('E18', 'Destacado(4)');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    $result = app(InstitutionalReportImporter::class)
        ->import($path, $this->programming, $this->professorUser->id);

    expect($result['saved'])->toBe(1)
        ->and(Grade::count())->toBe(1);
});

// ── Análisis ──────────────────────────────────────────────────────────────────

test('a differing analysis is reported instead of overwritten', function () {
    AcademicSpaceAnalysis::factory()->create([
        'programming_id' => $this->programming->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'outcome_performance' => 'Texto guardado en el sistema.',
        'academic_space_performance' => null,
        'improvement_proposals' => null,
        'written_by' => $this->professorUser->id,
    ]);

    $path = storeReport();

    $spreadsheet = IOFactory::load($path);
    $spreadsheet->getSheetByName('Analisis del EA')->setCellValue('B10', 'Texto distinto en el archivo.');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    $result = app(InstitutionalReportImporter::class)
        ->import($path, $this->programming, $this->professorUser->id);

    expect($result['analysis_conflicts'])->toHaveCount(1)
        ->and($result['analysis_conflicts'][0]['stored'])->toBe('Texto guardado en el sistema.')
        ->and($result['analysis_conflicts'][0]['incoming'])->toBe('Texto distinto en el archivo.')
        // Nothing was replaced: the professor decides.
        ->and(AcademicSpaceAnalysis::first()->outcome_performance)->toBe('Texto guardado en el sistema.');
});

test('the analysis is applied only when confirmed', function () {
    AcademicSpaceAnalysis::factory()->create([
        'programming_id' => $this->programming->id,
        'microcurricular_learning_outcome_id' => $this->outcome->id,
        'outcome_performance' => 'Texto guardado en el sistema.',
        'written_by' => $this->professorUser->id,
    ]);

    $path = storeReport();
    $spreadsheet = IOFactory::load($path);
    $spreadsheet->getSheetByName('Analisis del EA')->setCellValue('B10', 'Texto distinto en el archivo.');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    app(InstitutionalReportImporter::class)
        ->applyAnalysis($path, $this->programming, $this->professorUser->id);

    expect(AcademicSpaceAnalysis::first()->outcome_performance)
        ->toBe('Texto distinto en el archivo.');
});

test('an analysis written in the file and absent in the system is not a conflict', function () {
    $path = storeReport();

    $spreadsheet = IOFactory::load($path);
    $spreadsheet->getSheetByName('Analisis del EA')->setCellValue('B10', 'Primer análisis.');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    $result = app(InstitutionalReportImporter::class)
        ->import($path, $this->programming, $this->professorUser->id);

    // There is nothing to lose, so it is still reported for the professor to
    // apply, but with no stored text behind it.
    expect($result['analysis_conflicts'])->toHaveCount(1)
        ->and($result['analysis_conflicts'][0]['stored'])->toBeNull();
});

// ── Subida desde la interfaz ──────────────────────────────────────────────────

/** The stored report as the upload the professor would send. */
function uploadedReport(string $path): \Illuminate\Http\UploadedFile
{
    return new \Illuminate\Http\UploadedFile(
        $path,
        'IAP_SOF_BasesDeDatosI_Grupo1_2025-2.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

test('the professor uploads the filled report and the marks are recorded', function () {
    gradeEveryCriterion(3);
    $expected = Grade::orderBy('evaluation_criterion_id')->pluck('performance_level_id')->all();

    $path = storeReport();
    Grade::query()->delete();

    $response = $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.import', $this->programming),
            ['file' => uploadedReport($path)]
        );

    $response->assertOk()
        ->assertJsonPath('saved', count($expected))
        ->assertJsonPath('skipped', 0);

    expect(Grade::orderBy('evaluation_criterion_id')->pluck('performance_level_id')->all())
        ->toBe($expected);
});

test('the upload is written down in the import history', function () {
    gradeEveryCriterion(4);
    $path = storeReport();

    $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.import', $this->programming),
            ['file' => uploadedReport($path)]
        )->assertOk();

    $log = ImportLog::first();

    expect($log)->not->toBeNull()
        ->and($log->programming_id)->toBe($this->programming->id)
        ->and($log->imported_by)->toBe($this->professorUser->id)
        ->and($log->successful_rows)->toBe(4)
        ->and($log->status)->toBe('completed');
});

test('a report of another programming is refused by the route', function () {
    $path = storeReport();

    $otherSpace = AcademicSpace::factory()->create([
        'competency_id' => $this->space->competency_id,
        'name' => 'Redes Y Comunicacion De Datos',
    ]);
    $other = Programming::factory()->create([
        'academic_space_id' => $otherSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->programming->modality_id,
        'academic_period_id' => $this->programming->academic_period_id,
    ]);

    $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.import', $other),
            ['file' => uploadedReport($path)]
        )
        ->assertStatus(422)
        ->assertJsonPath('message', 'El archivo no corresponde a esta programación: revise el espacio académico y el período.');

    expect(Grade::count())->toBe(0)
        ->and(ImportLog::count())->toBe(0);
});

test('a professor cannot upload the report of another professor', function () {
    $path = storeReport();

    $other = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => $this->programming->modality_id,
        'academic_period_id' => $this->programming->academic_period_id,
    ]);

    $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.import', $other),
            ['file' => uploadedReport($path)]
        )
        ->assertForbidden();
});

test('the upload rejects a file that is not a spreadsheet', function () {
    $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.import', $this->programming),
            ['file' => \Illuminate\Http\UploadedFile::fake()->create('notas.pdf', 20, 'application/pdf')]
        )
        ->assertSessionHasErrors('file');
});

// ── Confirmación del análisis ─────────────────────────────────────────────────

/** Stores the report with a differing analysis and returns its path. */
function reportWithDifferingAnalysis(): string
{
    $test = test();

    AcademicSpaceAnalysis::factory()->create([
        'programming_id' => $test->programming->id,
        'microcurricular_learning_outcome_id' => $test->outcome->id,
        'outcome_performance' => 'Texto guardado en el sistema.',
        'written_by' => $test->professorUser->id,
    ]);

    $path = storeReport();
    $spreadsheet = IOFactory::load($path);

    // La primera respuesta del primer bloque, deducida de la hoja en lugar de
    // escrita a mano: si la maquetación cambia, el test la sigue.
    $answerCell = 'B'.(AnalysisSheet::FIRST_BLOCK_ROW + 4);

    $spreadsheet->getSheetByName('Analisis del EA')
        ->setCellValue($answerCell, 'Texto distinto en el archivo.');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

test('a differing analysis comes back as a conflict with a token to confirm', function () {
    $path = reportWithDifferingAnalysis();

    $response = $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.import', $this->programming),
            ['file' => uploadedReport($path)]
        )->assertOk();

    $response->assertJsonCount(1, 'analysis_conflicts')
        ->assertJsonPath('analysis_conflicts.0.stored', 'Texto guardado en el sistema.');

    expect($response->json('token'))->not->toBeNull()
        // Nothing replaced until the professor says so.
        ->and(AcademicSpaceAnalysis::first()->outcome_performance)->toBe('Texto guardado en el sistema.');

    $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.analysis', $this->programming),
            ['token' => $response->json('token')]
        )
        ->assertOk()
        ->assertJsonPath('applied', 1);

    expect(AcademicSpaceAnalysis::first()->outcome_performance)
        ->toBe('Texto distinto en el archivo.');
});

test('an import without conflicts keeps no file and issues no token', function () {
    gradeEveryCriterion(4);
    $path = storeReport();

    $response = $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.import', $this->programming),
            ['file' => uploadedReport($path)]
        )->assertOk();

    expect($response->json('token'))->toBeNull()
        ->and($response->json('analysis_conflicts'))->toBeEmpty()
        ->and(\Illuminate\Support\Facades\Storage::disk('local')->allFiles('institutional-imports'))->toBeEmpty();
});

test('the confirmation refuses a token of another programming', function () {
    $path = reportWithDifferingAnalysis();

    $token = $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.import', $this->programming),
            ['file' => uploadedReport($path)]
        )->json('token');

    // Sin token no hay nada que replicar, y el fallo diría «falta el token»
    // en lugar de señalar que la importación no vio el conflicto.
    expect($token)->not->toBeNull();

    // The token was issued for this group, so replaying it on another one must
    // not reach the file kept aside.
    $other = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->programming->modality_id,
        'academic_period_id' => $this->programming->academic_period_id,
        'group' => '77',
    ]);

    $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.analysis', $other),
            ['token' => $token]
        )
        ->assertStatus(422);

    expect(AcademicSpaceAnalysis::first()->outcome_performance)
        ->toBe('Texto guardado en el sistema.');
});

test('the confirmation refuses a token that was already used', function () {
    $path = reportWithDifferingAnalysis();

    $token = $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.import', $this->programming),
            ['file' => uploadedReport($path)]
        )->json('token');

    $route = route('professor.programmings.grading.institutional-report.analysis', $this->programming);

    $this->actingAs($this->professorUser)->post($route, ['token' => $token])->assertOk();
    $this->actingAs($this->professorUser)->post($route, ['token' => $token])->assertStatus(422);
});

test('a professor cannot confirm the analysis of another professor', function () {
    $other = Programming::factory()->create([
        'academic_space_id' => $this->space->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => $this->programming->modality_id,
        'academic_period_id' => $this->programming->academic_period_id,
    ]);

    $this->actingAs($this->professorUser)
        ->post(
            route('professor.programmings.grading.institutional-report.analysis', $other),
            ['token' => (string) \Illuminate\Support\Str::uuid()]
        )
        ->assertForbidden();
});
