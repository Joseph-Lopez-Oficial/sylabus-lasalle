<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportEnrollmentsRequest;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Imports\EnrollmentsImport;
use App\Models\Enrollment;
use App\Models\Programming;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentController extends Controller
{
    public function store(StoreEnrollmentRequest $request, Programming $programming): RedirectResponse
    {
        Enrollment::create([
            'programming_id' => $programming->id,
            'student_id' => $request->student_id,
            'enrolled_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        return back()->with('success', 'Estudiante inscrito exitosamente.');
    }

    public function toggleStatus(Programming $programming, Enrollment $enrollment): RedirectResponse
    {
        $enrollment->update(['is_active' => ! $enrollment->is_active]);

        $status = $enrollment->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Inscripción {$status} exitosamente.");
    }

    public function import(ImportEnrollmentsRequest $request, Programming $programming): RedirectResponse
    {
        $import = new EnrollmentsImport($programming);
        Excel::import($import, $request->file('file'));

        $created = count(array_filter($import->results, fn ($r) => $r['status'] === 'created'));
        $skipped = count(array_filter($import->results, fn ($r) => $r['status'] === 'skipped'));
        $errors = count(array_filter($import->results, fn ($r) => $r['status'] === 'error'));

        return back()->with([
            'success' => "Importación completada: {$created} inscritos, {$skipped} ya existían, {$errors} errores.",
            'import_results' => $import->results,
        ]);
    }
}
