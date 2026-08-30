<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Professor\SaveAcademicSpaceAnalysisRequest;
use App\Models\Programming;
use App\Services\AcademicSpaceAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AcademicSpaceAnalysisController extends Controller
{
    public function __construct(
        private readonly AcademicSpaceAnalysisService $analysisService,
    ) {}

    public function show(Request $request, Programming $programming): Response
    {
        $this->authorizeOwnership($request, $programming);

        $programming->load(['academicSpace:id,code,name', 'academicPeriod:id,name']);

        $outcomes = $this->analysisService->analysableOutcomes($programming);
        $averages = $this->analysisService->outcomeAverages($programming);

        $existing = $programming->academicSpaceAnalyses()
            ->get()
            ->keyBy('microcurricular_learning_outcome_id');

        return Inertia::render('professor/analysis/show', [
            'programming' => $programming->only(['id', 'group']),
            'academicSpace' => $programming->academicSpace?->only(['id', 'code', 'name']),
            'academicPeriod' => $programming->academicPeriod?->only(['id', 'name']),
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
            'canEdit' => true,
        ]);
    }

    public function save(SaveAcademicSpaceAnalysisRequest $request, Programming $programming): RedirectResponse
    {
        $this->authorizeOwnership($request, $programming);

        $this->analysisService->save(
            $request->validated()['analyses'],
            $programming,
            $request->user()->id
        );

        return back()->with('success', 'Análisis guardado exitosamente.');
    }

    private function authorizeOwnership(Request $request, Programming $programming): void
    {
        $professor = $request->user()->professor;

        if (! $professor || $programming->professor_id !== $professor->id) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }
    }
}
