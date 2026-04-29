<?php

namespace App\Exports;

use App\Models\AcademicSpace;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AdminAcademicSpaceStatisticsExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly AcademicSpace $academicSpace,
        private readonly array $statistics,
    ) {}

    public function sheets(): array
    {
        $summary = $this->statistics['summary'];
        $summary['distribution'] = collect($summary['distribution'])->toArray();
        $summary['trend_by_period'] = collect($summary['trend_by_period'] ?? [])->toArray();

        return [
            new Sheets\AdminAcademicSpace\SummarySheet($summary, $this->academicSpace),
            new Sheets\AdminAcademicSpace\ByProgrammingSheet(collect($this->statistics['by_programming'])->toArray()),
            new Sheets\AdminAcademicSpace\ByOutcomeSheet(collect($this->statistics['by_outcome'])->toArray()),
            new Sheets\AdminAcademicSpace\ByCriterionSheet(collect($this->statistics['by_criterion'])->toArray()),
        ];
    }
}
