<?php

namespace App\Exports\Sheets;

use App\Models\Grade;
use App\Models\PerformanceLevel;
use App\Models\Programming;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RawGradesSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly Programming $programming) {}

    public function title(): string
    {
        return 'Calificaciones Brutas';
    }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        $enrollmentIds = $this->programming->enrollments()
            ->where('is_active', true)
            ->pluck('id');

        return Grade::query()
            ->whereIn('enrollment_id', $enrollmentIds)
            ->with(['enrollment.student', 'microcurricularLearningOutcome.type', 'evaluationCriterion', 'performanceLevel'])
            ->orderBy('enrollment_id')
            ->orderBy('microcurricular_learning_outcome_id');
    }

    public function headings(): array
    {
        return ['Estudiante', 'Tipo de RA', 'Resultado Microcurricular', 'Criterio de Evaluación', 'Nivel de Desempeño', 'Nota'];
    }

    /** @param Grade $grade */
    public function map($grade): array
    {
        return [
            ($grade->enrollment->student->first_name ?? '').' '.($grade->enrollment->student->last_name ?? ''),
            $grade->microcurricularLearningOutcome->type?->name ?? '—',
            $grade->microcurricularLearningOutcome->description ?? '',
            $grade->evaluationCriterion->name ?? '',
            $grade->performanceLevel->name ?? '',
            PerformanceLevel::gradeForOrder($grade->performanceLevel->order),
        ];
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
