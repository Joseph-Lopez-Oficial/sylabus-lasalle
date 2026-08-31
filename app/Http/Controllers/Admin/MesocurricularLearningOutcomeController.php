<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMesocurricularLearningOutcomeRequest;
use App\Http\Requests\Admin\UpdateMesocurricularLearningOutcomeRequest;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\MesocurricularLearningOutcome;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MesocurricularLearningOutcomeController extends Controller
{
    use PaginatesListings;

    public function index(): Response
    {
        $facultyId = request('faculty_id');
        $programId = request('program_id');
        $nucleusId = request('problematic_nucleus_id');
        $competencyId = request('competency_id');

        $outcomes = MesocurricularLearningOutcome::query()
            ->with('competency.problematicNucleus.program.faculty')
            ->when(request('search'), fn ($q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->when($competencyId, fn ($q) => $q->where('competency_id', $competencyId))
            ->when($nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('id')
            ->paginate($this->perPage())
            ->withQueryString();

        $faculties = Faculty::query()->active()->orderBy('name')->get(['id', 'name']);

        $programs = Program::query()->active()
            ->when($facultyId, fn ($q) => $q->where('faculty_id', $facultyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $nuclei = ProblematicNucleus::query()->active()
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($facultyId && ! $programId, fn ($q) => $q->whereHas('program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'name']);

        $competencies = Competency::query()->active()
            ->when($nucleusId, fn ($q) => $q->where('problematic_nucleus_id', $nucleusId))
            ->when($programId && ! $nucleusId, fn ($q) => $q->whereHas('problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId, fn ($q) => $q->whereHas('problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return Inertia::render('admin/mesocurricular-outcomes/index', [
            'outcomes' => $outcomes,
            'faculties' => $faculties,
            'programs' => $programs,
            'nuclei' => $nuclei,
            'competencies' => $competencies,
            'filters' => request()->only(['search', 'faculty_id', 'program_id', 'problematic_nucleus_id', 'competency_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/mesocurricular-outcomes/create', [
            'competencies' => Competency::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
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
            'competencies' => Competency::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
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
