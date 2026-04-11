<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Grade;
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
                'enrollments' => fn ($q) => $q->where('is_active', true),
            ])
            ->get()
            ->map(function (Programming $programming) {
                $enrollmentIds = $programming->enrollments->pluck('id');

                $outcomeIds = $programming->academicSpace
                    ->microcurricularLearningOutcomes()
                    ->where('is_active', true)
                    ->pluck('id');

                $criterionCount = \App\Models\EvaluationCriterion::count();
                $total = $enrollmentIds->count() * $outcomeIds->count() * $criterionCount;

                $completed = $total > 0
                    ? Grade::whereIn('enrollment_id', $enrollmentIds)
                        ->whereIn('microcurricular_learning_outcome_id', $outcomeIds)
                        ->count()
                    : 0;

                $percentage = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

                return [
                    'id' => $programming->id,
                    'period' => $programming->period,
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
