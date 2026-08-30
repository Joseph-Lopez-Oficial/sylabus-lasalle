<?php

use App\Services\Import\MeasurementNormalizer;

beforeEach(fn () => $this->normalizer = new MeasurementNormalizer);

// ── Clasificación de archivos ─────────────────────────────────────────────────

test('non measurement files are recognised', function (string $fileName) {
    expect($this->normalizer->isMeasurementFile($fileName))->toBeFalse();
})->with([
    'Informe_de_Competencias_IS_2024_2_IntelliBoard (1).xlsx',
    'V2. Matriz ajustes RA y otros INGSW.xlsx',
    'Mapa de RA IS.xlsx',
    '2025-2_Marco_competencias_Ing._Software.xlsx',
]);

test('a measurement file is not mistaken for something else', function () {
    expect($this->normalizer->isMeasurementFile('IAP_SOF_BasesDeDatosI_Grupo1_25-2.xlsm'))->toBeTrue();
});

test('blank templates are recognised by their name', function () {
    expect($this->normalizer->isBlankTemplate('IAP_SOF_NombreEA_Grupo#_25-2.xlsm'))->toBeTrue()
        ->and($this->normalizer->isBlankTemplate('IAP_SOF_BasesDeDatosI_Grupo1_25-2.xlsm'))->toBeFalse();
});

// ── Clave de comparación ──────────────────────────────────────────────────────

test('the comparison key ignores accents, case and punctuation', function () {
    expect($this->normalizer->key('Lógica De Programación I'))
        ->toBe($this->normalizer->key('LOGICA DE PROGRAMACION I'))
        ->and($this->normalizer->key('Interacción Humano-Computador'))
        ->toBe('interaccion humano computador');
});

// ── Nombres de persona ────────────────────────────────────────────────────────

test('a person name is normalised to a single form', function () {
    expect($this->normalizer->personName('  JUAN   ANDRES  CAMACHO '))
        ->toBe('Juan Andres Camacho');
});

test('a four word name splits into two given names and two surnames', function () {
    expect($this->normalizer->splitName('DANIEL SANTIAGO BELTRAN RIAÑO'))
        ->toBe(['Daniel Santiago', 'Beltran Riaño']);
});

test('a three word name keeps a single surname', function () {
    expect($this->normalizer->splitName('MARCELA GONZALEZ MEJIA'))
        ->toBe(['Marcela Gonzalez', 'Mejia']);
});

// ── Documentos ────────────────────────────────────────────────────────────────

test('a document keeps only its digits', function () {
    expect($this->normalizer->documentNumber('1.013.159.478'))->toBe('1013159478')
        ->and($this->normalizer->documentNumber('001014990741'))->toBe('001014990741')
        ->and($this->normalizer->documentNumber(1067604913))->toBe('1067604913');
});

test('a cell that is not a document is rejected', function () {
    expect($this->normalizer->documentNumber('Nombre del Estudiante'))->toBeNull()
        ->and($this->normalizer->documentNumber(''))->toBeNull()
        ->and($this->normalizer->documentNumber('123'))->toBeNull();
});

// ── Niveles de desempeño ──────────────────────────────────────────────────────

test('the spreadsheet levels translate to the stored order', function (string $text, int $order) {
    expect($this->normalizer->levelOrder($text))->toBe($order);
})->with([
    ['Excelente(4)', 4],
    ['Bueno(3)', 3],
    ['Satisfactorio(2)', 2],
    ['Insuficiente(1)', 1],
]);

test('an unassessed criterion produces no level', function (?string $text) {
    expect($this->normalizer->levelOrder($text))->toBeNull();
})->with([
    'No Aplica',
    'no aplica',
    '',
    null,
]);

// ── Resultados de aprendizaje ─────────────────────────────────────────────────

test('an outcome statement is split into code, description and type', function () {
    $parsed = $this->normalizer->parseOutcome(
        'RA42. Identifica conceptos básicos en redes de comunicaciones. (Conocimiento)'
    );

    expect($parsed['code'])->toBe('RA42')
        ->and($parsed['type'])->toBe('Conocimiento')
        ->and($parsed['description'])->toBe('Identifica conceptos básicos en redes de comunicaciones.');
});

test('an outcome separated by a comma is read the same way', function () {
    $parsed = $this->normalizer->parseOutcome(
        'RA31, Diseña repositorios de datos con el fin de dar solución. (Habilidad)'
    );

    expect($parsed['code'])->toBe('RA31')
        ->and($parsed['type'])->toBe('Habilidad');
});

test('an outcome without its type leaves the type unresolved', function () {
    // The 2025-1 format omits it; the catalogue is what supplies it later.
    $parsed = $this->normalizer->parseOutcome(
        'RA1. Identifica modelos simbólicos de problemas aplicados.'
    );

    expect($parsed['code'])->toBe('RA1')
        ->and($parsed['type'])->toBeNull();
});

