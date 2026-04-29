<?php

namespace App\Exports\Sheets\AdminAcademicSpace;

use App\Models\AcademicSpace;
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
        private readonly AcademicSpace $academicSpace,
    ) {}

    public function title(): string
    {
        return 'Resumen Global';
    }

    public function array(): array
    {
        $rows = [
            ['Reporte de Estadísticas — Espacio Académico'],
            [],
            ['Espacio Académico', $this->academicSpace->name],
            ['Código', $this->academicSpace->code ?? ''],
            ['Competencia', $this->academicSpace->competency?->name ?? '—'],
            [],
            ['Promedio Global', $this->summary['global_average']],
            ['Total Programaciones', $this->summary['total_programmings']],
            ['Total Calificaciones', $this->summary['total_grade_records']],
            [],
            ['Distribución de Calificaciones'],
            ['Nivel de Desempeño', 'Cantidad', '% del Total'],
        ];

        foreach ($this->summary['distribution'] as $dist) {
            $rows[] = [$dist['level_name'], $dist['count'], $dist['percentage']];
        }

        if (! empty($this->summary['trend_by_period'])) {
            $rows[] = [];
            $rows[] = ['Tendencia por Período'];
            $rows[] = ['Período', 'Promedio'];

            foreach ($this->summary['trend_by_period'] as $trend) {
                $rows[] = [$trend['period'], $trend['average']];
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
