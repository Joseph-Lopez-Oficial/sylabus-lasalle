<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StudentTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportEnrollmentsRequest;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Imports\StudentsImport;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            'import_results' => session('import_results'),
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

    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new StudentTemplateExport, 'plantilla_estudiantes.xlsx');
    }

    public function import(ImportEnrollmentsRequest $request): RedirectResponse
    {
        $import = new StudentsImport;
        Excel::import($import, $request->file('file'));

        $created = count(array_filter($import->results, fn ($r) => $r['status'] === 'created'));
        $updated = count(array_filter($import->results, fn ($r) => in_array($r['status'], ['updated', 'restored'])));
        $errors = count(array_filter($import->results, fn ($r) => $r['status'] === 'error'));

        return back()->with([
            'success' => "Importación completada: {$created} creados, {$updated} actualizados, {$errors} errores.",
            'import_results' => $import->results,
        ]);
    }
}
