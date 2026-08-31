<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompetencyRequest;
use App\Http\Requests\Admin\UpdateCompetencyRequest;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompetencyController extends Controller
{
    use PaginatesListings;

    public function index(): Response
    {
        $facultyId = request('faculty_id');
        $programId = request('program_id');
        $nucleusId = request('problematic_nucleus_id');

        $competencies = Competency::query()
            ->with('problematicNucleus.program.faculty')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($nucleusId, fn ($q) => $q->where('problematic_nucleus_id', $nucleusId))
            ->when($programId && ! $nucleusId, fn ($q) => $q->whereHas('problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId, fn ($q) => $q->whereHas('problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
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

        return Inertia::render('admin/competencies/index', [
            'competencies' => $competencies,
            'faculties' => $faculties,
            'programs' => $programs,
            'nuclei' => $nuclei,
            'filters' => request()->only('search', 'faculty_id', 'program_id', 'problematic_nucleus_id', 'per_page'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/competencies/create', [
            'nuclei' => ProblematicNucleus::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCompetencyRequest $request): RedirectResponse
    {
        Competency::create($request->validated());

        return to_route('admin.competencies.index')->with('success', 'Competencia creada exitosamente.');
    }

    public function edit(Competency $competency): Response
    {
        return Inertia::render('admin/competencies/edit', [
            'competency' => $competency->load('problematicNucleus'),
            'nuclei' => ProblematicNucleus::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateCompetencyRequest $request, Competency $competency): RedirectResponse
    {
        $competency->update($request->validated());

        return to_route('admin.competencies.index')->with('success', 'Competencia actualizada exitosamente.');
    }

    public function toggleStatus(Competency $competency): RedirectResponse
    {
        $competency->update(['is_active' => ! $competency->is_active]);

        $status = $competency->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Competencia {$status} exitosamente.");
    }
}
