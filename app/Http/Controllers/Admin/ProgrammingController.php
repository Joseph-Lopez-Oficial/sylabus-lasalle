<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgrammingRequest;
use App\Http\Requests\Admin\UpdateProgrammingRequest;
use App\Models\AcademicPeriod;
use App\Models\AcademicSpace;
use App\Models\Modality;
use App\Models\Professor;
use App\Models\Programming;
use App\Models\Student;
use App\Services\GradingService;
use App\Services\StatisticsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProgrammingController extends Controller
{
    public function index(): Response
    {
        $programmings = Programming::query()
            ->with(['academicSpace', 'professor', 'modality', 'academicPeriod'])
            ->when(request('search'), fn($q, $search) => $q->where('group', 'like', "%{$search}%"))
            ->when(request('professor_id'), fn($q, $id) => $q->where('professor_id', $id))
            ->when(request('academic_space_id'), fn($q, $id) => $q->where('academic_space_id', $id))
            ->when(request('academic_period_id'), fn($q, $id) => $q->where('academic_period_id', $id))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/programmings/index', [
            'programmings' => $programmings,
            'professors' => Professor::query()->active()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'academicSpaces' => AcademicSpace::query()->active()->orderBy('name')->get(['id', 'name']),
            'academicPeriods' => AcademicPeriod::query()->orderByDesc('name')->get(['id', 'name']),
            'filters' => request()->only('search', 'professor_id', 'academic_space_id', 'academic_period_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/programmings/create', [
            'academicSpaces' => AcademicSpace::query()->active()->orderBy('name')->get(['id', 'name']),
            'professors' => Professor::query()->active()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'modalities' => Modality::query()->orderBy('name')->get(['id', 'name']),
            'academicPeriods' => AcademicPeriod::query()->active()->orderByDesc('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProgrammingRequest $request): RedirectResponse
    {
        Programming::create($request->validated());

        return to_route('admin.programmings.index')->with('success', 'Programación creada exitosamente.');
    }

    public function show(Programming $programming): Response
    {
        $programming->load([
            'academicSpace',
            'professor',
            'modality',
            'enrollments.student',
        ]);

        return Inertia::render('admin/programmings/show', [
            'programming' => $programming,
            'students' => Student::query()->active()->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'document_number']),
            'import_results' => session('import_results'),
        ]);
    }

    public function edit(Programming $programming): Response
    {
        return Inertia::render('admin/programmings/edit', [
            'programming' => $programming->load(['academicSpace', 'professor', 'modality', 'academicPeriod']),
            'academicSpaces' => AcademicSpace::query()->active()->orderBy('name')->get(['id', 'name']),
            'professors' => Professor::query()->active()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'modalities' => Modality::query()->orderBy('name')->get(['id', 'name']),
            'academicPeriods' => AcademicPeriod::query()->active()->orderByDesc('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProgrammingRequest $request, Programming $programming): RedirectResponse
    {
        $programming->update($request->validated());

        return to_route('admin.programmings.index')->with('success', 'Programación actualizada exitosamente.');
    }

    public function toggleStatus(Programming $programming): RedirectResponse
    {
        $programming->update(['is_active' => ! $programming->is_active]);

        $status = $programming->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Programación {$status} exitosamente.");
    }

    public function statistics(Programming $programming, GradingService $gradingService, StatisticsService $statisticsService): Response
    {
        $programming->load(['academicSpace.competency', 'professor', 'modality', 'academicPeriod']);

        $completeness = $gradingService->completeness($programming);

        $statistics = $statisticsService->calculate($programming);

        return Inertia::render('admin/programmings/statistics', [
            'programming' => [
                'id' => $programming->id,
                'group' => $programming->group,
                'academic_period' => $programming->academicPeriod ? ['name' => $programming->academicPeriod->name] : null,
                'academic_space' => $programming->academicSpace ? $programming->academicSpace->only(['id', 'name', 'code']) : null,
                'professor' => $programming->professor ? [
                    'id' => $programming->professor->id,
                    'first_name' => $programming->professor->first_name,
                    'last_name' => $programming->professor->last_name,
                ] : null,
                'modality' => $programming->modality ? $programming->modality->only(['id', 'name']) : null,
            ],
            'statistics' => $statistics,
            'completeness' => $completeness,
        ]);
    }
}
