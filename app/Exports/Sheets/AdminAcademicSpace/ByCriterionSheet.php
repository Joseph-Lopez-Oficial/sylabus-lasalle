<?php

namespace App\Exports\Sheets\AdminAcademicSpace;

use App\Exports\Concerns\StyledFlatTable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ByCriterionSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use StyledFlatTable;

    /** @param list<array<string,mixed>> $byCriterion */
    public function __construct(private readonly array $byCriterion) {}

    public function title(): string
    {
        return 'Por Criterio';
    }

    public function headings(): array
    {
        return ['Tipo de RA', 'Criterio de Evaluación', 'Promedio Grupo'];
    }

    public function array(): array
    {
        return array_map(fn ($c) => [
            $c['type_name'] ?? '—',
            $c['criterion_name'],
            $c['group_average'],
        ], $this->byCriterion);
    }

    public function styles(Worksheet $sheet): array
    {
        $this->styleFlatTable($sheet, count($this->byCriterion), 'C', ['C'], ['A']);

        return [];
    }
}
