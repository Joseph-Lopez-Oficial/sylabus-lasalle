<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProblematicNucleusRequest;
use App\Http\Requests\Admin\UpdateProblematicNucleusRequest;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProblematicNucleusController extends Controller
{
    public function index(): Response
    {
        $nuclei = ProblematicNucleus::query()
            ->with('program')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when(request('program_id'), fn ($q, $programId) => $q->where('program_id', $programId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $programs = Program::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/problematic-nuclei/index', [
            'nuclei' => $nuclei,
            'programs' => $programs,
            'filters' => request()->only('search', 'program_id'),
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
