<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GradingTemplateSheetExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $typeName,
        private readonly array $outcomes,
        private readonly array $criteria,
        private readonly array $enrollments,
        private readonly array $performanceLevelNames,
    ) {}

    public function title(): string
    {
        // Sheet name: "RA-Conocimiento", "RA-Habilidad", "RA-Actitud"
        return 'RA-'.$this->typeName;
    }

    public function headings(): array
    {
        // enrollment_id (hidden col A), Documento (B), Estudiante (C), then criteria × outcomes
        $headers = ['enrollment_id [NO EDITAR]', 'Documento', 'Estudiante'];

        foreach ($this->outcomes as $outcome) {
            foreach ($this->criteria as $criterion) {
                $code = $outcome['code'] ?? ('RA'.$outcome['id']);
                $headers[] = "{$code} — {$criterion['name']}";
            }
        }

        return $headers;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->enrollments as $enrollment) {
            $row = [
                $enrollment['id'],
                $enrollment['student']['document_number'] ?? '',
                ($enrollment['student']['first_name'] ?? '').' '.($enrollment['student']['last_name'] ?? ''),
            ];

            foreach ($this->outcomes as $outcome) {
                foreach ($this->criteria as $criterion) {
                    $row[] = '';
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $levelList = '"'.implode(',', $this->performanceLevelNames).'"';
                $totalRows = count($this->enrollments);
                // Cols: A=enrollment_id, B=documento, C=estudiante, D onward = grades
                $gradeCols = count($this->outcomes) * count($this->criteria);
                $firstGradeCol = 4; // D
                $lastGradeCol = $firstGradeCol + $gradeCols - 1;

                if ($totalRows === 0) {
                    return;
                }

                // Hide column A (enrollment_id)
                $sheet->getColumnDimension('A')->setVisible(false);

                // Add dropdown validation to grade cells (column D onward)
                for ($col = $firstGradeCol; $col <= $lastGradeCol; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

                    for ($row = 2; $row <= $totalRows + 1; $row++) {
                        $validation = $sheet->getCell("{$colLetter}{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(DataValidation::STYLE_STOP);
                        $validation->setAllowBlank(true);
                        $validation->setShowDropDown(true);
                        $validation->setFormula1($levelList);
                    }
                }

                // Color row 1 header per column group
                $sheet->getRowDimension(1)->setRowHeight(40);
            },
        ];
    }
}
