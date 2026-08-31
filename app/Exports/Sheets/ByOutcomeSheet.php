<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\InstitutionalStyling;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * How the group fared on each learning outcome.
 *
 * Outcomes are grouped by type, the way the institutional format presents them,
 * and each one shows its average beside a bar, so the reader sees which
 * outcomes lag without comparing numbers one by one.
 */
class ByOutcomeSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    use InstitutionalStyling;

    /** Column where the bars start. */
    private const BAR_COLUMN = 'H';

    /** Highest grade the scale reaches, used to size the bars. */
    private const TOP_GRADE = 5.0;

    /** @var list<array{row: int, kind: string, average?: float|string|null}> */
    private array $marks = [];

    private int $headerRow = 0;

    private int $lastRow = 0;

    /** @param list<array<string,mixed>> $byOutcome */
    public function __construct(private readonly array $byOutcome) {}

    public function title(): string
    {
        return 'Por Resultado';
    }

    public function array(): array
    {
        $rows = [
            [''],
            ['', 'DESEMPEÑO POR RESULTADO DE APRENDIZAJE'],
            [''],
        ];

        $this->headerRow = count($rows) + 1;
        $rows[] = ['', 'Resultado de aprendizaje', 'Promedio del grupo', 'Más alto', 'Más bajo', '', 'Representación'];

        // Grouped by type, so the reader compares like with like.
        $byType = [];
        foreach ($this->byOutcome as $outcome) {
            $byType[$outcome['type_name'] ?? '—'][] = $outcome;
        }

        foreach ($byType as $type => $outcomes) {
            $rows[] = ['', mb_strtoupper($type)];
            $this->marks[] = ['row' => count($rows), 'kind' => 'type'];

            foreach ($outcomes as $outcome) {
                $rows[] = [
                    '',
                    $outcome['outcome_desc'],
                    $outcome['group_average'],
                    $outcome['highest'],
                    $outcome['lowest'],
                ];

                $this->marks[] = [
                    'row' => count($rows),
                    'kind' => 'outcome',
                    'average' => $outcome['group_average'],
                ];
            }
        }

        $this->lastRow = count($rows);

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 3, 'B' => 62, 'C' => 18, 'D' => 12, 'E' => 12, 'F' => 3];
    }

    public function styles(Worksheet $sheet): array
    {
        $this->styleSectionTitle($sheet, 2, 'R');
        $sheet->getStyle('B2')->getFont()->setSize(13);

        $this->styleTable($sheet, $this->headerRow, $this->lastRow, 'R');
        $sheet->getStyle('B'.$this->headerRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach ($this->marks as $mark) {
            if ($mark['kind'] === 'type') {
                $this->styleTypeHeading($sheet, $mark['row']);

                continue;
            }

            $this->styleOutcomeRow($sheet, $mark['row'], $mark['average']);
        }

        $this->resetView($sheet);

        return [];
    }

    private function styleTypeHeading(Worksheet $sheet, int $row): void
    {
        $sheet->mergeCells('B'.$row.':E'.$row);
        $sheet->getStyle('B'.$row)->getFont()->setBold(true);
        $sheet->getStyle('B'.$row.':R'.$row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE7EEF7');
    }

    private function styleOutcomeRow(Worksheet $sheet, int $row, float|string|null $average): void
    {
        $sheet->getStyle('B'.$row)->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C'.$row.':E'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(32);

        foreach (['C', 'D', 'E'] as $column) {
            $this->styleGradeCell($sheet, $column.$row, $sheet->getCell($column.$row)->getValue());
        }

        if (! is_numeric($average)) {
            return;
        }

        // The bar shows the average as a share of the top of the scale.
        $percentage = ((float) $average / self::TOP_GRADE) * 100;
        $this->drawBar($sheet, $row, self::BAR_COLUMN, $percentage, $this->barColour((float) $average));
    }

    /**
     * Bar colour follows the same reading as the grade colour.
     */
    private function barColour(float $average): string
    {
        $threshold = \App\Models\PerformanceLevel::belowBasicThreshold();

        return match (true) {
            $average < $threshold => 'FFC00000',
            $average < $threshold * 1.5 => 'FFBF8F00',
            default => 'FF548235',
        };
    }
}
