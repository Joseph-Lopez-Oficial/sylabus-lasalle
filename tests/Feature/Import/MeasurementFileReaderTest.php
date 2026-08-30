<?php

use App\Services\Import\MeasurementFileReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->folder = sys_get_temp_dir().'/measurement-reader-'.uniqid();
    mkdir($this->folder);
});

afterEach(function () {
    if (is_dir($this->folder)) {
        array_map('unlink', glob($this->folder.'/*') ?: []);
        rmdir($this->folder);
    }
});

/**
 * Writes a spreadsheet with the structure the institutional format uses.
 *
 * @param  list<array{code: string, description: string, type: string}>  $outcomes
 * @param  list<array{document: string, name: string, levels: array<string, list<string>>}>  $students
 * @param  array<string, list<string>>  $analysis
 */
function writeMeasurementFile(
    string $folder,
    string $fileName,
    array $header,
    array $outcomes,
    array $students,
    array $analysis = [],
    bool $referenceDocuments = false
): string {
    $spreadsheet = new Spreadsheet;

    $consolidated = $spreadsheet->getActiveSheet();
    $consolidated->setTitle('Consolidado ');
    $consolidated->setCellValue('B4', 'RESULTADO FINAL '.$header['period']);
    $consolidated->setCellValue('C9', $header['professor']);
    $consolidated->setCellValue('C10', $header['academic_space']);
    $consolidated->setCellValue('C11', $header['group']);

    foreach ($outcomes as $index => $outcome) {
        $statement = $outcome['type'] === null
            ? "{$outcome['code']}. {$outcome['description']}"
            : "{$outcome['code']}. {$outcome['description']} ({$outcome['type']})";

        $consolidated->setCellValue('B'.(21 + $index), $statement);
    }

    $list = $spreadsheet->createSheet();
    $list->setTitle('Consolidado x Est');
    $list->setCellValue('B6', 'ID');

    foreach ($students as $index => $student) {
        $list->setCellValue('B'.(7 + $index), $student['document']);
        $list->setCellValue('C'.(7 + $index), $student['name']);
    }

    $seenPerType = [];
    foreach ($outcomes as $outcome) {
        $type = $outcome['sheet_type'] ?? $outcome['type'];
        $seenPerType[$type] = ($seenPerType[$type] ?? 0) + 1;
        $suffix = $seenPerType[$type] > 1 ? (string) $seenPerType[$type] : '';

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('RA '.$type.$suffix);

        $criteria = count($students[0]['levels'][$outcome['code']] ?? []);
        foreach (range(0, max($criteria, 1) - 1) as $column) {
            $sheet->setCellValue(chr(68 + $column).'17', 'Criterio '.($column + 1));
        }

        foreach ($students as $index => $student) {
            $row = 18 + $index;

            // The real files usually reference the student list through a
            // formula instead of repeating the document.
            $sheet->setCellValue(
                'B'.$row,
                $referenceDocuments ? "='Consolidado x Est'!B".(7 + $index) : $student['document']
            );

            foreach ($student['levels'][$outcome['code']] ?? [] as $column => $level) {
                $sheet->setCellValue(chr(68 + $column).$row, $level);
            }
        }
    }

    if ($analysis !== []) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Analisis del EA');
        $columns = ['Actitud' => 'B', 'Habilidad' => 'L', 'Conocimiento' => 'V'];

        foreach ($analysis as $type => $answers) {
            foreach ([12, 19, 25] as $index => $row) {
                if (isset($answers[$index])) {
                    $sheet->setCellValue($columns[$type].$row, $answers[$index]);
                }
            }
        }
    }

    $path = $folder.'/'.$fileName;
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

