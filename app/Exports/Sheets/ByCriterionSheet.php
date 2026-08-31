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
 * How the group fared on each evaluation criterion.
 *
 * This is the sheet that says where the difficulty lies: a criterion that lags
 * across every outcome points at something to work on, and the bar makes that
 * visible without reading the whole column.
 */
class ByCriterionSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    use InstitutionalStyling;

    /** Column where the bars start. */
    private const BAR_COLUMN = 'G';

    /** Highest grade the scale reaches, used to size the bars. */
    private const TOP_GRADE = 5.0;

    /** @var list<array{row: int, kind: string, average?: float|string|null}> */
    private array $marks = [];

    private int $headerRow = 0;

    private int $lastRow = 0;

    /** @param list<array<string,mixed>> $byCriterion */
    public function __construct(private readonly array $byCriterion) {}

    public function title(): string
    {
        return 'Por Criterio';
    }

    public function array(): array
    {
        $rows = [
            [''],
            ['', 'DESEMPEÑO POR CRITERIO DE EVALUACIÓN'],
            [''],
        ];

        $this->headerRow = count($rows) + 1;
        $rows[] = ['', 'Criterio / Resultado de aprendizaje', 'Tipo', 'Promedio', '', 'Representación'];

        foreach ($this->byCriterion as $criterion) {
            $rows[] = [
                '',
                $criterion['criterion_name'],
                $criterion['type_name'] ?? '—',
                $criterion['group_average'],
            ];

            $this->marks[] = [
                'row' => count($rows),
                'kind' => 'criterion',
                'average' => $criterion['group_average'],
            ];

            foreach ($criterion['by_outcome'] ?? [] as $outcome) {
                $rows[] = [
                    '',
                    '   '.($outcome['outcome_desc'] ?? ''),
                    '',
                    $outcome['group_average'] ?? null,
                ];

                $this->marks[] = [
                    'row' => count($rows),
                    'kind' => 'outcome',
                    'average' => $outcome['group_average'] ?? null,
                ];
            }
        }

        $this->lastRow = count($rows);

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 3, 'B' => 62, 'C' => 18, 'D' => 14, 'E' => 3];
    }

    public function styles(Worksheet $sheet): array
    {
        $this->styleSectionTitle($sheet, 2, 'Q');
        $sheet->getStyle('B2')->getFont()->setSize(13);

        $this->styleTable($sheet, $this->headerRow, $this->lastRow, 'Q');
        $sheet->getStyle('B'.$this->headerRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach ($this->marks as $mark) {
            $row = $mark['row'];

            if ($mark['kind'] === 'criterion') {
                $sheet->getStyle('B'.$row.':D'.$row)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE7EEF7');
                $sheet->getStyle('B'.$row)->getFont()->setBold(true);
                $sheet->getStyle('D'.$row)->getFont()->setBold(true);

                if (is_numeric($mark['average'] ?? null)) {
                    $average = (float) $mark['average'];
                    $this->drawBar(
                        $sheet,
                        $row,
                        self::BAR_COLUMN,
                        ($average / self::TOP_GRADE) * 100,
                        $this->barColour($average)
                    );
                }
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

        $this->resetView($sheet);

        return [];
    }

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
