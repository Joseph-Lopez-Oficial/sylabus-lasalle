<?php

namespace App\Exports;

use App\Models\Programming;
use App\Services\InstitutionalReportBuilder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The measurement report in the format the coordination works with.
 *
 * It carries the same sheets and the same order as the files the faculty
 * delivers, so what the system produces can be read — and filled in — by anyone
 * used to them, and uploaded back without any intermediate step.
 */
class InstitutionalReportExport implements WithMultipleSheets
{
    use Exportable;

    /** @var array<string, mixed> */
    private readonly array $report;

    public function __construct(
        Programming $programming,
        InstitutionalReportBuilder $builder,
    ) {
        $this->report = $builder->build($programming);
    }

    public function sheets(): array
    {
        $sheets = [
            new Sheets\Institutional\ConsolidatedSheet($this->report),
            new Sheets\Institutional\PerStudentSheet($this->report),
        ];

        foreach ($this->report['outcomes'] as $outcome) {
            $sheets[] = new Sheets\Institutional\OutcomeGradingSheet(
                $outcome,
                $this->report['students'],
                $this->report['levels'],
            );
        }

        $sheets[] = new Sheets\Institutional\AnalysisSheet($this->report);

        return $sheets;
    }
}
