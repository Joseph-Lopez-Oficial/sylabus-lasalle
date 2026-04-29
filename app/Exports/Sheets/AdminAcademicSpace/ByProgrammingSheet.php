<?php

namespace App\Exports\Sheets\AdminAcademicSpace;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ByProgrammingSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @param list<array<string,mixed>> $byProgramming */
    public function __construct(private readonly array $byProgramming) {}

    public function title(): string
    {
        return 'Por Programación';
    }

    public function headings(): array
    {
        return ['Período', 'Grupo', 'Profesor', '# Estudiantes', 'Promedio Grupo', 'Más Alto', 'Más Bajo'];
    }

    public function array(): array
    {
        return array_map(fn ($p) => [
            $p['period'] ?? '—',
            $p['group'] ?? '—',
            $p['professor']
                ? ($p['professor']['first_name'].' '.$p['professor']['last_name'])
                : '—',
            $p['student_count'],
            $p['group_average'],
            $p['highest'],
            $p['lowest'],
        ], $this->byProgramming);
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
