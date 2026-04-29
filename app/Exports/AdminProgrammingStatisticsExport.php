<?php

namespace App\Exports;

use App\Models\Programming;
use App\Services\StatisticsService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AdminProgrammingStatisticsExport implements WithMultipleSheets
{
    use Exportable;

    private readonly array $statistics;

    public function __construct(
        private readonly Programming $programming,
        StatisticsService $statisticsService,
    ) {
        $this->statistics = $statisticsService->calculate($programming);
    }

    public function sheets(): array
    {
        return [
            new Sheets\AdminProgramming\SummarySheet($this->statistics['summary'], $this->programming),
            new Sheets\AdminProgramming\ByStudentSheet($this->statistics['byStudent']),
            new Sheets\AdminProgramming\ByOutcomeSheet($this->statistics['byOutcome']),
            new Sheets\AdminProgramming\ByCriterionSheet($this->statistics['byCriterion']),
        ];
    }
}
