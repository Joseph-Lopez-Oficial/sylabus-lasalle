<?php

namespace App\Exports\Sheets\AdminProgramming;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ByStudentSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @param list<array<string,mixed>> $byStudent */
    public function __construct(private readonly array $byStudent) {}

    public function title(): string
    {
        return 'Por Estudiante';
    }

    public function headings(): array
    {
        return ['Estudiante', 'Promedio Final', 'Tipo de RA', 'Resultado Microcurricular', 'Promedio RA'];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->byStudent as $student) {
            $outcomes = $student['by_outcome'] ?? [];

            if (empty($outcomes)) {
                $rows[] = [$student['student_name'], $student['final_average'], '', '', ''];

                continue;
            }

            foreach ($outcomes as $i => $outcome) {
                $rows[] = [
                    $i === 0 ? $student['student_name'] : '',
                    $i === 0 ? $student['final_average'] : '',
                    $outcome['type_name'] ?? '—',
                    $outcome['outcome_desc'] ?? '',
                    $outcome['grade'] ?? '',
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];

        $currentRow = 2;
        $studentIndex = 0;
        foreach ($this->byStudent as $student) {
            $outcomeCount = max(1, count($student['by_outcome'] ?? []));
            $fillColor = $studentIndex % 2 === 0 ? 'EFF6FF' : 'DBEAFE';
            for ($r = $currentRow; $r < $currentRow + $outcomeCount; $r++) {
                $styles[$r] = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
                ];
            }
            $currentRow += $outcomeCount;
            $studentIndex++;
        }

        return $styles;
    }
}