test('a line that is not an outcome is rejected', function () {
    expect($this->normalizer->parseOutcome('RESULTADO DE APRENDIZAJE'))->toBeNull()
        ->and($this->normalizer->parseOutcome(''))->toBeNull();
});

test('the older type spellings map to the stored ones', function () {
    expect($this->normalizer->outcomeType('Conocimientos'))->toBe('Conocimiento')
        ->and($this->normalizer->outcomeType('Actitudinal'))->toBe('Actitud')
        ->and($this->normalizer->outcomeType('Habilidad'))->toBe('Habilidad');
});

// ── Grupo ─────────────────────────────────────────────────────────────────────

test('the group is read from the file name, not from the cell', function () {
    // Professors copied the template without updating the cell, so files of
    // different groups declare the same number.
    expect($this->normalizer->group('IAP_SOF_ESTRUCTURASDISCRETAS_Grupo2D_26-1.xlsm', '1D'))
        ->toBe('2D');
});

test('the journey keeps two groups of the same number apart', function () {
    $presencial = $this->normalizer->group('IAP_SOF_EstructurasDiscretasPresencial_Grupo01_25-2.xlsm', '1');
    $virtual = $this->normalizer->group('IAP_SOF_EstructurasDiscretasVirtual_Grupo1_25-2.xlsm', '1');

    expect($presencial)->not->toBe($virtual);
});

test('the distance cohort is its own group', function () {
    $regular = $this->normalizer->group('IAP_SOF_LOGICA_DE_PROGRAMACION_II_Grupo1_2026-1.xlsx', '1');
    $distance = $this->normalizer->group('IAP_SOF_LOGICA_DE_PROGRAMACION_II_CD_Grupo1_2026-1.xlsx', '1');

    expect($regular)->not->toBe($distance);
});

test('a group written as GR01 is read', function () {
    expect($this->normalizer->group('IAP_SOF_Lenguaje de programación2-GR02 2026-1.xlsm', ''))
        ->toBe('2');
});

test('leading zeros do not create a different group', function () {
    expect($this->normalizer->group('X_Grupo01_25-2.xlsm', ''))
        ->toBe($this->normalizer->group('X_Grupo1_25-2.xlsm', ''));
});

test('a cell holding a competency statement is not taken as a group', function () {
    $long = 'Analiza un problema y elabora una solución algorítmica de ingeniería.';

    expect($this->normalizer->group('Seg_RA_Lenguaje_25-1.xlsm', $long))->toBeNull();
});

// ── Período ───────────────────────────────────────────────────────────────────

test('the period is read from the file name even with underscores around it', function () {
    expect($this->normalizer->period(null, 'IAP_SOF_SistemasOperativos_Grupo1_26-1.xlsm'))
        ->toBe('2026-1');
});

test('the file name wins over a mistyped header', function () {
    // One file states 2026-2 in its header while sitting in 2026-1.
    expect($this->normalizer->period('RESULTADO FINAL 2026-2', 'IAP_SOF_SistemasOperativos_Grupo1_26-1.xlsm'))
        ->toBe('2026-1');
});

test('the header is used when the name says nothing', function () {
    expect($this->normalizer->period('RESULTADO FINAL 2025-2', 'Bases de datos I.xlsm'))
        ->toBe('2025-2');
});

// ── Equivalencias declaradas ──────────────────────────────────────────────────

test('declared professor variants resolve to one name', function () {
    expect($this->normalizer->professor('Jeferson Urrego'))->toBe('Jefferson Urrego')
        ->and($this->normalizer->professor('JHON ALEXIS MENDEZ LARA'))->toBe('Alexis Mendez');
});

test('a professor without a declared variant keeps their name', function () {
    expect($this->normalizer->professor('FABIO HERNANDEZ'))->toBe('Fabio Hernandez');
});

test('declared academic space variants resolve to the catalogue name', function () {
    expect($this->normalizer->academicSpace('Lenguaje de Programación I CD'))
        ->toBe('Lenguaje De Programación I')
        ->and($this->normalizer->academicSpace('Calidad de Software II'))
        ->toBe('Calidad En Software II');
});

test('a space declared as foreign to the programme is excluded', function () {
    expect($this->normalizer->academicSpace('Teoría de Control'))->toBeNull();
});

test('the academic space can be recovered from the file name', function () {
    expect($this->normalizer->academicSpaceFromFileName('IAP_SOF_HabilidadesInvestigativasI_Presencial_Grupo1_25-2.xlsm'))
        ->toBe('Habilidades Investigativas I');
});
