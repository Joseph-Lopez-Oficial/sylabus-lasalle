<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Reads one measurement spreadsheet into a plain array.
 *
 * Nothing here touches the database: the reader turns a file into the facts it
 * states, and the caller decides what to do with them. Cells are read raw and
 * never recalculated, because resolving the workbook's formulas takes minutes
 * per file and every value the import needs is stored literally.
 */
class MeasurementFileReader
{
    /** First row of the student list in a grading sheet. */
    private const GRADING_FIRST_ROW = 18;

    /**
     * Columns where a grading sheet holds its criteria.
     *
     * The rubric decides how many are actually used — four for knowledge, three
     * for skill and attitude — so the reader stops at the number of criteria
     * the sheet's own header declares.
     */
    private const CRITERION_COLUMNS = [4, 5, 6, 7];

    /** Row holding the "Criterio 1..n" header of a grading sheet. */
    private const CRITERION_HEADER_ROW = 17;

    /** Rows of the Consolidado sheet listing the assessed outcomes. */
    private const OUTCOME_ROWS = [21, 22, 23, 24, 25, 26];

    /** Rows of the analysis sheet holding each answer, per block. */
    private const ANALYSIS_ANSWER_ROWS = [
        1 => [12, 19, 25],
        2 => [39, 46, 52],
    ];

    /** Columns of the analysis sheet, one per outcome type. */
    private const ANALYSIS_COLUMNS = [
        'Actitud' => 2,
        'Habilidad' => 12,
        'Conocimiento' => 22,
    ];

    /**
     * Outcome type per code, read once from the programme catalogue.
     *
     * @var array<string, string>|null
     */
    private ?array $catalogTypes = null;

    public function __construct(
        private readonly MeasurementNormalizer $normalizer,
    ) {}

    /**
     * @return array{
     *   period: ?string, professor: string, academic_space: string, group: ?string,
     *   product: ?string, outcomes: list<array{code: string, description: string, type: string, sheet: string}>,
     *   students: list<array{document: string, name: string}>,
     *   grades: list<array{document: string, outcome_code: string, criterion_index: int, level_order: int}>,
     *   analyses: list<array{outcome_code: string, answers: array<int, string>}>
     * }
     */
    public function read(string $path): array
    {
        $spreadsheet = $this->load($path);
        $consolidated = $this->sheet($spreadsheet, fn (string $t) => str_starts_with($t, 'Consolidado') && ! str_contains($t, 'x Est'));

        if (! $consolidated) {
            throw new RuntimeException('El archivo no tiene hoja Consolidado.');
        }

        $fileName = basename($path);
        $outcomes = $this->outcomes($consolidated);
        $students = $this->students($spreadsheet);

        $data = [
            'period' => $this->normalizer->period($this->cell($consolidated, 2, 4), $fileName),
            'professor' => $this->normalizer->professor($this->cell($consolidated, 3, 9)),
            'academic_space' => $this->academicSpaceName($consolidated, $fileName),
            'group' => $this->normalizer->group($fileName, $this->cell($consolidated, 3, 11)),
            'product' => $this->cell($consolidated, 3, 27) ?: ($this->cell($consolidated, 3, 28) ?: null),
            'outcomes' => $outcomes,
            'students' => $students,
            'grades' => $this->grades($spreadsheet, $outcomes, $students),
            'analyses' => $this->analyses($spreadsheet, $outcomes),
        ];

        $spreadsheet->disconnectWorksheets();

        return $data;
    }

