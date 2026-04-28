<?php

namespace App\Exports;

use App\Models\EvaluationCriterion;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcomeType;
use App\Models\PerformanceLevel;
use App\Models\Programming;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GradingTemplateExport implements WithMultipleSheets
{
    use Exportable;

    private readonly array $enrollments;

    private readonly array $outcomesByType;

    private readonly array $criteriaByTypeId;

    private readonly array $performanceLevelNames;

    public function __construct(private readonly Programming $programming)
    {
        $this->enrollments = $programming->enrollments()
            ->where('is_active', true)
            ->with('student')
            ->orderBy('id')
            ->get()
            ->toArray();

        $outcomeIds = $programming->academicSpace
            ->microcurricularLearningOutcomes()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id');

        $outcomes = MicrocurricularLearningOutcome::whereIn('id', $outcomeIds)
            ->with('type')
            ->orderBy('type_id')
            ->orderBy('id')
            ->get();

        $this->outcomesByType = $outcomes
            ->groupBy('type_id')
            ->map(fn ($items) => $items->values()->toArray())
            ->toArray();

        $typeIds = $outcomes->pluck('type_id')->unique()->values();
        $this->criteriaByTypeId = EvaluationCriterion::whereIn(
            'microcurricular_learning_outcome_type_id',
            $typeIds
        )->orderBy('order')->get()
            ->groupBy('microcurricular_learning_outcome_type_id')
            ->map(fn ($items) => $items->values()->toArray())
            ->toArray();

        $this->performanceLevelNames = PerformanceLevel::orderBy('order')
            ->pluck('name')
            ->toArray();

        // Load type names
        $this->typeNames = MicrocurricularLearningOutcomeType::whereIn('id', $typeIds)
            ->pluck('name', 'id')
            ->toArray();
    }

    /** @phpstan-ignore-next-line */
    private array $typeNames = [];

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->outcomesByType as $typeId => $outcomes) {
            $typeName = $this->typeNames[$typeId] ?? "Tipo {$typeId}";
            $criteria = $this->criteriaByTypeId[$typeId] ?? [];

            $sheets[] = new GradingTemplateSheetExport(
                $typeName,
                $outcomes,
                $criteria,
                $this->enrollments,
                $this->performanceLevelNames,
            );
        }

        return $sheets;
    }
}
