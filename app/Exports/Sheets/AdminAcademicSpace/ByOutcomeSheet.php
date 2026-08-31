<?php

namespace App\Exports\Sheets\AdminAcademicSpace;

use App\Exports\Concerns\StyledFlatTable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ByOutcomeSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use StyledFlatTable;

    /** @param list<array<string,mixed>> $byOutcome */
    public function __construct(private readonly array $byOutcome) {}

    public function title(): string
    {
        return 'Por Resultado';
    }

    public function headings(): array
    {
        return ['Tipo de RA', 'Resultado Microcurricular', 'Promedio', 'Más Alto', 'Más Bajo', '# Programaciones'];
    }

    public function array(): array
    {
        return array_map(fn ($o) => [
            $o['type_name'] ?? '—',
            $o['outcome_desc'],
            $o['group_average'],
            $o['highest'],
            $o['lowest'],
            $o['programming_count'] ?? 1,
        ], $this->byOutcome);
    }

    public function styles(Worksheet $sheet): array
    {
        $this->styleFlatTable($sheet, count($this->byOutcome), 'F', ['C', 'D', 'E'], ['A', 'F']);

        return [];
    }
}
