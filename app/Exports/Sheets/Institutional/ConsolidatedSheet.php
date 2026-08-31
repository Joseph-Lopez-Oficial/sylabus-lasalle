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
 * The report's cover sheet, holding the group's identity and the average each
 * assessed outcome reached.
 *
 * Rows follow the institutional layout the coordination works with, so a reader
 * used to their files finds every field where they expect it.
 */
class ConsolidatedSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /** @param array<string, mixed> $report */
    public function __construct(private readonly array $report) {}

    public function title(): string
    {
        return 'Consolidado';
    }

    public function array(): array
    {
        $programming = $this->report['programming'];
        $space = $programming->academicSpace;
        $faculty = $space?->competency?->problematicNucleus?->program?->faculty;
        $program = $space?->competency?->problematicNucleus?->program;
        $nucleus = $space?->competency?->problematicNucleus;
        $professor = $programming->professor;

        $rows = [
            [''],
            ['', mb_strtoupper($faculty?->name ?? 'FACULTAD DE INGENIERÍA')],
            ['', 'SEGUIMIENTO RESULTADOS DE APRENDIZAJE - NIVEL MICROCURRICULAR'],
            ['', 'RESULTADO FINAL '.($programming->academicPeriod?->name ?? '')],
            [''],
            [''],
            ['', 'ÁREA DE ÉNFASIS', mb_strtoupper($nucleus?->name ?? '')],
            ['', 'PROGRAMA', mb_strtoupper($program?->name ?? '')],
            ['', 'DOCENTE', $professor ? trim($professor->first_name.' '.$professor->last_name) : ''],
            ['', 'ESPACIO ACADÉMICO', $space?->name ?? ''],
            ['', 'GRUPO', $programming->group ?? ''],
        ];

        // Competencies start on row 12 and continue downwards, as in the format.
        $competencies = $this->report['competencies'];
        $rows[] = ['', 'COMPETENCIAS A LAS QUE APORTA EL ESPACIO ACADÉMICO', $competencies[0] ?? ''];

        foreach (array_slice($competencies, 1) as $competency) {
            $rows[] = ['', '', $competency];
        }

        while (count($rows) < 20) {
            $rows[] = [''];
        }

        $rows[] = ['', 'RESULTADO DE APRENDIZAJE'];

        foreach ($this->report['outcomes'] as $outcome) {
            $rows[] = ['', sprintf('%s. %s (%s)', $outcome['code'], $outcome['description'], $outcome['type'])];
        }

        while (count($rows) < 26) {
            $rows[] = [''];
        }

        $rows[] = ['', 'PRODUCTO PRIVILEGIADO', ''];
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = [''];

        // Group results: one column per assessed outcome.
        $header = ['', 'RESULTADOS CONSOLIDADO', 'CATEGORÍA'];
        $codes = ['', '', ''];
        $averages = ['', '', 'Promedio del Grupo por Resultado de Aprendizaje'];

        foreach ($this->report['outcomes'] as $outcome) {
            $header[] = $outcome['sheet'];
            $codes[] = $outcome['code'];
            $averages[] = $outcome['average'] ?? 'Sin Evaluar';
        }

        $rows[] = $header;
        $rows[] = $codes;
        $rows[] = $averages;

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 3,
            'B' => 46,
            'C' => 58,
            'D' => 18,
            'E' => 18,
            'F' => 18,
            'G' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $outcomeCount = count($this->report['outcomes']);
        $lastColumn = chr(67 + max($outcomeCount, 1));

        $sheet->mergeCells('B2:G2');
        $sheet->mergeCells('B3:G3');
        $sheet->mergeCells('B4:G4');

        foreach (['B2', 'B3', 'B4'] as $cell) {
            $sheet->getStyle($cell)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('B4')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('B2:G4')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1F4E78');
        $sheet->getStyle('B2:G4')->getFont()->getColor()->setARGB('FFFFFFFF');

        // The identity block reads as a form: label on the left, value beside it.
        $sheet->getStyle('B7:B12')->getFont()->setBold(true);
        $sheet->getStyle('B7:C12')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
        $sheet->getStyle('C7:C13')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('B21')->getFont()->setBold(true);
        $sheet->getStyle('B21:B'.(21 + $outcomeCount))->getAlignment()->setWrapText(true);
        $sheet->getStyle('B27')->getFont()->setBold(true);

        $sheet->getStyle('B31:'.$lastColumn.'31')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('B31:'.$lastColumn.'31')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2E75B6');
        $sheet->getStyle('B31:'.$lastColumn.'33')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
        $sheet->getStyle('C33')->getFont()->setBold(true);
        $sheet->getStyle('D31:'.$lastColumn.'33')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D33:'.$lastColumn.'33')->getNumberFormat()->setFormatCode('0.00');

        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getRowDimension(12)->setRowHeight(34);

        // Every sheet opens at the top; without this it opens wherever the
        // last write left the cursor, which looks like a scrolled-off file.
        $sheet->setSelectedCell('A1');

        return [];
    }
}
