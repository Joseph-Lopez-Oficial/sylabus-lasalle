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
        return ['Estudiante', 'Promedio Final'];
    }

    public function array(): array
    {
        return array_map(fn ($s) => [
            $s['student_name'],
            $s['final_average'],
        ], $this->byStudent);
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
