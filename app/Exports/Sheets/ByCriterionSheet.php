<?php

namespace App\Exports\Sheets;

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
        return ['Criterio de Evaluación', 'Promedio Grupo'];
    }

    public function array(): array
    {
        return array_map(fn ($c) => [
            $c['criterion_name'],
            $c['group_average'],
        ], $this->byCriterion);
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
