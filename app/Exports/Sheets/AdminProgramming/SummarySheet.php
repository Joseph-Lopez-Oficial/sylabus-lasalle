<?php

namespace App\Exports\Sheets\AdminProgramming;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The administration's view of a programming.
 *
 * It is the professor's summary with two more columns in the distribution: how
 * many students reached each level, which is what the coordination looks at
 * when comparing groups.
 */
class SummarySheet extends \App\Exports\Sheets\SummarySheet
{
    public function title(): string
    {
        return 'Resumen Global';
    }

    protected function reportSubtitle(): string
    {
        return 'Reporte de estadísticas de programación';
    }

    protected function distributionHeader(): array
    {
        return ['', 'Nivel', 'Calificaciones', '% del total', 'Estudiantes', '% estudiantes', 'Representación'];
    }

    protected function distributionRow(array $distribution): array
    {
        return [
            '',
            $distribution['level_name'],
            $distribution['count'],
            $distribution['percentage'],
            $distribution['student_count'] ?? '',
            $distribution['student_percentage'] ?? '',
        ];
    }

    protected function styleDistributionRow(Worksheet $sheet, int $row): void
    {
        parent::styleDistributionRow($sheet, $row);

        $sheet->getStyle('E'.$row.':F'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('0.0"%"');
    }
}
