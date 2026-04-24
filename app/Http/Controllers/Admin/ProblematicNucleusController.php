<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProblematicNucleusRequest;
use App\Http\Requests\Admin\UpdateProblematicNucleusRequest;
use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProblematicNucleusController extends Controller
{
    public function index(): Response
    {
        $facultyId = request('faculty_id');
        $programId = request('program_id');

        $nuclei = ProblematicNucleus::query()
            ->with('program.faculty')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($facultyId && ! $programId, fn ($q) => $q->whereHas('program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $faculties = Faculty::query()->active()->orderBy('name')->get(['id', 'name']);

        $programs = Program::query()->active()
            ->when($facultyId, fn ($q) => $q->where('faculty_id', $facultyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/problematic-nuclei/index', [
            'nuclei' => $nuclei,
            'faculties' => $faculties,
            'programs' => $programs,
            'filters' => request()->only('search', 'faculty_id', 'program_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/problematic-nuclei/create', [
            'programs' => Program::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProblematicNucleusRequest $request): RedirectResponse
    {
        ProblematicNucleus::create($request->validated());

        return to_route('admin.problematic-nuclei.index')->with('success', 'Núcleo problemático creado exitosamente.');
    }

    public function edit(ProblematicNucleus $problematicNucleus): Response
    {
        return Inertia::render('admin/problematic-nuclei/edit', [
            'nucleus' => $problematicNucleus->load('program'),
            'programs' => Program::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProblematicNucleusRequest $request, ProblematicNucleus $problematicNucleus): RedirectResponse
    {
        $problematicNucleus->update($request->validated());

        return to_route('admin.problematic-nuclei.index')->with('success', 'Núcleo problemático actualizado exitosamente.');
    }

    public function toggleStatus(ProblematicNucleus $problematicNucleus): RedirectResponse
    {
        $problematicNucleus->update(['is_active' => ! $problematicNucleus->is_active]);

        $status = $problematicNucleus->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Núcleo problemático {$status} exitosamente.");
    }
}
