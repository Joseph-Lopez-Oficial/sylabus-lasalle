<?php

use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\AcademicSpaceController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\CompetencyController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\EvaluationCriterionController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\MesocurricularLearningOutcomeController;
use App\Http\Controllers\Admin\MicrocurricularLearningOutcomeController;
use App\Http\Controllers\Admin\PerformanceLevelController;
use App\Http\Controllers\Admin\ProblematicNucleusController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfessorController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ProgrammingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\Professor\DashboardController;
use App\Http\Controllers\Professor\GradingController;
use App\Http\Controllers\Professor\StatisticsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        $role = Auth::user()->role;

        return redirect()->route(match ($role) {
            'admin' => 'admin.dashboard',
            'professor' => 'professor.dashboard',
            default => 'admin.dashboard',
        });
    }

    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    if (Auth::check()) {
        $role = Auth::user()->role;

        return redirect()->route(match ($role) {
            'admin' => 'admin.dashboard',
            'professor' => 'professor.dashboard',
            default => 'admin.dashboard',
        });
    }

    return redirect()->route('login');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

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
    Route::get('academic-spaces/{academicSpace}/statistics/export', [AcademicSpaceController::class, 'downloadStatistics'])->name('academic-spaces.statistics.export');
    Route::get('academic-spaces/{academicSpace}/edit', [AcademicSpaceController::class, 'edit'])->name('academic-spaces.edit');
    Route::put('academic-spaces/{academicSpace}', [AcademicSpaceController::class, 'update'])->name('academic-spaces.update');
    Route::patch('academic-spaces/{academicSpace}/toggle-status', [AcademicSpaceController::class, 'toggleStatus'])->name('academic-spaces.toggle-status');

    // Resultados Microcurriculares
    Route::get('microcurricular-outcomes', [MicrocurricularLearningOutcomeController::class, 'index'])->name('microcurricular-outcomes.index');
    Route::get('microcurricular-outcomes/create', [MicrocurricularLearningOutcomeController::class, 'create'])->name('microcurricular-outcomes.create');
    Route::post('microcurricular-outcomes', [MicrocurricularLearningOutcomeController::class, 'store'])->name('microcurricular-outcomes.store');
    Route::get('microcurricular-outcomes/{microcurricularOutcome}', [MicrocurricularLearningOutcomeController::class, 'show'])->name('microcurricular-outcomes.show');
    Route::get('microcurricular-outcomes/{microcurricularOutcome}/export', [MicrocurricularLearningOutcomeController::class, 'downloadReport'])->name('microcurricular-outcomes.export');
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

    // Profesores
    Route::get('professors', [ProfessorController::class, 'index'])->name('professors.index');
    Route::get('professors/create', [ProfessorController::class, 'create'])->name('professors.create');
    Route::post('professors', [ProfessorController::class, 'store'])->name('professors.store');
    Route::get('professors/{professor}/edit', [ProfessorController::class, 'edit'])->name('professors.edit');
    Route::put('professors/{professor}', [ProfessorController::class, 'update'])->name('professors.update');
    Route::patch('professors/{professor}/toggle-status', [ProfessorController::class, 'toggleStatus'])->name('professors.toggle-status');
    Route::post('professors/import-students', [ProfessorController::class, 'importStudents'])->name('professors.import-students');

    // Estudiantes
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('students', [StudentController::class, 'store'])->name('students.store');
    Route::get('students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
    Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::patch('students/{student}/toggle-status', [StudentController::class, 'toggleStatus'])->name('students.toggle-status');

    // Períodos Académicos
    Route::get('academic-periods', [AcademicPeriodController::class, 'index'])->name('academic-periods.index');
    Route::get('academic-periods/create', [AcademicPeriodController::class, 'create'])->name('academic-periods.create');
    Route::post('academic-periods', [AcademicPeriodController::class, 'store'])->name('academic-periods.store');
    Route::get('academic-periods/{academicPeriod}/edit', [AcademicPeriodController::class, 'edit'])->name('academic-periods.edit');
    Route::put('academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'update'])->name('academic-periods.update');
    Route::patch('academic-periods/{academicPeriod}/toggle-status', [AcademicPeriodController::class, 'toggleStatus'])->name('academic-periods.toggle-status');

    // Criterios de Evaluación (configuración del modelo evaluativo)
    Route::get('evaluation-criteria', [EvaluationCriterionController::class, 'index'])->name('evaluation-criteria.index');
    Route::get('evaluation-criteria/create', [EvaluationCriterionController::class, 'create'])->name('evaluation-criteria.create');
    Route::post('evaluation-criteria', [EvaluationCriterionController::class, 'store'])->name('evaluation-criteria.store');
    Route::get('evaluation-criteria/{evaluationCriterion}/edit', [EvaluationCriterionController::class, 'edit'])->name('evaluation-criteria.edit');
    Route::put('evaluation-criteria/{evaluationCriterion}', [EvaluationCriterionController::class, 'update'])->name('evaluation-criteria.update');
    Route::patch('evaluation-criteria/{evaluationCriterion}/toggle-status', [EvaluationCriterionController::class, 'toggleStatus'])->name('evaluation-criteria.toggle-status');

    // Niveles de Desempeño (configuración de la escala de calificación)
    Route::get('performance-levels', [PerformanceLevelController::class, 'index'])->name('performance-levels.index');
    Route::get('performance-levels/create', [PerformanceLevelController::class, 'create'])->name('performance-levels.create');
    Route::post('performance-levels', [PerformanceLevelController::class, 'store'])->name('performance-levels.store');
    Route::get('performance-levels/{performanceLevel}/edit', [PerformanceLevelController::class, 'edit'])->name('performance-levels.edit');
    Route::put('performance-levels/{performanceLevel}', [PerformanceLevelController::class, 'update'])->name('performance-levels.update');
    Route::patch('performance-levels/{performanceLevel}/toggle-status', [PerformanceLevelController::class, 'toggleStatus'])->name('performance-levels.toggle-status');

    // Programaciones
    Route::get('programmings', [ProgrammingController::class, 'index'])->name('programmings.index');
    Route::get('programmings/create', [ProgrammingController::class, 'create'])->name('programmings.create');
    Route::post('programmings', [ProgrammingController::class, 'store'])->name('programmings.store');
    Route::get('programmings/{programming}', [ProgrammingController::class, 'show'])->name('programmings.show');
    Route::get('programmings/{programming}/statistics', [ProgrammingController::class, 'statistics'])->name('programmings.statistics');
    Route::get('programmings/{programming}/statistics/export', [ProgrammingController::class, 'downloadStatistics'])->name('programmings.statistics.export');
    Route::get('programmings/{programming}/edit', [ProgrammingController::class, 'edit'])->name('programmings.edit');
    Route::put('programmings/{programming}', [ProgrammingController::class, 'update'])->name('programmings.update');
    Route::patch('programmings/{programming}/toggle-status', [ProgrammingController::class, 'toggleStatus'])->name('programmings.toggle-status');

    // Inscripciones (anidadas bajo programaciones)
    Route::post('programmings/{programming}/enrollments', [EnrollmentController::class, 'store'])->name('programmings.enrollments.store');
    Route::patch('programmings/{programming}/enrollments/{enrollment}/toggle-status', [EnrollmentController::class, 'toggleStatus'])
        ->scopeBindings()
        ->name('programmings.enrollments.toggle-status');
    Route::post('programmings/{programming}/enrollments/import', [EnrollmentController::class, 'import'])->name('programmings.enrollments.import');
    Route::get('programmings/{programming}/enrollments/template', [EnrollmentController::class, 'downloadTemplate'])->name('programmings.enrollments.template');
});

