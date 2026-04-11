<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompetencyRequest;
use App\Http\Requests\Admin\UpdateCompetencyRequest;
use App\Models\Competency;
use App\Models\ProblematicNucleus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompetencyController extends Controller
{
    public function index(): Response
    {
        $competencies = Competency::query()
            ->with('problematicNucleus.program')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when(request('problematic_nucleus_id'), fn ($q, $nucleusId) => $q->where('problematic_nucleus_id', $nucleusId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $nuclei = ProblematicNucleus::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/competencies/index', [
            'competencies' => $competencies,
            'nuclei' => $nuclei,
            'filters' => request()->only('search', 'problematic_nucleus_id'),
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
