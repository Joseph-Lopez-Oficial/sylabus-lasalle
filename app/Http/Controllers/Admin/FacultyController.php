<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFacultyRequest;
use App\Http\Requests\Admin\UpdateFacultyRequest;
use App\Models\Faculty;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FacultyController extends Controller
{
    public function index(): Response
    {
        $faculties = Faculty::query()
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/faculties/index', [
            'faculties' => $faculties,
            'filters' => request()->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/faculties/create');
    }

    public function store(StoreFacultyRequest $request): RedirectResponse
    {
        Faculty::create($request->validated());

        return to_route('admin.faculties.index')->with('success', 'Facultad creada exitosamente.');
    }

    public function edit(Faculty $faculty): Response
    {
        return Inertia::render('admin/faculties/edit', [
            'faculty' => $faculty,
        ]);
    }

    public function update(UpdateFacultyRequest $request, Faculty $faculty): RedirectResponse
    {
        $faculty->update($request->validated());

        return to_route('admin.faculties.index')->with('success', 'Facultad actualizada exitosamente.');
    }

    public function toggleStatus(Faculty $faculty): RedirectResponse
    {
        $faculty->update(['is_active' => ! $faculty->is_active]);

        $status = $faculty->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Facultad {$status} exitosamente.");
    }
}
