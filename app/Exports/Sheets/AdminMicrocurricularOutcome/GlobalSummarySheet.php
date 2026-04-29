<?php

namespace App\Exports\Sheets\AdminMicrocurricularOutcome;

use App\Models\MicrocurricularLearningOutcome;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GlobalSummarySheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /** @param array<string,mixed> $summary */
    public function __construct(
        private readonly array $summary,
        private readonly MicrocurricularLearningOutcome $outcome,
    ) {}

    public function title(): string
    {
        return 'Resumen Global';
    }

    public function array(): array
    {
        $space = $this->outcome->academicSpace;
        $competency = $space?->competency;
        $nucleus = $competency?->problematicNucleus;
        $program = $nucleus?->program;
        $faculty = $program?->faculty;

        $rows = [
            ['Reporte — Resultado Microcurricular'],
            [],
            ['Código', $this->outcome->code ?? ''],
            ['Descripción', $this->outcome->description ?? ''],
            ['Tipo', $this->outcome->type?->name ?? '—'],
            ['Espacio Académico', $space?->name ?? '—'],
            ['Competencia', $competency?->name ?? '—'],
            ['Núcleo Problémico', $nucleus?->name ?? '—'],
            ['Programa', $program?->name ?? '—'],
            ['Facultad', $faculty?->name ?? '—'],
            [],
            ['Promedio Global (todos los períodos)', $this->summary['global_average']],
            ['Total Programaciones', $this->summary['total_programmings']],
            ['Total Calificaciones', $this->summary['total_grade_records']],
            [],
            ['Distribución Global de Calificaciones'],
            ['Nivel de Desempeño', 'Cantidad', '% del Total'],
        ];

        foreach ($this->summary['distribution'] as $dist) {
            $rows[] = [$dist['level_name'], $dist['count'], $dist['percentage']];
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
