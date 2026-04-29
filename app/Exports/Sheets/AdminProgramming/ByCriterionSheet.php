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

class ByCriterionSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @param list<array<string,mixed>> $byCriterion */
    public function __construct(private readonly array $byCriterion) {}

    public function title(): string
    {
        return 'Por Criterio';
    }

    public function headings(): array
    {
        return ['Tipo de RA', 'Criterio de Evaluación', 'Resultado Asociado', 'Promedio Grupo'];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->byCriterion as $criterion) {
            $byOutcome = $criterion['by_outcome'] ?? [];

            if (empty($byOutcome)) {
                $rows[] = [
                    $criterion['type_name'] ?? '—',
                    $criterion['criterion_name'],
                    '',
                    $criterion['group_average'],
                ];

                continue;
            }

            foreach ($byOutcome as $i => $outcome) {
                $rows[] = [
                    $i === 0 ? ($criterion['type_name'] ?? '—') : '',
                    $i === 0 ? $criterion['criterion_name'] : '',
                    $outcome['outcome_desc'] ?? '',
                    $i === 0 ? $criterion['group_average'] : ($outcome['group_average'] ?? ''),
                ];
            }
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
}
