<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\InstitutionalStyling;
use App\Models\PerformanceLevel;
use App\Models\Programming;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The report's opening sheet: who the group is, how it did, and where the
 * attention is needed.
 *
 * It follows the institutional report's layout so both documents read as one
 * family, and the distribution is drawn as bars rather than bare percentages,
 * which is what makes a table of numbers legible at a glance.
 */
class SummarySheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    use InstitutionalStyling;

    /** Column where the distribution bars start. */
    protected const BAR_COLUMN = 'H';

    /** @var array<string, int> */
    protected array $anchors = [];

    /** @param array<string,mixed> $summary */
    public function __construct(
        protected readonly array $summary,
        protected readonly Programming $programming,
    ) {}

    public function title(): string
    {
        return 'Resumen Global';
    }

    public function array(): array
    {
        $space = $this->programming->academicSpace;
        $professor = $this->programming->professor;

        $rows = $this->bannerRows($this->programming, $this->reportSubtitle());

        $rows[] = ['', 'ESPACIO ACADÉMICO', $space?->name ?? ''];
        $rows[] = ['', 'CÓDIGO', $space?->code ?? ''];
        $rows[] = ['', 'GRUPO', $this->programming->group ?? 'N/A'];
        $rows[] = ['', 'DOCENTE', $professor ? trim($professor->first_name.' '.$professor->last_name) : '—'];
        $rows[] = [''];

        // The headline figure, given a block of its own.
        $this->anchors['average'] = count($rows) + 1;
        $rows[] = ['', 'PROMEDIO GENERAL DEL GRUPO', $this->summary['overall_average'] ?? 'Sin Evaluar'];
        $rows[] = [''];

        $this->anchors['distribution_title'] = count($rows) + 1;
        $rows[] = ['', 'DISTRIBUCIÓN DE NIVELES DE DESEMPEÑO'];

        $this->anchors['distribution_header'] = count($rows) + 1;
        $rows[] = $this->distributionHeader();

        $this->anchors['distribution_first'] = count($rows) + 1;

        foreach ($this->summary['distribution'] as $dist) {
            $rows[] = $this->distributionRow($dist);
        }

        $this->anchors['distribution_last'] = count($rows);
        $rows[] = [''];

        $this->anchors['top_title'] = count($rows) + 1;
        $rows[] = ['', 'ESTUDIANTES CON MEJOR DESEMPEÑO'];

        $this->anchors['top_header'] = count($rows) + 1;
        $rows[] = ['', 'Estudiante', 'Promedio final'];

        $this->anchors['top_first'] = count($rows) + 1;

        foreach ($this->summary['top_students'] as $student) {
            $rows[] = ['', $student['student_name'], $student['final_average']];
        }

        $this->anchors['top_last'] = count($rows);

        if (! empty($this->summary['below_basic'])) {
            $rows[] = [''];

            $this->anchors['risk_title'] = count($rows) + 1;
            $rows[] = ['', 'ESTUDIANTES POR DEBAJO DE '.mb_strtoupper(PerformanceLevel::belowBasicLevelName())];

            $this->anchors['risk_header'] = count($rows) + 1;
            $rows[] = ['', 'Estudiante', 'Promedio final'];

            $this->anchors['risk_first'] = count($rows) + 1;

            foreach ($this->summary['below_basic'] as $student) {
                $rows[] = ['', $student['student_name'], $student['final_average']];
            }

            $this->anchors['risk_last'] = count($rows);
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 3, 'B' => 34, 'C' => 34, 'D' => 14, 'E' => 3];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = 'P';
        $this->styleBanner($sheet, $lastColumn);

        // Identity block.
        $sheet->getStyle('B6:B9')->getFont()->setBold(true);
        $sheet->getStyle('C6:C9')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $this->styleHeadlineAverage($sheet);
        $this->styleDistribution($sheet, $lastColumn);
        $this->styleStudentTable($sheet, 'top', 'FF548235');

        if (isset($this->anchors['risk_title'])) {
            $this->styleStudentTable($sheet, 'risk', 'FFC00000');
        }

        $this->resetView($sheet);

        return [];
    }

    /**
     * The group's average, set apart as the first thing the reader sees.
     */
    protected function styleHeadlineAverage(Worksheet $sheet): void
    {
        $row = $this->anchors['average'];

        $sheet->getStyle('B'.$row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('C'.$row)->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('B'.$row.':C'.$row)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(30);

        $this->styleGradeCell($sheet, 'C'.$row, $this->summary['overall_average'] ?? null);
    }

    protected function styleDistribution(Worksheet $sheet, string $lastColumn): void
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
            $this->styleDistributionRow($sheet, $row);

            // Levels are listed from the highest downwards, so the colour is
            // taken from the position rather than from the row.
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

    /**
     * What the report says it is, on the banner's second line.
     */
    protected function reportSubtitle(): string
    {
        return 'Reporte de calificaciones';
    }

    /**
     * Header of the distribution table, so a report can add columns of its own.
     */
    protected function distributionHeader(): array
    {
        return ['', 'Nivel', 'Calificaciones', '% del total', '', 'Representación'];
    }

    /**
     * @param  array<string, mixed>  $distribution
     */
    protected function distributionRow(array $distribution): array
    {
        return ['', $distribution['level_name'], $distribution['count'], $distribution['percentage']];
    }

    protected function styleDistributionRow(Worksheet $sheet, int $row): void
    {
        $sheet->getStyle('C'.$row.':D'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('0.0"%"');
    }

    /**
     * The top and at-risk tables share their shape; only the accent changes.
     */
    protected function styleStudentTable(Worksheet $sheet, string $prefix, string $accent): void
    {
        $title = $this->anchors[$prefix.'_title'];
        $header = $this->anchors[$prefix.'_header'];
        $first = $this->anchors[$prefix.'_first'];
        $last = $this->anchors[$prefix.'_last'];

        $this->styleSectionTitle($sheet, $title, 'D');
        $this->styleTable($sheet, $header, $last, 'D', secondary: true);
        $sheet->getStyle('B'.$title)->getFont()->getColor()->setARGB($accent);

        if ($last < $first) {
            return;
        }

        $this->styleZebra($sheet, $first, $last, 'D');

        for ($row = $first; $row <= $last; $row++) {
            $sheet->getStyle('C'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->styleGradeCell($sheet, 'C'.$row, $sheet->getCell('C'.$row)->getValue());
        }
    }
}
