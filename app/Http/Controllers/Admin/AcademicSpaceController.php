<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicSpaceRequest;
use App\Http\Requests\Admin\UpdateAcademicSpaceRequest;
use App\Models\AcademicSpace;
use App\Models\Competency;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSpaceController extends Controller
{
    public function index(): Response
    {
        $academicSpaces = AcademicSpace::query()
            ->with('competency.problematicNucleus.program')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->when(request('competency_id'), fn ($q, $competencyId) => $q->where('competency_id', $competencyId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $competencies = Competency::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/academic-spaces/index', [
            'academicSpaces' => $academicSpaces,
            'competencies' => $competencies,
            'filters' => request()->only('search', 'competency_id'),
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
