<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Http\Requests\Admin\StoreProfessorRequest;
use App\Http\Requests\Admin\UpdateProfessorRequest;
use App\Imports\StudentsImport;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ProfessorController extends Controller
{
    public function index(): Response
    {
        $professors = Professor::query()
            ->with('user')
            ->when(request('search'), fn ($q, $search) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('document_number', 'like', "%{$search}%"))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/professors/index', [
            'professors' => $professors,
            'filters' => request()->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/professors/create');
    }

    public function store(StoreProfessorRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $userId = null;

            if ($request->boolean('create_user')) {
                $user = User::create([
                    'name' => $request->first_name.' '.$request->last_name,
                    'email' => $request->user_email,
                    'password' => Hash::make($request->user_password),
                    'role' => 'professor',
                ]);
                $userId = $user->id;
            }

            Professor::create([
                'user_id' => $userId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'document_number' => $request->document_number,
                'institutional_email' => $request->institutional_email,
                'phone' => $request->phone,
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return to_route('admin.professors.index')->with('success', 'Profesor creado exitosamente.');
    }

    public function edit(Professor $professor): Response
    {
        return Inertia::render('admin/professors/edit', [
            'professor' => $professor->load('user'),
        ]);
    }

    public function update(UpdateProfessorRequest $request, Professor $professor): RedirectResponse
    {
        $professor->update($request->validated());

        return to_route('admin.professors.index')->with('success', 'Profesor actualizado exitosamente.');
    }

    public function toggleStatus(Professor $professor): RedirectResponse
    {
        $professor->update(['is_active' => ! $professor->is_active]);

        $status = $professor->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Profesor {$status} exitosamente.");
    }

    public function importStudents(ImportStudentsRequest $request): RedirectResponse
    {
        $import = new StudentsImport;
        Excel::import($import, $request->file('file'));

        $created = count(array_filter($import->results, fn ($r) => $r['status'] === 'created'));
        $updated = count(array_filter($import->results, fn ($r) => $r['status'] === 'updated'));
        $errors = count(array_filter($import->results, fn ($r) => $r['status'] === 'error'));

        return back()->with([
            'success' => "Importación completada: {$created} creados, {$updated} actualizados, {$errors} errores.",
            'import_results' => $import->results,
        ]);
    }
}
