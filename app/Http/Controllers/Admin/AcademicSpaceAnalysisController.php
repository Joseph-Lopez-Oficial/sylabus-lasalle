<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Programming;
use App\Services\AcademicSpaceAnalysisService;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSpaceAnalysisController extends Controller
{
    public function __construct(
        private readonly AcademicSpaceAnalysisService $analysisService,
    ) {}

    /**
     * Read-only view of any programming's analysis.
     *
     * The administrator consults what the professor wrote but never edits it,
     * so no save endpoint exists on this side.
     */
    public function show(Programming $programming): Response
    {
        $programming->load([
            'academicSpace:id,code,name',
            'academicPeriod:id,name',
            'professor:id,first_name,last_name',
        ]);

        $outcomes = $this->analysisService->analysableOutcomes($programming);
        $averages = $this->analysisService->outcomeAverages($programming);

        $existing = $programming->academicSpaceAnalyses()
            ->get()
            ->keyBy('microcurricular_learning_outcome_id');

        return Inertia::render('admin/programmings/analysis', [
            'programming' => $programming->only(['id', 'group']),
            'academicSpace' => $programming->academicSpace?->only(['id', 'code', 'name']),
            'academicPeriod' => $programming->academicPeriod?->only(['id', 'name']),
            'professor' => $programming->professor?->only(['id', 'first_name', 'last_name']),
            'outcomes' => $outcomes->map(fn ($outcome) => [
                'id' => $outcome->id,
                'code' => $outcome->code,
                'description' => $outcome->description,
                'type' => $outcome->type?->only(['id', 'name']),
                'average' => $averages[$outcome->id] ?? null,
                'analysis' => $existing->get($outcome->id)?->only([
                    'outcome_performance',
                    'academic_space_performance',
                    'improvement_proposals',
                ]),
            ])->values(),
        ]);
    }
}
