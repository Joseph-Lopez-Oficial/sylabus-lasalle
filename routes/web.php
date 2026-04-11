<?php

use App\Http\Controllers\Admin\AcademicSpaceController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\CompetencyController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\MesocurricularLearningOutcomeController;
use App\Http\Controllers\Admin\MicrocurricularLearningOutcomeController;
use App\Http\Controllers\Admin\ProblematicNucleusController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\TopicController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('admin/dashboard');
    })->name('dashboard');

    // Facultades
    Route::get('faculties', [FacultyController::class, 'index'])->name('faculties.index');
    Route::get('faculties/create', [FacultyController::class, 'create'])->name('faculties.create');
    Route::post('faculties', [FacultyController::class, 'store'])->name('faculties.store');
    Route::get('faculties/{faculty}/edit', [FacultyController::class, 'edit'])->name('faculties.edit');
    Route::put('faculties/{faculty}', [FacultyController::class, 'update'])->name('faculties.update');
    Route::patch('faculties/{faculty}/toggle-status', [FacultyController::class, 'toggleStatus'])->name('faculties.toggle-status');

    // Programas
    Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
    Route::get('programs/create', [ProgramController::class, 'create'])->name('programs.create');
    Route::post('programs', [ProgramController::class, 'store'])->name('programs.store');
    Route::get('programs/{program}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
    Route::put('programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
    Route::patch('programs/{program}/toggle-status', [ProgramController::class, 'toggleStatus'])->name('programs.toggle-status');

    // Núcleos Problemáticos
    Route::get('problematic-nuclei', [ProblematicNucleusController::class, 'index'])->name('problematic-nuclei.index');
    Route::get('problematic-nuclei/create', [ProblematicNucleusController::class, 'create'])->name('problematic-nuclei.create');
    Route::post('problematic-nuclei', [ProblematicNucleusController::class, 'store'])->name('problematic-nuclei.store');
    Route::get('problematic-nuclei/{problematicNucleus}/edit', [ProblematicNucleusController::class, 'edit'])->name('problematic-nuclei.edit');
    Route::put('problematic-nuclei/{problematicNucleus}', [ProblematicNucleusController::class, 'update'])->name('problematic-nuclei.update');
    Route::patch('problematic-nuclei/{problematicNucleus}/toggle-status', [ProblematicNucleusController::class, 'toggleStatus'])->name('problematic-nuclei.toggle-status');

    // Competencias
    Route::get('competencies', [CompetencyController::class, 'index'])->name('competencies.index');
    Route::get('competencies/create', [CompetencyController::class, 'create'])->name('competencies.create');
    Route::post('competencies', [CompetencyController::class, 'store'])->name('competencies.store');
    Route::get('competencies/{competency}/edit', [CompetencyController::class, 'edit'])->name('competencies.edit');
    Route::put('competencies/{competency}', [CompetencyController::class, 'update'])->name('competencies.update');
    Route::patch('competencies/{competency}/toggle-status', [CompetencyController::class, 'toggleStatus'])->name('competencies.toggle-status');

    // Resultados Mesocurriculares
    Route::get('mesocurricular-outcomes', [MesocurricularLearningOutcomeController::class, 'index'])->name('mesocurricular-outcomes.index');
    Route::get('mesocurricular-outcomes/create', [MesocurricularLearningOutcomeController::class, 'create'])->name('mesocurricular-outcomes.create');
    Route::post('mesocurricular-outcomes', [MesocurricularLearningOutcomeController::class, 'store'])->name('mesocurricular-outcomes.store');
    Route::get('mesocurricular-outcomes/{mesocurricularOutcome}/edit', [MesocurricularLearningOutcomeController::class, 'edit'])->name('mesocurricular-outcomes.edit');
    Route::put('mesocurricular-outcomes/{mesocurricularOutcome}', [MesocurricularLearningOutcomeController::class, 'update'])->name('mesocurricular-outcomes.update');
    Route::patch('mesocurricular-outcomes/{mesocurricularOutcome}/toggle-status', [MesocurricularLearningOutcomeController::class, 'toggleStatus'])->name('mesocurricular-outcomes.toggle-status');

    // Espacios Académicos
    Route::get('academic-spaces', [AcademicSpaceController::class, 'index'])->name('academic-spaces.index');
    Route::get('academic-spaces/create', [AcademicSpaceController::class, 'create'])->name('academic-spaces.create');
    Route::post('academic-spaces', [AcademicSpaceController::class, 'store'])->name('academic-spaces.store');
    Route::get('academic-spaces/{academicSpace}', [AcademicSpaceController::class, 'show'])->name('academic-spaces.show');
    Route::get('academic-spaces/{academicSpace}/edit', [AcademicSpaceController::class, 'edit'])->name('academic-spaces.edit');
    Route::put('academic-spaces/{academicSpace}', [AcademicSpaceController::class, 'update'])->name('academic-spaces.update');
    Route::patch('academic-spaces/{academicSpace}/toggle-status', [AcademicSpaceController::class, 'toggleStatus'])->name('academic-spaces.toggle-status');

    // Resultados Microcurriculares
    Route::get('microcurricular-outcomes', [MicrocurricularLearningOutcomeController::class, 'index'])->name('microcurricular-outcomes.index');
    Route::get('microcurricular-outcomes/create', [MicrocurricularLearningOutcomeController::class, 'create'])->name('microcurricular-outcomes.create');
    Route::post('microcurricular-outcomes', [MicrocurricularLearningOutcomeController::class, 'store'])->name('microcurricular-outcomes.store');
    Route::get('microcurricular-outcomes/{microcurricularOutcome}/edit', [MicrocurricularLearningOutcomeController::class, 'edit'])->name('microcurricular-outcomes.edit');
    Route::put('microcurricular-outcomes/{microcurricularOutcome}', [MicrocurricularLearningOutcomeController::class, 'update'])->name('microcurricular-outcomes.update');
    Route::patch('microcurricular-outcomes/{microcurricularOutcome}/toggle-status', [MicrocurricularLearningOutcomeController::class, 'toggleStatus'])->name('microcurricular-outcomes.toggle-status');

    // Temas
    Route::get('topics', [TopicController::class, 'index'])->name('topics.index');
    Route::get('topics/create', [TopicController::class, 'create'])->name('topics.create');
    Route::post('topics', [TopicController::class, 'store'])->name('topics.store');
    Route::get('topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
    Route::put('topics/{topic}', [TopicController::class, 'update'])->name('topics.update');
    Route::patch('topics/{topic}/toggle-status', [TopicController::class, 'toggleStatus'])->name('topics.toggle-status');

    // Actividades
    Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('activities/create', [ActivityController::class, 'create'])->name('activities.create');
    Route::post('activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::get('activities/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::patch('activities/{activity}/toggle-status', [ActivityController::class, 'toggleStatus'])->name('activities.toggle-status');

    // Productos
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
});

Route::middleware(['auth', 'professor'])->prefix('professor')->name('professor.')->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('professor/dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
