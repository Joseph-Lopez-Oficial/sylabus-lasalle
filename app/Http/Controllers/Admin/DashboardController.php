<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSpace;
use App\Models\Faculty;
use App\Models\Professor;
use App\Models\Programming;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/dashboard', [
            'metrics' => [
                'faculties' => Faculty::count(),
                'academic_spaces' => AcademicSpace::count(),
                'professors' => Professor::count(),
                'students' => Student::count(),
                'programmings' => Programming::where('is_active', true)->count(),
            ],
        ]);
    }
}
