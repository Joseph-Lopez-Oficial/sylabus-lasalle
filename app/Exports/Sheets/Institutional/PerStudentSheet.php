<?php

namespace App\Exports\Sheets\Institutional;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One row per student with the average reached in each assessed outcome.
 *
 * Averages are written as values, not formulas: the system is what computes and
 * validates them, and duplicating that arithmetic in the workbook would allow
 * the two to disagree.
 */
class PerStudentSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /** @param array<string, mixed> $report */
    public function __construct(private readonly array $report) {}

    public function title(): string
    {
        return 'Consolidado x Est';
    }

    public function array(): array
    {
        $programming = $this->report['programming'];
        $students = $this->report['students'];

        $rows = [
            [''],
            ['', 'TOTAL DE ESTUDIANTES', count($students)],
            ['', 'RESULTADOS FINAL '.($programming->academicPeriod?->name ?? '')],
            [''],
        ];

        $typeRow = ['', '', 'Tipo RA'];
        $header = ['', 'ID', 'Nombre del Estudiante'];

        foreach ($this->report['outcomes'] as $outcome) {
            $typeRow[] = $outcome['sheet'];
            $header[] = $outcome['code'];
        }

        $header[] = 'TOTAL';

        $rows[] = $typeRow;
        $rows[] = $header;

        foreach ($students as $student) {
            $row = ['', $student['document'], $student['name']];

            foreach ($this->report['outcomes'] as $outcome) {
                $row[] = $student['outcomes'][$outcome['code']]['average'] ?? 'Sin Evaluar';
            }

            $row[] = $student['average'] ?? 'Sin Evaluar';
            $rows[] = $row;
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 3, 'B' => 16, 'C' => 38];
        $column = 'D';

        foreach ($this->report['outcomes'] as $outcome) {
            $widths[$column] = 14;
            $column++;
        }

        $widths[$column] = 12;

        return $widths;
    }

    public function styles(Worksheet $sheet): array
    {
        $outcomeCount = count($this->report['outcomes']);
        $studentCount = count($this->report['students']);
        $lastColumn = chr(67 + $outcomeCount + 1);
        $lastRow = 6 + $studentCount;

        $sheet->getStyle('B2')->getFont()->setBold(true);
        $sheet->getStyle('B3')->getFont()->setBold(true);

        $sheet->getStyle('B5:'.$lastColumn.'6')->getFont()->setBold(true)
            ->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('B5:'.$lastColumn.'6')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1F4E78');
        $sheet->getStyle('B5:'.$lastColumn.'6')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        if ($studentCount > 0) {
            $sheet->getStyle('B5:'.$lastColumn.$lastRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
            $sheet->getStyle('D7:'.$lastColumn.$lastRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D7:'.$lastColumn.$lastRow)->getNumberFormat()->setFormatCode('0.00');

            // The total column is the one a reader looks for first.
            $sheet->getStyle($lastColumn.'7:'.$lastColumn.$lastRow)->getFont()->setBold(true);
        }

        $sheet->getRowDimension(6)->setRowHeight(20);
        // The header sits on row 6, so freezing just below it keeps the names
        // and the outcome columns in view without pinning half the sheet.
        $sheet->freezePane('D7');

        // Every sheet opens at the top; without this it opens wherever the
        // last write left the cursor, which looks like a scrolled-off file.
        $sheet->setSelectedCell('A1');

        return [];
    }
}
