<?php

namespace App\Exports\Sheets\AdminMicrocurricularOutcome;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrendByPeriodSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @param list<array<string,mixed>> $trendByPeriod */
    public function __construct(private readonly array $trendByPeriod) {}

    public function title(): string
    {
        return 'Tendencia por Período';
    }

    public function headings(): array
    {
        return ['Período Académico', 'Promedio del Resultado'];
    }

    public function array(): array
    {
        return array_map(fn ($t) => [
            $t['period'],
            $t['average'],
        ], $this->trendByPeriod);
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
