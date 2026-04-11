<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMicrocurricularLearningOutcomeRequest;
use App\Http\Requests\Admin\UpdateMicrocurricularLearningOutcomeRequest;
use App\Models\AcademicSpace;
use App\Models\MesocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcomeType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MicrocurricularLearningOutcomeController extends Controller
{
    public function index(): Response
    {
        $outcomes = MicrocurricularLearningOutcome::query()
            ->with(['academicSpace', 'type', 'mesocurricularLearningOutcome'])
            ->when(request('search'), fn ($q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->when(request('academic_space_id'), fn ($q, $id) => $q->where('academic_space_id', $id))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $academicSpaces = AcademicSpace::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/microcurricular-outcomes/index', [
            'outcomes' => $outcomes,
            'academicSpaces' => $academicSpaces,
            'filters' => request()->only('search', 'academic_space_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/microcurricular-outcomes/create', [
            'academicSpaces' => AcademicSpace::query()->active()->orderBy('name')->get(['id', 'name']),
            'types' => MicrocurricularLearningOutcomeType::query()->orderBy('name')->get(['id', 'name']),
            'mesocurricularOutcomes' => MesocurricularLearningOutcome::query()->active()->orderByDesc('id')->get(['id', 'description']),
        ]);
    }

    public function store(StoreMicrocurricularLearningOutcomeRequest $request): RedirectResponse
    {
        MicrocurricularLearningOutcome::create($request->validated());

        return to_route('admin.microcurricular-outcomes.index')->with('success', 'Resultado microcurricular creado exitosamente.');
    }

    public function edit(MicrocurricularLearningOutcome $microcurricularOutcome): Response
    {
        return Inertia::render('admin/microcurricular-outcomes/edit', [
            'outcome' => $microcurricularOutcome->load(['academicSpace', 'type', 'mesocurricularLearningOutcome']),
            'academicSpaces' => AcademicSpace::query()->active()->orderBy('name')->get(['id', 'name']),
            'types' => MicrocurricularLearningOutcomeType::query()->orderBy('name')->get(['id', 'name']),
            'mesocurricularOutcomes' => MesocurricularLearningOutcome::query()->active()->orderByDesc('id')->get(['id', 'description']),
        ]);
    }

    public function update(UpdateMicrocurricularLearningOutcomeRequest $request, MicrocurricularLearningOutcome $microcurricularOutcome): RedirectResponse
    {
        $microcurricularOutcome->update($request->validated());

        return to_route('admin.microcurricular-outcomes.index')->with('success', 'Resultado microcurricular actualizado exitosamente.');
    }

    public function toggleStatus(MicrocurricularLearningOutcome $microcurricularOutcome): RedirectResponse
    {
        $microcurricularOutcome->update(['is_active' => ! $microcurricularOutcome->is_active]);

        $status = $microcurricularOutcome->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Resultado microcurricular {$status} exitosamente.");
    }
}
