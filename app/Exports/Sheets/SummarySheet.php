<?php

namespace App\Exports\Sheets;

use App\Models\Programming;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummarySheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /** @param array<string,mixed> $summary */
    public function __construct(
        private readonly array $summary,
        private readonly Programming $programming,
    ) {}

    public function title(): string
    {
        return 'Resumen Global';
    }

    public function array(): array
    {
        $rows = [
            ['Reporte de Calificaciones — Resumen Global'],
            [],
            ['Espacio Académico', $this->programming->academicSpace->name ?? ''],
            ['Período', $this->programming->period],
            ['Grupo', $this->programming->group ?? 'N/A'],
            ['Promedio General', $this->summary['overall_average']],
            [],
            ['Distribución de Niveles de Desempeño'],
            ['Nivel', 'Cantidad', 'Porcentaje (%)'],
        ];

        foreach ($this->summary['distribution'] as $dist) {
            $rows[] = [$dist['level_name'], $dist['count'], $dist['percentage']];
        }

        $rows[] = [];
        $rows[] = ['Top 5 Estudiantes'];
        $rows[] = ['Estudiante', 'Promedio'];

        foreach ($this->summary['top_students'] as $student) {
            $rows[] = [$student['student_name'], $student['final_average']];
        }

        if (! empty($this->summary['below_basic'])) {
            $rows[] = [];
            $rows[] = ['Estudiantes por debajo del nivel Básico'];
            $rows[] = ['Estudiante', 'Promedio'];

            foreach ($this->summary['below_basic'] as $student) {
                $rows[] = [$student['student_name'], $student['final_average']];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'A' => ['font' => ['bold' => true]],
        ];
    }
}
