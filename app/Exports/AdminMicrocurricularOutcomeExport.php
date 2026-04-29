<?php

namespace App\Exports;

use App\Models\MicrocurricularLearningOutcome;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AdminMicrocurricularOutcomeExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly MicrocurricularLearningOutcome $outcome,
        private readonly array $summary,
        private readonly array $byProgramming,
    ) {}

    public function sheets(): array
    {
        return [
            new Sheets\AdminMicrocurricularOutcome\GlobalSummarySheet($this->summary, $this->outcome),
            new Sheets\AdminMicrocurricularOutcome\ByProgrammingSheet($this->byProgramming),
            new Sheets\AdminMicrocurricularOutcome\TrendByPeriodSheet($this->summary['trend_by_period'] ?? []),
        ];
    }
}
