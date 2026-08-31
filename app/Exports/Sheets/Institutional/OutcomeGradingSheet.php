<?php

namespace App\Exports\Sheets\Institutional;

use App\Models\PerformanceLevel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Grading sheet for a single learning outcome.
 *
 * The rubric sits at the top and the students below, in the rows the importer
 * expects: header on row 17, first student on row 18, criteria from column D.
 * A dropdown offers the configured levels so a professor filling the file in by
 * hand cannot invent a level the system would then reject.
 */
class OutcomeGradingSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /** Row where the student table's header sits. */
    public const HEADER_ROW = 17;

    /** Row where the first student sits. */
    public const FIRST_STUDENT_ROW = 18;

    /**
     * @param  array<string, mixed>  $outcome
     * @param  list<array<string, mixed>>  $students
     * @param  Collection<int, PerformanceLevel>  $levels
     */
    public function __construct(
        private readonly array $outcome,
        private readonly array $students,
        private readonly Collection $levels,
    ) {}

    public function title(): string
    {
        return $this->outcome['sheet'];
    }

    public function array(): array
    {
        $criteria = $this->outcome['criteria'];

        $rows = [
            [''],
            ['', 'RA DE '.mb_strtoupper($this->outcome['type'])],
            ['', $this->outcome['code'].'. '.$this->outcome['description']],
            ['', 'Rúbrica Analítica para medir un RA de '.$this->outcome['type']],
        ];

        // Rubric header: one column per level, highest first.
        $rubricHeader = ['', 'Numero', 'Criterio'];
        foreach ($this->levels as $level) {
            $rubricHeader[] = $this->levelLabel($level);
        }
        $rubricHeader[] = 'No Aplica';
        $rows[] = $rubricHeader;

        foreach ($criteria as $index => $criterion) {
            $row = ['', $index + 1, $criterion->name];

            foreach ($this->levels as $level) {
                $row[] = $level->description ?? '';
            }

            $rows[] = $row;
        }

        $equivalents = ['', '', 'Nota equivalente'];
        foreach ($this->levels as $level) {
            $equivalents[] = $level->grade_value;
        }
        $rows[] = $equivalents;

        while (count($rows) < 13) {
            $rows[] = [''];
        }

        $rows[] = ['', '', 'PROMEDIO TOTAL DEL GRUPO', $this->outcome['average'] ?? 'Sin Evaluar'];

        while (count($rows) < self::HEADER_ROW - 1) {
            $rows[] = [''];
        }

        $header = ['', 'ID', 'Nombre del Estudiante'];
        foreach ($criteria as $index => $criterion) {
            $header[] = 'Criterio '.($index + 1);
        }
        $header[] = 'TOTAL';
        $rows[] = $header;

        foreach ($this->students as $student) {
            $entry = $student['outcomes'][$this->outcome['code']] ?? ['marks' => [], 'average' => null];
            $row = ['', $student['document'], $student['name']];

            foreach ($criteria as $index => $criterion) {
                $level = $entry['marks'][$index] ?? null;

                // An unassessed criterion says so, instead of looking like a
                // zero or an empty cell nobody can tell apart from an omission.
                $row[] = $level === null ? 'No Aplica' : $this->levelLabel($level);
            }

            $row[] = $entry['average'] ?? 'Sin Evaluar';
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * A level as the institutional files write it: name and score in brackets.
     */
    private function levelLabel(PerformanceLevel $level): string
    {
        return $level->name.'('.$level->order.')';
    }

    public function columnWidths(): array
    {
        // The rubric's descriptions are long paragraphs, and the grading table
        // shares these same columns. They are kept wide, as the institutional
        // file does, so the text spreads sideways instead of stacking into very
        // tall rows.
        $widths = ['A' => 3, 'B' => 18, 'C' => 30];
        $column = 'D';

        // One column per level, since the rubric is what needs the room; the
        // criteria of the grading table sit underneath in the same columns.
        $descriptionColumns = max(count($this->outcome['criteria']), $this->levels->count());

        for ($i = 0; $i < $descriptionColumns; $i++) {
            $widths[$column] = 36;
            $column++;
        }

        $widths[$column] = 14;

        return $widths;
    }

    public function styles(Worksheet $sheet): array
    {
        $criteriaCount = count($this->outcome['criteria']);
        $studentCount = count($this->students);
        $lastCriterionColumn = chr(67 + max($criteriaCount, 1));
        $totalColumn = chr(67 + $criteriaCount + 1);
        $rubricLastColumn = chr(67 + $this->levels->count() + 1);
        $lastRow = self::HEADER_ROW + $studentCount;

        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('B3')->getFont()->setBold(true);
        $sheet->getStyle('B4')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('B4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(20);
        $sheet->mergeCells('B3:'.$rubricLastColumn.'3');
        $sheet->getStyle('B3')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(30);

        // Rubric block.
        $rubricLastRow = 6 + $criteriaCount;
        $sheet->getStyle('B5:'.$rubricLastColumn.'5')->getFont()->setBold(true)
            ->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('B5:'.$rubricLastColumn.'5')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF548235');
        $sheet->getStyle('B5:'.$rubricLastColumn.$rubricLastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
        $sheet->getStyle('D6:'.$rubricLastColumn.$rubricLastRow)->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('B5:'.$rubricLastColumn.'5')->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(5)->setRowHeight(28);

        // Wrapped text does not always make Excel recompute the row height, so
        // the rubric rows are given one that fits the longest description.
        for ($row = 6; $row < $rubricLastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(76);
        }
        $sheet->getStyle('B'.$rubricLastRow.':'.$rubricLastColumn.$rubricLastRow)->getFont()->setBold(true);

        $sheet->getStyle('C14')->getFont()->setBold(true);
        $sheet->getStyle('D14')->getFont()->setBold(true);
        $sheet->getStyle('D14')->getNumberFormat()->setFormatCode('0.00');

        // Student table.
        $sheet->getStyle('B'.self::HEADER_ROW.':'.$totalColumn.self::HEADER_ROW)
            ->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('B'.self::HEADER_ROW.':'.$totalColumn.self::HEADER_ROW)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');
        $sheet->getStyle('B'.self::HEADER_ROW.':'.$totalColumn.self::HEADER_ROW)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C'.self::HEADER_ROW)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(20);

        if ($studentCount > 0) {
            $sheet->getStyle('B'.self::HEADER_ROW.':'.$totalColumn.$lastRow)
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
            $sheet->getStyle('D'.self::FIRST_STUDENT_ROW.':'.$totalColumn.$lastRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($totalColumn.self::FIRST_STUDENT_ROW.':'.$totalColumn.$lastRow)
                ->getFont()->setBold(true);
            $sheet->getStyle($totalColumn.self::FIRST_STUDENT_ROW.':'.$totalColumn.$lastRow)
                ->getNumberFormat()->setFormatCode('0.00');

            $this->applyLevelDropdown($sheet, $lastCriterionColumn, $lastRow);
        }

        // Only the student's name column stays put. Freezing at the table's
        // header would pin the whole rubric above it — seventeen rows — and
        // leave barely a strip of the sheet able to scroll.
        $sheet->freezePane('D1');

        // The sheet opens at the top; without this it opens wherever the last
        // write left the cursor, which looks like a scrolled-off file.
        $sheet->setSelectedCell('A1');

        return [];
    }

    /**
     * Restricts the criterion cells to the configured levels.
     *
     * The file is meant to be filled in by hand and uploaded back, so the
     * dropdown keeps a professor from typing a level the importer cannot map.
     */
    private function applyLevelDropdown(Worksheet $sheet, string $lastColumn, int $lastRow): void
    {
        $options = $this->levels->map(fn (PerformanceLevel $level) => $this->levelLabel($level))
            ->push('No Aplica')
            ->implode(',');

        for ($row = self::FIRST_STUDENT_ROW; $row <= $lastRow; $row++) {
            for ($column = 'D'; $column <= $lastColumn; $column++) {
                $validation = $sheet->getCell($column.$row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Nivel no válido');
                $validation->setError('Seleccione uno de los niveles de la lista.');
                $validation->setFormula1('"'.$options.'"');
            }
        }
    }
}