/** A knowledge outcome with two students, one criterion left unassessed. */
function readerFixture(string $folder, bool $referenceDocuments = false): string
{
    return writeMeasurementFile(
        $folder,
        'IAP_SOF_BasesDeDatosI_Grupo1_25-2.xlsx',
        ['period' => '2025-2', 'professor' => 'Fabio Hernandez', 'academic_space' => 'Bases De Datos I', 'group' => '1'],
        [['code' => 'RA31', 'description' => 'Diseña repositorios de datos.', 'type' => 'Conocimiento']],
        [
            [
                'document' => '1013159478',
                'name' => 'DIEGO ALEJANDRO FORERO HERNANDEZ',
                'levels' => ['RA31' => ['Excelente(4)', 'Excelente(4)', 'Bueno(3)', 'Bueno(3)']],
            ],
            [
                'document' => '1007160077',
                'name' => 'LABOREN NEGRON DIEGO ALEJANDRO',
                'levels' => ['RA31' => ['Bueno(3)', 'No Aplica', 'Bueno(3)', 'Excelente(4)']],
            ],
        ],
        ['Conocimiento' => ['El grupo alcanzó el resultado.', 'Participación constante.', 'Reforzar prácticas.']],
        $referenceDocuments
    );
}

// ── Encabezado ────────────────────────────────────────────────────────────────

test('the header of the file is read', function () {
    $data = app(MeasurementFileReader::class)->read(readerFixture($this->folder));

    expect($data['period'])->toBe('2025-2')
        ->and($data['professor'])->toBe('Fabio Hernandez')
        ->and($data['academic_space'])->toBe('Bases De Datos I')
        ->and($data['group'])->toBe('1');
});

test('the assessed outcome points at its grading sheet', function () {
    $data = app(MeasurementFileReader::class)->read(readerFixture($this->folder));

    expect($data['outcomes'])->toHaveCount(1)
        ->and($data['outcomes'][0]['code'])->toBe('RA31')
        ->and($data['outcomes'][0]['type'])->toBe('Conocimiento')
        ->and($data['outcomes'][0]['sheet'])->toBe('RA Conocimiento');
});

test('a second outcome of the same type points at the numbered sheet', function () {
    $path = writeMeasurementFile(
        $this->folder,
        'IAP_SOF_BasesDeDatosI_Grupo1_25-2.xlsx',
        ['period' => '2025-2', 'professor' => 'Fabio Hernandez', 'academic_space' => 'Bases De Datos I', 'group' => '1'],
        [
            ['code' => 'RA31', 'description' => 'Primero.', 'type' => 'Conocimiento'],
            ['code' => 'RA32', 'description' => 'Segundo.', 'type' => 'Conocimiento'],
        ],
        [['document' => '1013159478', 'name' => 'ALGUIEN', 'levels' => [
            'RA31' => ['Bueno(3)'],
            'RA32' => ['Excelente(4)'],
        ]]]
    );

    $data = app(MeasurementFileReader::class)->read($path);

    expect(array_column($data['outcomes'], 'sheet'))
        ->toBe(['RA Conocimiento', 'RA Conocimiento2']);
});

// ── Estudiantes y notas ───────────────────────────────────────────────────────

test('students are read from the consolidated list', function () {
    $data = app(MeasurementFileReader::class)->read(readerFixture($this->folder));

    expect(array_column($data['students'], 'document'))
        ->toBe(['1013159478', '1007160077']);
});

test('an unassessed criterion produces no grade', function () {
    $data = app(MeasurementFileReader::class)->read(readerFixture($this->folder));

    $second = array_values(array_filter($data['grades'], fn ($g) => $g['document'] === '1007160077'));

    expect($data['grades'])->toHaveCount(7)
        ->and($second)->toHaveCount(3)
        ->and(array_column($second, 'criterion_index'))->toBe([0, 2, 3]);
});

test('grades carry the order of the level assigned', function () {
    $data = app(MeasurementFileReader::class)->read(readerFixture($this->folder));

    $first = array_values(array_filter($data['grades'], fn ($g) => $g['document'] === '1013159478'));

    expect(array_column($first, 'level_order'))->toBe([4, 4, 3, 3]);
});

