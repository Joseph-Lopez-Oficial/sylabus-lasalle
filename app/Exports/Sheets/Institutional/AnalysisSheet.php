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
 * The professor's written reading of each assessed outcome.
 *
 * The institutional file lays this out in three columns, one per outcome type.
 * Here each outcome takes a block of its own down the sheet: the report may
 * carry any number of outcomes, and a fixed three-column grid cannot hold them
 * without dropping some.
 */
class AnalysisSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /** The three open questions, in the order the format asks them. */
    public const QUESTIONS = [
        'outcome_performance' => 'Describa el desempeño del grupo con relación al Resultado de Aprendizaje',
        'academic_space_performance' => 'Describa el desempeño del grupo con relación al espacio académico',
        'improvement_proposals' => '¿Cuál es el análisis y qué propuestas de mejora se sugieren?',
    ];

    /** Rows a single outcome block occupies. */
    public const BLOCK_HEIGHT = 9;

    /** Row where the first block starts. */
    public const FIRST_BLOCK_ROW = 6;

    /** @param array<string, mixed> $report */
    public function __construct(private readonly array $report) {}

    public function title(): string
    {
        return 'Analisis del EA';
    }

    public function array(): array
    {
        $programming = $this->report['programming'];

        $rows = [
            [''],
            ['', 'ANÁLISIS DEL ESPACIO ACADÉMICO'],
            ['', 'RESULTADOS FINAL '.($programming->academicPeriod?->name ?? '')],
            [''],
            [''],
        ];

        foreach ($this->report['outcomes'] as $outcome) {
            $analysis = $outcome['analysis'];

            $rows[] = ['', 'RA DE '.mb_strtoupper($outcome['type']), $outcome['code']];
            $rows[] = ['', 'RESULTADO', $outcome['average'] ?? 'Sin Evaluar'];
            $rows[] = [''];

            foreach (self::QUESTIONS as $field => $question) {
                $rows[] = ['', $question];
                $rows[] = ['', $analysis?->{$field} ?? ''];
            }
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 3, 'B' => 96, 'C' => 20];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('B3')->getFont()->setBold(true);
        $sheet->mergeCells('B2:C2');

        $row = self::FIRST_BLOCK_ROW;

        foreach ($this->report['outcomes'] as $outcome) {
            $sheet->getStyle('B'.$row.':C'.$row)->getFont()->setBold(true)
                ->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('B'.$row.':C'.$row)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF7030A0');

            $sheet->getStyle('B'.($row + 1))->getFont()->setBold(true);
            $sheet->getStyle('C'.($row + 1))->getFont()->setBold(true);
            $sheet->getStyle('C'.($row + 1))->getNumberFormat()->setFormatCode('0.00');

            // Each question is a label followed by the answer beneath it.
            $answerRow = $row + 4;
            foreach (self::QUESTIONS as $question) {
                $sheet->getStyle('B'.($answerRow - 1))->getFont()->setItalic(true);
                $sheet->getStyle('B'.($answerRow - 1))->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEDEDED');
                $sheet->getStyle('B'.$answerRow)->getAlignment()
                    ->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('B'.$answerRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
                $sheet->getRowDimension($answerRow)->setRowHeight(52);

                $answerRow += 2;
            }

            $row += self::BLOCK_HEIGHT;
        }

        // Every sheet opens at the top; without this it opens wherever the
        // last write left the cursor, which looks like a scrolled-off file.
        $sheet->setSelectedCell('A1');

        return [];
    }
}
