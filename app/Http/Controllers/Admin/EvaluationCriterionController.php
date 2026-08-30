<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEvaluationCriterionRequest;
use App\Http\Requests\Admin\UpdateEvaluationCriterionRequest;
use App\Models\EvaluationCriterion;
use App\Models\MicrocurricularLearningOutcomeType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationCriterionController extends Controller
{
    public function index(): Response
    {
        $typeId = request('microcurricular_learning_outcome_type_id');

        $criteria = EvaluationCriterion::query()
            ->with('outcomeType:id,name')
            ->withCount('grades')
            ->when(request('search'), fn ($q, $search) => $q->where(
                fn ($sub) => $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            ))
            ->when($typeId, fn ($q) => $q->where('microcurricular_learning_outcome_type_id', $typeId))
            ->orderBy('microcurricular_learning_outcome_type_id')
            ->orderBy('order')
            ->get();

        return Inertia::render('admin/evaluation-criteria/index', [
            'criteria' => $criteria,
            'types' => MicrocurricularLearningOutcomeType::orderBy('id')->get(['id', 'name']),
            'filters' => request()->only(['search', 'microcurricular_learning_outcome_type_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/evaluation-criteria/create', [
            'types' => MicrocurricularLearningOutcomeType::orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function store(StoreEvaluationCriterionRequest $request): RedirectResponse
    {
        EvaluationCriterion::create($request->validated());

        return to_route('admin.evaluation-criteria.index')
            ->with('success', 'Criterio de evaluación creado exitosamente.');
    }

    public function edit(EvaluationCriterion $evaluationCriterion): Response
    {
        return Inertia::render('admin/evaluation-criteria/edit', [
            'criterion' => $evaluationCriterion->only([
                'id', 'name', 'description', 'order', 'is_active',
                'microcurricular_learning_outcome_type_id',
            ]),
            'types' => MicrocurricularLearningOutcomeType::orderBy('id')->get(['id', 'name']),
            'gradesCount' => $evaluationCriterion->grades()->count(),
        ]);
    }

    public function update(UpdateEvaluationCriterionRequest $request, EvaluationCriterion $evaluationCriterion): RedirectResponse
    {
        $evaluationCriterion->update($request->validated());

        return to_route('admin.evaluation-criteria.index')
            ->with('success', 'Criterio de evaluación actualizado exitosamente.');
    }

    public function toggleStatus(EvaluationCriterion $evaluationCriterion): RedirectResponse
    {
        // Retiring a criterion still in use would hide the marks recorded
        // against it, so the operation is refused while grades depend on it.
        if ($evaluationCriterion->is_active) {
            $gradesCount = $evaluationCriterion->grades()->count();

            if ($gradesCount > 0) {
                return back()->with(
                    'error',
                    "No se puede desactivar este criterio porque {$gradesCount} calificación(es) dependen de él."
                );
            }
        }

        $evaluationCriterion->update(['is_active' => ! $evaluationCriterion->is_active]);

        $status = $evaluationCriterion->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Criterio {$status} exitosamente.");
    }
}
