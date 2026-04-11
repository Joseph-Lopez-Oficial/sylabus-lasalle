<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Models\Faculty;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index(): Response
    {
        $programs = Program::query()
            ->with('faculty')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->when(request('faculty_id'), fn ($q, $facultyId) => $q->where('faculty_id', $facultyId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $faculties = Faculty::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/programs/index', [
            'programs' => $programs,
            'faculties' => $faculties,
            'filters' => request()->only('search', 'faculty_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/programs/create', [
            'faculties' => Faculty::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        Program::create($request->validated());

        return to_route('admin.programs.index')->with('success', 'Programa creado exitosamente.');
    }

    public function edit(Program $program): Response
    {
        return Inertia::render('admin/programs/edit', [
            'program' => $program->load('faculty'),
            'faculties' => Faculty::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        return to_route('admin.programs.index')->with('success', 'Programa actualizado exitosamente.');
    }

    public function toggleStatus(Program $program): RedirectResponse
    {
        $program->update(['is_active' => ! $program->is_active]);

        $status = $program->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Programa {$status} exitosamente.");
    }
}