test('students are resolved by position when the sheet references the list', function () {
    // The real files hold a formula in the document cell, so the row's position
    // is what ties a mark to its student.
    $data = app(MeasurementFileReader::class)->read(readerFixture($this->folder, referenceDocuments: true));

    expect($data['grades'])->toHaveCount(7)
        ->and(array_unique(array_column($data['grades'], 'document')))
        ->toEqualCanonicalizing(['1013159478', '1007160077']);
});

test('a leftover filler document does not steal the marks', function () {
    // Templates ship with placeholder documents such as 10010 that are not
    // students; the consolidated list is what decides who was graded.
    $path = writeMeasurementFile(
        $this->folder,
        'IAP_SOF_BasesDeDatosI_Grupo1_25-2.xlsx',
        ['period' => '2025-2', 'professor' => 'Fabio Hernandez', 'academic_space' => 'Bases De Datos I', 'group' => '1'],
        [['code' => 'RA31', 'description' => 'Diseña repositorios.', 'type' => 'Conocimiento']],
        [['document' => '10010', 'name' => 'RELLENO DE PLANTILLA', 'levels' => ['RA31' => ['Bueno(3)']]]]
    );

    // Rewrite the grading row with a filler document while the list holds the
    // real student.
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    $spreadsheet->getSheetByName('Consolidado x Est')->setCellValue('B7', '1013159478');
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    $data = app(MeasurementFileReader::class)->read($path);

    expect(array_column($data['grades'], 'document'))->toBe(['1013159478']);
});

// ── Análisis ──────────────────────────────────────────────────────────────────

test('the analysis is read and tied to its outcome', function () {
    $data = app(MeasurementFileReader::class)->read(readerFixture($this->folder));

    expect($data['analyses'])->toHaveCount(1)
        ->and($data['analyses'][0]['outcome_code'])->toBe('RA31')
        ->and($data['analyses'][0]['answers'][0])->toBe('El grupo alcanzó el resultado.')
        ->and($data['analyses'][0]['answers'][2])->toBe('Reforzar prácticas.');
});

test('an analysis block marked as not applicable is skipped', function () {
    $path = writeMeasurementFile(
        $this->folder,
        'IAP_SOF_BasesDeDatosI_Grupo1_25-2.xlsx',
        ['period' => '2025-2', 'professor' => 'Fabio Hernandez', 'academic_space' => 'Bases De Datos I', 'group' => '1'],
        [['code' => 'RA31', 'description' => 'Diseña repositorios.', 'type' => 'Conocimiento']],
        [['document' => '1013159478', 'name' => 'ALGUIEN', 'levels' => ['RA31' => ['Bueno(3)']]]],
        ['Conocimiento' => ['No aplica', 'No aplica', 'No aplica']]
    );

    $data = app(MeasurementFileReader::class)->read($path);

    expect($data['analyses'])->toBeEmpty();
});

// ── Formato antiguo de 2025-1 ─────────────────────────────────────────────────

test('an outcome without its type is resolved from the catalogue', function () {
    // The 2025-1 files omit the type and name the attitude sheet differently.
    $path = writeMeasurementFile(
        $this->folder,
        'Seg_RA_LogicaDeProgramacionI_Grupo1_25-1.xlsx',
        ['period' => '2025-1', 'professor' => 'Natalia Martinez Rojas', 'academic_space' => 'Logica De Programación I', 'group' => '1'],
        [['code' => 'RA1', 'description' => 'Identifica modelos simbólicos.', 'type' => null, 'sheet_type' => 'Conocimiento']],
        [['document' => '1013159478', 'name' => 'ALGUIEN', 'levels' => ['RA1' => ['Bueno(3)']]]]
    );

    $data = app(MeasurementFileReader::class)->read($path);

    expect($data['outcomes'])->toHaveCount(1)
        ->and($data['outcomes'][0]['type'])->toBe('Conocimiento')
        ->and($data['grades'])->toHaveCount(1);
});

test('a file without a consolidated sheet is rejected', function () {
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->setTitle('Otra cosa');
    $path = $this->folder.'/sin-consolidado.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    expect(fn () => app(MeasurementFileReader::class)->read($path))
        ->toThrow(RuntimeException::class);
});
