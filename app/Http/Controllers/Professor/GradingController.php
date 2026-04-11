<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Professor\SaveGradesRequest;
use App\Models\EvaluationCriterion;
use App\Models\Grade;
use App\Models\MicrocurricularLearningOutcomeType;
use App\Models\PerformanceLevel;
use App\Models\Programming;
use App\Services\GradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class GradingController extends Controller
{
    public function __construct(private readonly GradingService $gradingService) {}

    public function show(Request $request, Programming $programming): Response
    {
        $this->authorizeOwnership($request, $programming);

        $academicSpace = $programming->academicSpace->load('competency');

        $outcomesByType = MicrocurricularLearningOutcomeType::query()
            ->with(['microcurricularLearningOutcomes' => function ($q) use ($academicSpace) {
                $q->where('academic_space_id', $academicSpace->id)
                    ->where('is_active', true)
                    ->orderBy('id');
            }])
            ->get()
            ->filter(fn ($type) => $type->microcurricularLearningOutcomes->isNotEmpty())
            ->values();

        $enrollments = $programming->enrollments()
            ->where('is_active', true)
            ->with('student')
            ->get(['id', 'student_id']);

        $outcomeIds = $outcomesByType->flatMap(
            fn ($type) => $type->microcurricularLearningOutcomes->pluck('id')
        );

        $enrollmentIds = $enrollments->pluck('id');

        $existingGrades = Grade::whereIn('enrollment_id', $enrollmentIds)
            ->whereIn('microcurricular_learning_outcome_id', $outcomeIds)
            ->get(['enrollment_id', 'microcurricular_learning_outcome_id', 'evaluation_criterion_id', 'performance_level_id', 'observations']);

        $completeness = $this->gradingService->completeness($programming);

        return Inertia::render('professor/grading/show', [
            'programming' => $programming->only(['id', 'period', 'group']),
            'academicSpace' => $academicSpace->only(['id', 'name', 'code']),
            'outcomesByType' => $outcomesByType,
            'enrollments' => $enrollments,
            'criteria' => EvaluationCriterion::orderBy('order')->get(['id', 'name', 'order']),
            'performanceLevels' => PerformanceLevel::orderBy('order')->get(['id', 'name', 'order']),
            'existingGrades' => $existingGrades,
            'completeness' => $completeness,
        ]);
    }

    public function saveGrades(SaveGradesRequest $request, Programming $programming): JsonResponse
    {
        $this->authorizeOwnership($request, $programming);

        $this->gradingService->saveGrades(
            $request->validated('grades'),
            $request->user()->id
        );

        return response()->json(['message' => 'Calificaciones guardadas exitosamente.']);
    }

    public function confirmConsolidation(Request $request, Programming $programming): RedirectResponse|JsonResponse
    {
        $this->authorizeOwnership($request, $programming);

        $completeness = $this->gradingService->completeness($programming);

        if ($completeness['percentage'] < 100.0) {
            return response()->json([
                'message' => 'No se puede confirmar el consolidado. Aún hay calificaciones pendientes.',
                'completeness' => $completeness,
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => 'Consolidado confirmado exitosamente.']);
    }

    private function authorizeOwnership(Request $request, Programming $programming): void
    {
        $professor = $request->user()->professor;

        if (! $professor || $programming->professor_id !== $professor->id) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }
    }
}
