<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\InstitutionalStyling;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Each student's standing, outcome by outcome.
 *
 * A student occupies a block: their name and final average on top, and beneath
 * it one line per assessed outcome. Repeating the name on every line, as a flat
 * table would, makes it hard to see where one student ends and the next begins.
 */
class ByStudentSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    use InstitutionalStyling;

    /** @var list<array{row: int, kind: string, average?: float|string|null}> */
    private array $marks = [];

    private int $headerRow = 0;

    private int $lastRow = 0;

    /** @param list<array<string,mixed>> $byStudent */
    public function __construct(private readonly array $byStudent) {}

    public function title(): string
    {
        return 'Por Estudiante';
    }

    public function array(): array
    {
        $rows = [
            [''],
            ['', 'DESEMPEÑO POR ESTUDIANTE'],
            [''],
        ];

        $this->headerRow = count($rows) + 1;
        $rows[] = ['', 'Estudiante / Resultado de aprendizaje', 'Tipo', 'Promedio'];

        foreach ($this->byStudent as $student) {
            $rows[] = ['', $student['student_name'], '', $student['final_average']];
            $this->marks[] = [
                'row' => count($rows),
                'kind' => 'student',
                'average' => $student['final_average'],
            ];

            foreach ($student['by_outcome'] ?? [] as $outcome) {
                $rows[] = [
                    '',
                    '   '.($outcome['outcome_desc'] ?? ''),
                    $outcome['type_name'] ?? '—',
                    $outcome['grade'] ?? null,
                ];

                $this->marks[] = [
                    'row' => count($rows),
                    'kind' => 'outcome',
                    'average' => $outcome['grade'] ?? null,
                ];
            }
        }

        $this->lastRow = count($rows);

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 3, 'B' => 70, 'C' => 18, 'D' => 14];
    }

    public function styles(Worksheet $sheet): array
    {
        $this->styleSectionTitle($sheet, 2, 'D');
        $sheet->getStyle('B2')->getFont()->setSize(13);

        $this->styleTable($sheet, $this->headerRow, $this->lastRow, 'D');
        $sheet->getStyle('B'.$this->headerRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach ($this->marks as $mark) {
            $row = $mark['row'];

            if ($mark['kind'] === 'student') {
                // The student's line is the one that anchors the block.
                $sheet->getStyle('B'.$row.':D'.$row)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE7EEF7');
                $sheet->getStyle('B'.$row)->getFont()->setBold(true);
                $sheet->getStyle('D'.$row)->getFont()->setBold(true);
            } else {
                $sheet->getStyle('B'.$row)->getAlignment()
                    ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight(28);
            }

            $sheet->getStyle('C'.$row.':D'.$row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $this->styleGradeCell($sheet, 'D'.$row, $mark['average'] ?? null);
        }

        $sheet->freezePane('B'.($this->headerRow + 1));
        $this->resetView($sheet);

        return [];
    }
}
