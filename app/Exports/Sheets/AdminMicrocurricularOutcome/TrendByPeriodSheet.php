<?php

namespace App\Exports\Sheets\AdminMicrocurricularOutcome;

use App\Exports\Concerns\StyledFlatTable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrendByPeriodSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use StyledFlatTable;

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
        $this->styleFlatTable($sheet, count($this->trendByPeriod), 'B', ['B'], ['A']);

        return [];
    }
}
