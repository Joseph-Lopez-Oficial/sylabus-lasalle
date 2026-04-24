<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicSpaceRequest;
use App\Http\Requests\Admin\UpdateAcademicSpaceRequest;
use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSpaceController extends Controller
{
    public function index(): Response
    {
        $facultyId = request('faculty_id');
        $programId = request('program_id');
        $nucleusId = request('problematic_nucleus_id');
        $competencyId = request('competency_id');

        $academicSpaces = AcademicSpace::query()
            ->with('competency.problematicNucleus.program.faculty')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->when($competencyId, fn ($q) => $q->where('competency_id', $competencyId))
            ->when($nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
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

        return Inertia::render('admin/academic-spaces/index', [
            'academicSpaces' => $academicSpaces,
            'faculties' => $faculties,
            'programs' => $programs,
            'nuclei' => $nuclei,
            'competencies' => $competencies,
            'filters' => request()->only('search', 'faculty_id', 'program_id', 'problematic_nucleus_id', 'competency_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/academic-spaces/create', [
            'competencies' => Competency::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreAcademicSpaceRequest $request): RedirectResponse
    {
        AcademicSpace::create($request->validated());

        return to_route('admin.academic-spaces.index')->with('success', 'Espacio académico creado exitosamente.');
    }

    public function show(AcademicSpace $academicSpace): Response
    {
        $academicSpace->load([
            'competency',
            'microcurricularLearningOutcomes.type',
            'topics' => fn ($q) => $q->orderBy('order'),
            'programmings.professor',
        ]);

        return Inertia::render('admin/academic-spaces/show', [
            'academicSpace' => $academicSpace,
        ]);
    }

    public function edit(AcademicSpace $academicSpace): Response
    {
        return Inertia::render('admin/academic-spaces/edit', [
            'academicSpace' => $academicSpace->load('competency'),
            'competencies' => Competency::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateAcademicSpaceRequest $request, AcademicSpace $academicSpace): RedirectResponse
    {
        $academicSpace->update($request->validated());

        return to_route('admin.academic-spaces.index')->with('success', 'Espacio académico actualizado exitosamente.');
    }

    public function toggleStatus(AcademicSpace $academicSpace): RedirectResponse
    {
        $academicSpace->update(['is_active' => ! $academicSpace->is_active]);

        $status = $academicSpace->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Espacio académico {$status} exitosamente.");
    }
}
