<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMicrocurricularLearningOutcomeRequest;
use App\Http\Requests\Admin\UpdateMicrocurricularLearningOutcomeRequest;
use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\MesocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcomeType;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MicrocurricularLearningOutcomeController extends Controller
{
    public function index(): Response
    {
        $facultyId = request('faculty_id');
        $programId = request('program_id');
        $nucleusId = request('problematic_nucleus_id');
        $competencyId = request('competency_id');
        $spaceId = request('academic_space_id');

        $outcomes = MicrocurricularLearningOutcome::query()
            ->with(['academicSpace.competency.problematicNucleus.program.faculty', 'type', 'mesocurricularLearningOutcome'])
            ->when(request('search'), fn ($q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->when($spaceId, fn ($q) => $q->where('academic_space_id', $spaceId))
            ->when($competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace', fn ($sq) => $sq->where('competency_id', $competencyId)))
            ->when($nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderByDesc('id')
            ->paginate(15)
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
            ->get(['id', 'name']);

        $academicSpaces = AcademicSpace::query()->active()
            ->when($competencyId, fn ($q) => $q->where('competency_id', $competencyId))
            ->when($nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/microcurricular-outcomes/index', [
            'outcomes' => $outcomes,
            'faculties' => $faculties,
            'programs' => $programs,
            'nuclei' => $nuclei,
            'competencies' => $competencies,
            'academicSpaces' => $academicSpaces,
            'filters' => request()->only('search', 'faculty_id', 'program_id', 'problematic_nucleus_id', 'competency_id', 'academic_space_id'),
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
