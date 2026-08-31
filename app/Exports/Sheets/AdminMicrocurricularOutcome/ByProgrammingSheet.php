<?php

namespace App\Exports\Sheets\AdminMicrocurricularOutcome;

use App\Exports\Concerns\StyledFlatTable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ByProgrammingSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use StyledFlatTable;

    /** @param list<array<string,mixed>> $byProgramming */
    public function __construct(private readonly array $byProgramming) {}

    public function title(): string
    {
        return 'Por Programación';
    }

    public function headings(): array
    {
        return ['Período', 'Grupo', 'Profesor', 'Espacio Académico', '# Estudiantes', 'Promedio', 'Más Alto', 'Más Bajo'];
    }

    public function array(): array
    {
        return array_map(fn ($p) => [
            $p['period'] ?? '—',
            $p['group'] ?? '—',
            $p['professor']
                ? ($p['professor']['first_name'].' '.$p['professor']['last_name'])
                : '—',
            $p['academic_space']['name'] ?? '—',
            $p['student_count'],
            $p['group_average'],
            $p['highest'],
            $p['lowest'],
        ], $this->byProgramming);
    }

    public function styles(Worksheet $sheet): array
    {
        $this->styleFlatTable($sheet, count($this->byProgramming), 'H', ['F', 'G', 'H'], ['A', 'B', 'E']);

        return [];
    }
}
