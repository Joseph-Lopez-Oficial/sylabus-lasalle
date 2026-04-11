<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMesocurricularLearningOutcomeRequest;
use App\Http\Requests\Admin\UpdateMesocurricularLearningOutcomeRequest;
use App\Models\Competency;
use App\Models\MesocurricularLearningOutcome;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MesocurricularLearningOutcomeController extends Controller
{
    public function index(): Response
    {
        $outcomes = MesocurricularLearningOutcome::query()
            ->with('competency.problematicNucleus')
            ->when(request('search'), fn ($q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->when(request('competency_id'), fn ($q, $competencyId) => $q->where('competency_id', $competencyId))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $competencies = Competency::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/mesocurricular-outcomes/index', [
            'outcomes' => $outcomes,
            'competencies' => $competencies,
            'filters' => request()->only('search', 'competency_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/mesocurricular-outcomes/create', [
            'competencies' => Competency::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreMesocurricularLearningOutcomeRequest $request): RedirectResponse
    {
        MesocurricularLearningOutcome::create($request->validated());

        return to_route('admin.mesocurricular-outcomes.index')->with('success', 'Resultado mesocurricular creado exitosamente.');
    }

    public function edit(MesocurricularLearningOutcome $mesocurricularOutcome): Response
    {
        return Inertia::render('admin/mesocurricular-outcomes/edit', [
            'outcome' => $mesocurricularOutcome->load('competency'),
            'competencies' => Competency::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateMesocurricularLearningOutcomeRequest $request, MesocurricularLearningOutcome $mesocurricularOutcome): RedirectResponse
    {
        $mesocurricularOutcome->update($request->validated());

        return to_route('admin.mesocurricular-outcomes.index')->with('success', 'Resultado mesocurricular actualizado exitosamente.');
    }

    public function toggleStatus(MesocurricularLearningOutcome $mesocurricularOutcome): RedirectResponse
    {
        $mesocurricularOutcome->update(['is_active' => ! $mesocurricularOutcome->is_active]);

        $status = $mesocurricularOutcome->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Resultado mesocurricular {$status} exitosamente.");
    }
}
