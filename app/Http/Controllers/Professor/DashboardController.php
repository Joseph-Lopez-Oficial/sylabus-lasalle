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
                'enrollments' => fn($q) => $q->where('is_active', true),
            ])
            ->get()
            ->map(function (Programming $programming) {
                $enrollmentIds = $programming->enrollments->pluck('id');

                $outcomes = MicrocurricularLearningOutcome::query()
                    ->where('academic_space_id', $programming->academic_space_id)
                    ->where('is_active', true)
                    ->get(['id', 'type_id']);

                // Total = sum of (criteria_count_for_type × enrollments) per outcome
                $criteriaCountByType = EvaluationCriterion::whereIn(
                    'microcurricular_learning_outcome_type_id',
                    $outcomes->pluck('type_id')->unique()
                )->get(['id', 'microcurricular_learning_outcome_type_id'])
                    ->groupBy('microcurricular_learning_outcome_type_id')
                    ->map->count();

                $total = $enrollmentIds->count() * $outcomes->sum(
                    fn($o) => $criteriaCountByType[$o->type_id] ?? 0
                );

                $outcomeIds = $outcomes->pluck('id');
                $completed = $total > 0
                    ? Grade::whereIn('enrollment_id', $enrollmentIds)
                    ->whereIn('microcurricular_learning_outcome_id', $outcomeIds)
                    ->count()
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
