<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(): Response
    {
        $students = Student::query()
            ->when(request('search'), fn ($q, $search) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('document_number', 'like', "%{$search}%"))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/students/index', [
            'students' => $students,
            'filters' => request()->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/students/create');
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        Student::create($request->validated());

        return to_route('admin.students.index')->with('success', 'Estudiante creado exitosamente.');
    }

    public function edit(Student $student): Response
    {
        return Inertia::render('admin/students/edit', [
            'student' => $student,
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        return to_route('admin.students.index')->with('success', 'Estudiante actualizado exitosamente.');
    }

    public function toggleStatus(Student $student): RedirectResponse
    {
        $student->update(['is_active' => ! $student->is_active]);

        $status = $student->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Estudiante {$status} exitosamente.");
    }
}
