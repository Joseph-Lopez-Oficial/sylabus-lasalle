<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Styling for the sheets that are a plain table with its headings on row one.
 *
 * They keep that shape because the data is genuinely flat — one row per
 * programming, per outcome, per period — and only borrow the report's colours,
 * borders, zebra striping and grade semaphore.
 */
trait StyledFlatTable
{
    use InstitutionalStyling;

    /**
     * Paints a table whose header sits on row one and whose body follows.
     *
     * @param  list<string>  $gradeColumns  columns holding grades, to be coloured by level
     */
    protected function styleFlatTable(
        Worksheet $sheet,
        int $rowCount,
        string $lastColumn,
        array $gradeColumns = [],
        array $centredColumns = []
    ): void {
        $lastRow = $rowCount + 1;

        $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true)
            ->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:'.$lastColumn.'1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1F4E78');
        $sheet->getStyle('A1:'.$lastColumn.'1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(26);

        if ($rowCount === 0) {
            return;
        }

        $sheet->getStyle('A1:'.$lastColumn.$lastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB('FFBFBFBF');

        // Alternating shading, which is what makes a wide table followable.
        for ($row = 3; $row <= $lastRow; $row += 2) {
            $sheet->getStyle('A'.$row.':'.$lastColumn.$row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF7F9FC');
        }

        foreach ($centredColumns as $column) {
            $sheet->getStyle($column.'2:'.$column.$lastRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        foreach ($gradeColumns as $column) {
            for ($row = 2; $row <= $lastRow; $row++) {
                $sheet->getStyle($column.$row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $this->styleGradeCell($sheet, $column.$row, $sheet->getCell($column.$row)->getValue());
            }
        }

        $sheet->freezePane('A2');
        $this->resetView($sheet);
    }
}
