<?php

namespace App\Exports\Sheets\AdminAcademicSpace;

use App\Exports\Concerns\InstitutionalStyling;
use App\Models\AcademicSpace;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * How an academic space has fared across every group that has taught it.
 *
 * Unlike a programming's report, this one spans periods, so the trend over time
 * is the figure that matters and is drawn as bars for that reason.
 */
class SummarySheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    use InstitutionalStyling;

    /** Column where the bars start. */
    private const BAR_COLUMN = 'G';

    /** Highest grade the scale reaches, used to size the trend bars. */
    private const TOP_GRADE = 5.0;

    /** @var array<string, int> */
    private array $anchors = [];

    /** @param array<string,mixed> $summary */
    public function __construct(
        private readonly array $summary,
        private readonly AcademicSpace $academicSpace,
    ) {}

    public function title(): string
    {
        return 'Resumen Global';
    }

    public function array(): array
    {
        $faculty = $this->academicSpace->competency?->problematicNucleus?->program?->faculty;

        $rows = [
            [''],
            ['', mb_strtoupper($faculty?->name ?? 'FACULTAD DE INGENIERÍA')],
            ['', 'REPORTE DE ESTADÍSTICAS DEL ESPACIO ACADÉMICO'],
            ['', mb_strtoupper($this->academicSpace->name)],
            [''],
            ['', 'CÓDIGO', $this->academicSpace->code ?? ''],
            ['', 'COMPETENCIA', $this->academicSpace->competency?->name ?? '—'],
            [''],
        ];

        // The three figures that describe the space at a glance.
        $this->anchors['figures'] = count($rows) + 1;
        $rows[] = ['', 'PROMEDIO GLOBAL', $this->summary['global_average'] ?? 'Sin Evaluar'];
        $rows[] = ['', 'PROGRAMACIONES', $this->summary['total_programmings'] ?? 0];
        $rows[] = ['', 'CALIFICACIONES REGISTRADAS', $this->summary['total_grade_records'] ?? 0];
        $rows[] = [''];

        $this->anchors['distribution_title'] = count($rows) + 1;
        $rows[] = ['', 'DISTRIBUCIÓN DE CALIFICACIONES'];

        $this->anchors['distribution_header'] = count($rows) + 1;
        $rows[] = ['', 'Nivel de desempeño', 'Calificaciones', '% del total', '', 'Representación'];

        $this->anchors['distribution_first'] = count($rows) + 1;

        foreach ($this->summary['distribution'] as $dist) {
            $rows[] = ['', $dist['level_name'], $dist['count'], $dist['percentage']];
        }

        $this->anchors['distribution_last'] = count($rows);

        if (! empty($this->summary['trend_by_period'])) {
            $rows[] = [''];

            $this->anchors['trend_title'] = count($rows) + 1;
            $rows[] = ['', 'TENDENCIA POR PERÍODO'];

            $this->anchors['trend_header'] = count($rows) + 1;
            $rows[] = ['', 'Período', 'Promedio', '', '', 'Representación'];

            $this->anchors['trend_first'] = count($rows) + 1;

            foreach ($this->summary['trend_by_period'] as $trend) {
                $rows[] = ['', $trend['period'], $trend['average']];
            }

            $this->anchors['trend_last'] = count($rows);
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 3, 'B' => 34, 'C' => 30, 'D' => 14, 'E' => 3];
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

        $sheet->getStyle('B2:'.$lastColumn.'4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1F4E78');
        $sheet->getStyle('B2:'.$lastColumn.'4')->getFont()
            ->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('B2')->getFont()->setSize(13);
        $sheet->getRowDimension(2)->setRowHeight(24);

        $sheet->getStyle('B6:B7')->getFont()->setBold(true);

        $this->styleFigures($sheet);
        $this->styleDistribution($sheet, $lastColumn);

        if (isset($this->anchors['trend_title'])) {
            $this->styleTrend($sheet, $lastColumn);
        }

        $this->resetView($sheet);

        return [];
    }

    private function styleFigures(Worksheet $sheet): void
    {
        $row = $this->anchors['figures'];

        $sheet->getStyle('B'.$row.':B'.($row + 2))->getFont()->setBold(true);
        $sheet->getStyle('C'.$row)->getFont()->setBold(true)->setSize(16);
        $sheet->getRowDimension($row)->setRowHeight(30);

        $this->styleGradeCell($sheet, 'C'.$row, $this->summary['global_average'] ?? null);

        $sheet->getStyle('C'.($row + 1).':C'.($row + 2))->getFont()->setBold(true);
    }

    private function styleDistribution(Worksheet $sheet, string $lastColumn): void
    {
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
    }

    private function styleTrend(Worksheet $sheet, string $lastColumn): void
    {
        $this->styleSectionTitle($sheet, $this->anchors['trend_title'], $lastColumn);
        $this->styleTable(
            $sheet,
            $this->anchors['trend_header'],
            $this->anchors['trend_last'],
            $lastColumn,
            secondary: true
        );

        for ($row = $this->anchors['trend_first']; $row <= $this->anchors['trend_last']; $row++) {
            $sheet->getStyle('B'.$row.':C'.$row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $average = $sheet->getCell('C'.$row)->getValue();
            $this->styleGradeCell($sheet, 'C'.$row, $average);

            if (is_numeric($average)) {
                $this->drawBar(
                    $sheet,
                    $row,
                    self::BAR_COLUMN,
                    ((float) $average / self::TOP_GRADE) * 100,
                    'FF2E75B6'
                );
            }
        }
    }
}