Route::middleware(['auth', 'professor'])->prefix('professor')->name('professor.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Calificaciones
    Route::get('programmings/{programming}/grading', [GradingController::class, 'show'])->name('programmings.grading.show');
    Route::post('programmings/{programming}/grading/grades', [GradingController::class, 'saveGrades'])->name('programmings.grading.save');
    Route::post('programmings/{programming}/grading/confirm', [GradingController::class, 'confirmConsolidation'])->name('programmings.grading.confirm');
    Route::get('programmings/{programming}/grading/template', [GradingController::class, 'downloadTemplate'])->name('programmings.grading.template');
    Route::get('programmings/{programming}/grading/import', [GradingController::class, 'importPage'])->name('programmings.grading.import-page');
    Route::post('programmings/{programming}/grading/import', [GradingController::class, 'importGrades'])->name('programmings.grading.import');
    Route::get('programmings/{programming}/grading/report', [GradingController::class, 'downloadReport'])->name('programmings.grading.report');

    // Inscripciones del profesor en su programación
    Route::get('programmings/{programming}/enrollments/search', [GradingController::class, 'searchStudents'])->name('programmings.enrollments.search');
    Route::post('programmings/{programming}/enrollments', [GradingController::class, 'enrollByDocument'])->name('programmings.enrollments.store');
    Route::get('programmings/{programming}/enrollments/template', [GradingController::class, 'downloadEnrollmentTemplate'])->name('programmings.enrollments.template');
    Route::post('programmings/{programming}/enrollments/import', [GradingController::class, 'importEnrollments'])->name('programmings.enrollments.import');

    // Estadísticas
    Route::get('programmings/{programming}/statistics', [StatisticsController::class, 'show'])->name('programmings.statistics.show');
});

require __DIR__.'/settings.php';
