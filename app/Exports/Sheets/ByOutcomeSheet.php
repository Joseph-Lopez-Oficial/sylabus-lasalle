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

class ByOutcomeSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @param list<array<string,mixed>> $byOutcome */
    public function __construct(private readonly array $byOutcome) {}

    public function title(): string
    {
        return 'Por Resultado';
    }

    public function headings(): array
    {
        return ['Resultado Microcurricular', 'Promedio Grupo', 'Más Alto', 'Más Bajo'];
    }

    public function array(): array
    {
        return array_map(fn ($o) => [
            $o['outcome_desc'],
            $o['group_average'],
            $o['highest'],
            $o['lowest'],
        ], $this->byOutcome);
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