    private function load(string $path): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        return $reader->load($path);
    }

    /**
     * @param  callable(string): bool  $matches
     */
    private function sheet(Spreadsheet $spreadsheet, callable $matches): ?Worksheet
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if ($matches(trim($sheet->getTitle()))) {
                return $sheet;
            }
        }

        return null;
    }

    private function cell(Worksheet $sheet, int $column, int $row): string
    {
        $value = $sheet->getCell([$column, $row])->getValue();

        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    /**
     * Assessed outcomes, each tied to the grading sheet that holds its marks.
     *
     * The sheet is named after the outcome's type, numbered when the group
     * assesses more than one outcome of the same type, which is exactly how the
     * workbook's own formulas resolve them.
     *
     * @return list<array{code: string, description: string, type: string, sheet: string}>
     */
    private function outcomes(Worksheet $consolidated): array
    {
        $outcomes = [];
        $seenPerType = [];

        foreach (self::OUTCOME_ROWS as $row) {
            $parsed = $this->normalizer->parseOutcome($this->cell($consolidated, 2, $row));

            if ($parsed === null) {
                continue;
            }

            $type = $parsed['type'] ?? $this->catalogType($parsed['code']);

            if ($type === null) {
                continue;
            }

            $parsed['type'] = $type;
            $seenPerType[$type] = ($seenPerType[$type] ?? 0) + 1;
            $suffix = $seenPerType[$type] > 1 ? (string) $seenPerType[$type] : '';

            $outcomes[] = $parsed + ['sheet' => 'RA '.$type.$suffix];
        }

        return $outcomes;
    }

    /**
     * Academic space of the file.
     *
     * At least one file leaves the cell blank while its name states the space,
     * so the name stands in rather than losing the whole file.
     */
    private function academicSpaceName(Worksheet $consolidated, string $fileName): ?string
    {
        $fromCell = $this->cell($consolidated, 3, 10);

        if ($fromCell !== '') {
            return $this->normalizer->academicSpace($fromCell);
        }

        return $this->normalizer->academicSpaceFromFileName($fileName);
    }

    /**
     * Type declared for an outcome code in the programme catalogue.
     *
     * The 2025-1 format lists outcomes without their type, so the catalogue
     * that ships with the spreadsheets is what supplies it.
     */
    private function catalogType(string $code): ?string
    {
        if ($this->catalogTypes === null) {
            $path = database_path('data/program-catalog.json');
            $this->catalogTypes = [];

            if (is_file($path)) {
                $catalog = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

                foreach ($catalog['outcomes'] ?? [] as $outcome) {
                    $this->catalogTypes[$outcome['code']] = $outcome['type'];
                }
            }
        }

        return $this->catalogTypes[$code] ?? null;
    }

    /**
     * @return list<array{document: string, name: string}>
     */
    private function students(Spreadsheet $spreadsheet): array
    {
        $sheet = $this->sheet($spreadsheet, fn (string $t) => str_contains($t, 'Consolidado x Est'));

        if (! $sheet) {
            return [];
        }

        $students = [];
        $lastRow = $sheet->getHighestDataRow();

        // The list starts on row 7, and on row 6 in the 2025-1 format.
        $firstRow = $this->normalizer->key($this->cell($sheet, 2, 5)) === 'id' ? 6 : 7;

        for ($row = $firstRow; $row <= $lastRow; $row++) {
            $document = $this->normalizer->documentNumber($sheet->getCell([2, $row])->getValue());
            $name = $this->cell($sheet, 3, $row);

            if ($document === null || $name === '' || str_starts_with($name, '=')) {
                continue;
            }

            $students[] = ['document' => $document, 'name' => $name];
        }

        return $students;
    }

    /**
     * Marks read from every grading sheet the assessed outcomes point at.
     *
     * A criterion left as "No Aplica" produces no entry at all, so it stays
     * unassessed instead of being recorded as a grade.
     *
     * @param  list<array{code: string, sheet: string}>  $outcomes
     * @param  list<array{document: string, name: string}>  $students
     * @return list<array{document: string, outcome_code: string, criterion_index: int, level_order: int}>
     */
    private function grades(Spreadsheet $spreadsheet, array $outcomes, array $students): array
    {
        $grades = [];
        $enrolled = array_flip(array_column($students, 'document'));

        foreach ($outcomes as $outcome) {
            $sheet = $this->gradingSheet($spreadsheet, $outcome['sheet']);

            if (! $sheet) {
                continue;
            }

            $lastRow = $sheet->getHighestDataRow();
            $columns = $this->criterionColumns($sheet);

            for ($row = self::GRADING_FIRST_ROW; $row <= $lastRow; $row++) {
                // The student list is the authority on who sits in the group.
                // A literal document is trusted only when it appears there: the
                // cell often holds a formula, and where it holds a literal it
                // may be leftover filler from the template ("10010", "11011").
                // Otherwise the row's position identifies the student, since
                // row 18 lines up with the first name on the list.
                $literal = $this->normalizer->documentNumber($sheet->getCell([2, $row])->getValue());

                $document = ($literal !== null && isset($enrolled[$literal]))
                    ? $literal
                    : ($students[$row - self::GRADING_FIRST_ROW]['document'] ?? null);

                if ($document === null) {
                    continue;
                }

                foreach ($columns as $index => $column) {
                    $order = $this->normalizer->levelOrder($this->cell($sheet, $column, $row));

                    if ($order === null) {
                        continue;
                    }

                    $grades[] = [
                        'document' => $document,
                        'outcome_code' => $outcome['code'],
                        'criterion_index' => $index,
                        'level_order' => $order,
                    ];
                }
            }
        }

        return $grades;
    }

    /**
     * Grading sheet for an outcome, tolerating the older naming.
     *
     * The 2025-1 files call the attitude sheet "RA Actitudinal" while the rest
     * call it "RA Actitud".
     */
    private function gradingSheet(Spreadsheet $spreadsheet, string $name): ?Worksheet
    {
        $wanted = $this->normalizer->key($name);

        foreach ([$wanted, str_replace('actitud', 'actitudinal', $wanted)] as $candidate) {
            $sheet = $this->sheet(
                $spreadsheet,
                fn (string $t) => $this->normalizer->key($t) === $candidate
            );

            if ($sheet) {
                return $sheet;
            }
        }

        return null;
    }

    /**
     * Analysis laid out as one block per outcome, the shape the system writes.
     *
     * Each block states its outcome code beside the heading, so the answers are
     * tied to their outcome without depending on the order of the sheet.
     *
     * @param  list<array{code: string}>  $outcomes
     * @return list<array{outcome_code: string, answers: array<int, string>}>
     */
    private function generatedAnalyses(Worksheet $sheet, array $outcomes): array
    {
        $codes = array_column($outcomes, 'code');
        $analyses = [];
        $lastRow = $sheet->getHighestDataRow();

        for ($row = 6; $row <= $lastRow; $row++) {
            $code = trim($this->cell($sheet, 3, $row));

            if ($code === '' || ! in_array($code, $codes, true)) {
                continue;
            }

            // Answers sit two rows apart, each under its question.
            $answers = [];
            foreach ([4, 6, 8] as $index => $offset) {
                $text = $this->cell($sheet, 2, $row + $offset);

                if ($text !== '' && $this->normalizer->key($text) !== 'no aplica') {
                    $answers[$index] = $text;
                }
            }

            if ($answers !== []) {
                $analyses[] = ['outcome_code' => $code, 'answers' => $answers];
            }
        }

        return $analyses;
    }

    /**
     * Columns the sheet actually uses for criteria.
     *
     * The header labels them "Criterio 1", "Criterio 2"...; anything past the
     * last label belongs to the rubric legend, not to a criterion.
     *
     * @return list<int>
     */
    private function criterionColumns(Worksheet $sheet): array
    {
        $columns = [];

        foreach (self::CRITERION_COLUMNS as $column) {
            $header = $this->normalizer->key($this->cell($sheet, $column, self::CRITERION_HEADER_ROW));

            if (! str_starts_with($header, 'criterio')) {
                break;
            }

            $columns[] = $column;
        }

        return $columns === [] ? self::CRITERION_COLUMNS : $columns;
    }

    /**
     * Qualitative analysis, matched to each outcome by its type and position.
     *
     * The sheet lays the three answers out in a column per type, repeating the
     * block for a second outcome of the same type, which mirrors how the
     * grading sheets are numbered.
     *
     * @param  list<array{code: string, type: string}>  $outcomes
     * @return list<array{outcome_code: string, answers: array<int, string>}>
     */
    private function analyses(Spreadsheet $spreadsheet, array $outcomes): array
    {
        $sheet = $this->sheet(
            $spreadsheet,
            fn (string $t) => str_contains($this->normalizer->key($t), 'analisis')
        );

        if (! $sheet) {
            return [];
        }

        // Reports the system generates lay the analysis out as one block per
        // outcome down column B; the coordination's own files use a column per
        // type with fixed rows. Both are read.
        if ($this->normalizer->key($this->cell($sheet, 2, 6)) !== '') {
            $generated = $this->generatedAnalyses($sheet, $outcomes);

            if ($generated !== []) {
                return $generated;
            }
        }

        $analyses = [];
        $blockPerType = [];

        foreach ($outcomes as $outcome) {
            $type = $outcome['type'];
            $blockPerType[$type] = ($blockPerType[$type] ?? 0) + 1;
            $block = $blockPerType[$type];
            $column = self::ANALYSIS_COLUMNS[$type] ?? null;

            if ($column === null || ! isset(self::ANALYSIS_ANSWER_ROWS[$block])) {
                continue;
            }

            $answers = [];
            foreach (self::ANALYSIS_ANSWER_ROWS[$block] as $index => $row) {
                $text = $this->cell($sheet, $column, $row);

                // "No aplica" is how the format marks a block the group did not
                // use; it is not an answer worth storing.
                if ($text === '' || $this->normalizer->key($text) === 'no aplica') {
                    continue;
                }

                $answers[$index] = $text;
            }

            if ($answers !== []) {
                $analyses[] = ['outcome_code' => $outcome['code'], 'answers' => $answers];
            }
        }

        return $analyses;
    }
}
