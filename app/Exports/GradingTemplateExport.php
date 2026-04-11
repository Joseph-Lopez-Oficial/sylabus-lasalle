<?php

namespace App\Exports;

use App\Models\EvaluationCriterion;
use App\Models\PerformanceLevel;
use App\Models\Programming;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GradingTemplateExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles
{
    private readonly array $enrollments;

    private readonly array $criteria;

    private readonly array $performanceLevelNames;

    private readonly array $outcomes;

    public function __construct(private readonly Programming $programming)
    {
        $this->enrollments = $programming->enrollments()
            ->where('is_active', true)
            ->with('student')
            ->get()
            ->toArray();

        $this->outcomes = $programming->academicSpace
            ->microcurricularLearningOutcomes()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->toArray();

        $this->criteria = EvaluationCriterion::orderBy('order')->get()->toArray();

        $this->performanceLevelNames = PerformanceLevel::orderBy('order')
            ->pluck('name')
            ->toArray();
    }

    public function headings(): array
    {
        $headers = ['enrollment_id', 'Estudiante'];

        foreach ($this->outcomes as $outcome) {
            foreach ($this->criteria as $criterion) {
                $headers[] = 'RA'.$outcome['id'].'_'.$criterion['id'];
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
                ($enrollment['student']['first_name'] ?? '').' '.($enrollment['student']['last_name'] ?? ''),
            ];

            // Empty cells for each outcome×criterion combination
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
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
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
                $totalColumns = 2 + count($this->outcomes) * count($this->criteria);

                if ($totalRows === 0) {
                    return;
                }

                // Add dropdown validation to grade cells (columns C onward)
                for ($col = 3; $col <= $totalColumns; $col++) {
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
            },
        ];
    }
}
