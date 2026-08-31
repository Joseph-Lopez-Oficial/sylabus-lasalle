<?php

namespace App\Exports\Concerns;

use App\Models\PerformanceLevel;
use App\Models\Programming;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The visual language the institutional report established, shared by every
 * statistics sheet so all the workbooks the system produces read as one family.
 *
 * The pieces here are deliberately small — a banner, a section title, a table
 * header, a bar — because each sheet arranges its own content and only borrows
 * the way it looks.
 */
trait InstitutionalStyling
{
    /** Institutional blue, used for banners and table headers. */
    private const COLOUR_PRIMARY = 'FF1F4E78';

    /** A lighter blue for secondary headers. */
    private const COLOUR_SECONDARY = 'FF2E75B6';

    /** Grey for section titles and labels. */
    private const COLOUR_SECTION = 'FFEDEDED';

    /** Border grey. */
    private const COLOUR_BORDER = 'FFBFBFBF';

    /**
     * The three-line heading every report opens with: faculty, what the report
     * is, and the period it covers.
     */
    protected function bannerRows(Programming $programming, string $subtitle): array
    {
        $faculty = $programming->academicSpace?->competency?->problematicNucleus
            ?->program?->faculty?->name;

        return [
            [''],
            ['', mb_strtoupper($faculty ?? 'FACULTAD DE INGENIERÍA')],
            ['', mb_strtoupper($subtitle)],
            ['', 'RESULTADO FINAL '.($programming->academicPeriod?->name ?? '')],
            [''],
        ];
    }

    /**
     * Paints the banner written by bannerRows().
     */
    protected function styleBanner(Worksheet $sheet, string $lastColumn): void
    {
        foreach ([2, 3, 4] as $row) {
            $sheet->mergeCells('B'.$row.':'.$lastColumn.$row);
            $sheet->getStyle('B'.$row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        $sheet->getStyle('B2:'.$lastColumn.'4')->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOUR_PRIMARY);
        $sheet->getStyle('B2:'.$lastColumn.'4')->getFont()
            ->setBold(true)->getColor()->setARGB('FFFFFFFF');

        $sheet->getStyle('B2')->getFont()->setSize(13);
        $sheet->getRowDimension(2)->setRowHeight(24);
    }

    /**
     * A title that separates one block of the sheet from the next.
     */
    protected function styleSectionTitle(Worksheet $sheet, int $row, string $lastColumn): void
    {
        $sheet->mergeCells('B'.$row.':'.$lastColumn.$row);
        $sheet->getStyle('B'.$row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('B'.$row)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::COLOUR_SECTION);
        $sheet->getStyle('B'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    /**
     * The header row of a table, plus the borders around its body.
     */
    protected function styleTable(
        Worksheet $sheet,
        int $headerRow,
        int $lastRow,
        string $lastColumn,
        bool $secondary = false
    ): void {
        $range = 'B'.$headerRow.':'.$lastColumn.$headerRow;

        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($secondary ? self::COLOUR_SECONDARY : self::COLOUR_PRIMARY);
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        if ($lastRow > $headerRow) {
            $sheet->getStyle('B'.$headerRow.':'.$lastColumn.$lastRow)
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB(self::COLOUR_BORDER);
        }
    }

    /**
     * Alternating row shading, which makes a wide table far easier to follow.
     */
    protected function styleZebra(Worksheet $sheet, int $firstRow, int $lastRow, string $lastColumn): void
    {
        for ($row = $firstRow; $row <= $lastRow; $row += 2) {
            $sheet->getStyle('B'.$row.':'.$lastColumn.$row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF7F9FC');
        }
    }

    /**
     * Colours a grade according to the level it falls into.
     *
     * The thresholds come from the configured scale, so a change to the
     * institutional levels is reflected here without touching this code.
     */
    protected function styleGradeCell(Worksheet $sheet, string $cell, float|string|null $value): void
    {
        if (! is_numeric($value)) {
            $sheet->getStyle($cell)->getFont()->setItalic(true)
                ->getColor()->setARGB('FF999999');

            return;
        }

        $sheet->getStyle($cell)->getFont()->setBold(true)
            ->getColor()->setARGB($this->gradeColour((float) $value));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0.00');
    }

    /**
     * Font colour for a grade: red below the basic threshold, amber up to the
     * next level, green above it.
     */
    private function gradeColour(float $value): string
    {
        $threshold = PerformanceLevel::belowBasicThreshold();

        $upperLevels = PerformanceLevel::query()
            ->whereNotNull('grade_value')
            ->where('grade_value', '>', $threshold)
            ->orderBy('grade_value')
            ->pluck('grade_value');

        $middle = (float) ($upperLevels->first() ?? $threshold);

        return match (true) {
            $value < $threshold => 'FFC00000',
            $value < $middle => 'FFBF8F00',
            default => 'FF548235',
        };
    }

    /**
     * A bar drawn with cell fills, so a distribution can be read at a glance
     * without embedding a chart the spreadsheet may render differently.
     *
     * Ten cells stand for the whole; as many as the percentage covers are
     * filled in.
     */
    protected function drawBar(Worksheet $sheet, int $row, string $firstColumn, float $percentage, string $colour): void
    {
        $filled = (int) round(max(0.0, min(100.0, $percentage)) / 10);
        $column = $firstColumn;

        for ($i = 0; $i < 10; $i++) {
            $sheet->getStyle($column.$row)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($i < $filled ? $colour : 'FFE9ECEF');
            $sheet->getColumnDimension($column)->setWidth(2.4);
            $column++;
        }
    }

    /**
     * Colour of a performance level's bar, from worst to best.
     */
    protected function levelColour(int $order, int $total): string
    {
        if ($total <= 1) {
            return 'FF2E75B6';
        }

        // The scale runs from red at the bottom to green at the top, whatever
        // number of levels the institution has configured.
        $position = ($order - 1) / ($total - 1);

        return match (true) {
            $position < 0.34 => 'FFC00000',
            $position < 0.67 => 'FFBF8F00',
            default => 'FF548235',
        };
    }

    /**
     * Every sheet opens at the top, rather than wherever the last write left
     * the cursor.
     */
    protected function resetView(Worksheet $sheet): void
    {
        $sheet->setSelectedCell('A1');
    }
}
