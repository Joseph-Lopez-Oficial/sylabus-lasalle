<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Programming;
use App\Services\GradingService;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly GradingService $gradingService,
        private readonly StatisticsService $statisticsService,
    ) {}

    public function show(Request $request, Programming $programming): Response
    {
        $this->authorizeOwnership($request, $programming);

        $completeness = $this->gradingService->completeness($programming);

        if ($completeness['percentage'] < 100.0) {
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'Las calificaciones de esta programación no están completas. Complete todas las calificaciones antes de consultar las estadísticas.');
        }

        $programming->load(['academicSpace', 'professor', 'modality']);
        $statistics = $this->statisticsService->calculate($programming);

        return Inertia::render('professor/statistics/show', [
            'programming' => [
                'id' => $programming->id,
                'period' => $programming->period,
                'group' => $programming->group,
                'academic_space' => $programming->academicSpace ? [
                    'id' => $programming->academicSpace->id,
                    'name' => $programming->academicSpace->name,
                    'code' => $programming->academicSpace->code,
                ] : null,
                'professor' => $programming->professor ? [
                    'id' => $programming->professor->id,
                    'first_name' => $programming->professor->first_name,
                    'last_name' => $programming->professor->last_name,
                ] : null,
                'modality' => $programming->modality ? [
                    'id' => $programming->modality->id,
                    'name' => $programming->modality->name,
                ] : null,
            ],
            'statistics' => $statistics,
            'completeness' => $completeness,
        ]);
    }

    private function authorizeOwnership(Request $request, Programming $programming): void
    {
        $professor = $request->user()->professor;

        if (! $professor || $programming->professor_id !== $professor->id) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }
    }
}
