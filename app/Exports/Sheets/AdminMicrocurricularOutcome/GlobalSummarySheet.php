<?php

namespace App\Exports\Sheets\AdminMicrocurricularOutcome;

use App\Exports\Concerns\InstitutionalStyling;
use App\Models\MicrocurricularLearningOutcome;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * How a single learning outcome has fared everywhere it is assessed.
 *
 * The outcome's place in the curriculum is stated first, since this report is
 * read by someone asking whether a particular outcome is being reached across
 * the programme, not by whoever taught one group.
 */
class GlobalSummarySheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    use InstitutionalStyling;

    /** Column where the bars start. */
    private const BAR_COLUMN = 'G';

    /** @var array<string, int> */
    private array $anchors = [];

    /** @param array<string,mixed> $summary */
    public function __construct(
        private readonly array $summary,
        private readonly MicrocurricularLearningOutcome $outcome,
    ) {}

    public function title(): string
    {
        return 'Resumen Global';
    }

    public function array(): array
    {
        $space = $this->outcome->academicSpace;
        $competency = $space?->competency;
        $nucleus = $competency?->problematicNucleus;
        $program = $nucleus?->program;
        $faculty = $program?->faculty;

        $rows = [
            [''],
            ['', mb_strtoupper($faculty?->name ?? 'FACULTAD DE INGENIERÍA')],
            ['', 'REPORTE DE RESULTADO DE APRENDIZAJE'],
            ['', trim(($this->outcome->code ?? '').' — '.($this->outcome->type?->name ?? ''))],
            [''],
        ];

        $this->anchors['description'] = count($rows) + 1;
        $rows[] = ['', 'DESCRIPCIÓN', $this->outcome->description ?? ''];
        $rows[] = ['', 'ESPACIO ACADÉMICO', $space?->name ?? '—'];
        $rows[] = ['', 'COMPETENCIA', $competency?->name ?? '—'];
        $rows[] = ['', 'NÚCLEO PROBLÉMICO', $nucleus?->name ?? '—'];
        $rows[] = ['', 'PROGRAMA', $program?->name ?? '—'];
        $rows[] = [''];

        $this->anchors['figures'] = count($rows) + 1;
        $rows[] = ['', 'PROMEDIO GLOBAL', $this->summary['global_average'] ?? 'Sin Evaluar'];
        $rows[] = ['', 'PROGRAMACIONES', $this->summary['total_programmings'] ?? 0];
        $rows[] = ['', 'CALIFICACIONES REGISTRADAS', $this->summary['total_grade_records'] ?? 0];
        $rows[] = [''];

        $this->anchors['distribution_title'] = count($rows) + 1;
        $rows[] = ['', 'DISTRIBUCIÓN GLOBAL DE CALIFICACIONES'];

        $this->anchors['distribution_header'] = count($rows) + 1;
        $rows[] = ['', 'Nivel de desempeño', 'Calificaciones', '% del total', '', 'Representación'];

        $this->anchors['distribution_first'] = count($rows) + 1;

        foreach ($this->summary['distribution'] as $dist) {
            $rows[] = ['', $dist['level_name'], $dist['count'], $dist['percentage']];
        }

        $this->anchors['distribution_last'] = count($rows);

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 3, 'B' => 34, 'C' => 62, 'D' => 14, 'E' => 3];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = 'Q';

        foreach ([2, 3, 4] as $row) {
            $sheet->mergeCells('B'.$row.':'.$lastColumn.$row);
            $sheet->getStyle('B'.$row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        $sheet->getStyle('B2:'.$lastColumn.'4')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1F4E78');
        $sheet->getStyle('B2:'.$lastColumn.'4')->getFont()
            ->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('B2')->getFont()->setSize(13);
        $sheet->getRowDimension(2)->setRowHeight(24);

        // Curriculum block: the outcome's statement needs room to breathe.
        $first = $this->anchors['description'];
        $sheet->getStyle('B'.$first.':B'.($first + 4))->getFont()->setBold(true);
        $sheet->getStyle('C'.$first)->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($first)->setRowHeight(44);

        $figures = $this->anchors['figures'];
        $sheet->getStyle('B'.$figures.':B'.($figures + 2))->getFont()->setBold(true);
        $sheet->getStyle('C'.$figures)->getFont()->setBold(true)->setSize(16);
        $sheet->getRowDimension($figures)->setRowHeight(30);
        $this->styleGradeCell($sheet, 'C'.$figures, $this->summary['global_average'] ?? null);
        $sheet->getStyle('C'.($figures + 1).':C'.($figures + 2))->getFont()->setBold(true);

        $this->styleSectionTitle($sheet, $this->anchors['distribution_title'], $lastColumn);
        $this->styleTable(
            $sheet,
            $this->anchors['distribution_header'],
            $this->anchors['distribution_last'],
            $lastColumn
        );

        $levelCount = count($this->summary['distribution']);
        $row = $this->anchors['distribution_first'];

        foreach ($this->summary['distribution'] as $index => $dist) {
            $sheet->getStyle('C'.$row.':D'.$row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('0.0"%"');

            $this->drawBar(
                $sheet,
                $row,
                self::BAR_COLUMN,
                (float) $dist['percentage'],
                $this->levelColour($levelCount - $index, $levelCount)
            );

            $row++;
        }

        $this->resetView($sheet);

        return [];
    }
}
