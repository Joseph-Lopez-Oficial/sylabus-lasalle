<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriterion;
use App\Models\Grade;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\Programming;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $professor = $request->user()->professor;

        $programmings = Programming::query()
            ->where('professor_id', $professor?->id)
            ->where('is_active', true)
            ->with([
                'academicSpace',
                'modality',
                'academicPeriod',
                'enrollments' => fn ($q) => $q->where('is_active', true),
            ])
            ->get();

        // Preload everything the progress calculation needs, so the map below
        // runs entirely in memory instead of querying once per programming.
        $outcomesBySpace = MicrocurricularLearningOutcome::query()
            ->whereIn('academic_space_id', $programmings->pluck('academic_space_id')->unique())
            ->where('is_active', true)
            ->get(['id', 'academic_space_id', 'type_id'])
            ->groupBy('academic_space_id');

        $criteriaCountByType = EvaluationCriterion::query()
            ->get(['id', 'microcurricular_learning_outcome_type_id'])
            ->groupBy('microcurricular_learning_outcome_type_id')
            ->map->count();

        $gradeCountByEnrollment = Grade::query()
            ->whereIn('enrollment_id', $programmings->pluck('enrollments')->flatten()->pluck('id'))
            ->selectRaw('enrollment_id, count(*) as aggregate')
            ->groupBy('enrollment_id')
            ->pluck('aggregate', 'enrollment_id');

        $programmings = $programmings
            ->map(function (Programming $programming) use ($outcomesBySpace, $criteriaCountByType, $gradeCountByEnrollment) {
                $enrollmentIds = $programming->enrollments->pluck('id');
                $outcomes = $outcomesBySpace[$programming->academic_space_id] ?? collect();

                // Total = sum of (criteria_count_for_type × enrollments) per outcome
                $total = $enrollmentIds->count() * $outcomes->sum(
                    fn ($o) => $criteriaCountByType[$o->type_id] ?? 0
                );

                $completed = $total > 0
                    ? $enrollmentIds->sum(fn ($id) => $gradeCountByEnrollment[$id] ?? 0)
                    : 0;

                $percentage = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

                return [
                    'id' => $programming->id,
                    'period' => $programming->academicPeriod?->name,
                    'group' => $programming->group,
                    'academic_space' => $programming->academicSpace->only(['id', 'name', 'code']),
                    'modality' => $programming->modality->only(['id', 'name']),
                    'enrolled_count' => $enrollmentIds->count(),
                    'grading_percentage' => $percentage,
                ];
            });

        return Inertia::render('professor/dashboard', [
            'programmings' => $programmings,
        ]);
    }
}
